<?php
require_once '../../php/session_guard.php';
check_faculty();
require_once '../../php/db_connect.php';

$faculty_name = htmlspecialchars($_SESSION['faculty_name']);
$faculty_id   = $_SESSION['faculty_id'];
$name_parts   = explode(' ', $faculty_name);
$first_name   = $name_parts[0];
$initials     = strtoupper(substr($name_parts[0], 0, 1) . substr(end($name_parts), 0, 1));

// Fetch email
$faculty_email = '';
$stmt = $conn->prepare('SELECT email FROM faculty WHERE id = ?');
$stmt->bind_param('i', $faculty_id);
$stmt->execute();
$stmt->bind_result($faculty_email);
$stmt->fetch();
$stmt->close();

// Handle extend request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_id'])) {
    $schedule_id = (int)$_POST['schedule_id'];
    $extend_mins = (int)($_POST['extend_mins'] ?? 30);

    // Check if there's already a pending request for this slot
    $stmt = $conn->prepare("
        SELECT id FROM extension_requests
        WHERE schedule_id = ? AND faculty_id = ? AND status = 'pending'
    ");
    $stmt->bind_param('ii', $schedule_id, $faculty_id);
    $stmt->execute();
    $stmt->store_result();
    $already_requested = $stmt->num_rows > 0;
    $stmt->close();

    if (!$already_requested) {
        $stmt = $conn->prepare("
            INSERT INTO extension_requests (schedule_id, faculty_id, extend_mins)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('iii', $schedule_id, $faculty_id, $extend_mins);
        $stmt->execute();
        $stmt->close();
        $_SESSION['timetable_success'] = 'Extension request submitted!';
    } else {
        $_SESSION['timetable_error'] = 'You already have a pending request for this slot.';
    }

    header('Location: faculty-timetable.php');
    exit;
}

// Current schedule label
$today = date('l');
$current_sched = 'No class right now';
$now = date('H:i:s');

// Full weekly schedule
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$schedule_by_day = [];
foreach ($days as $day) $schedule_by_day[$day] = [];

$r = $conn->query("
    SELECT s.id, s.day_of_week, s.start_time, s.end_time,
           s.extended_until, c.room_name,
           (SELECT status FROM extension_requests
            WHERE schedule_id = s.id AND faculty_id = $faculty_id
            ORDER BY requested_at DESC LIMIT 1) AS ext_status
    FROM schedules s
    JOIN classrooms c ON c.id = s.classroom_id
    WHERE s.created_by = $faculty_id
    ORDER BY FIELD(s.day_of_week,'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
             s.start_time
");
while ($row = $r->fetch_assoc()) {
    $schedule_by_day[$row['day_of_week']][] = $row;
    // Check current schedule
    if ($row['day_of_week'] === $today && $now >= $row['start_time'] && $now <= $row['end_time']) {
        $current_sched = $row['room_name'] . ' · '
            . date('g:i A', strtotime($row['start_time'])) . ' - '
            . date('g:i A', strtotime($row['end_time']));
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!--External links-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!--Relative links-->
    <link type="icon" href="../../logo.png">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/containers.css">
    <link rel="stylesheet" href="../../css/tooltip.css">

    <title>Class Schedule – LumineSense</title>

    <style>
        .weekly-schedule-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1rem;
            width: 100%;
        }

        .day-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            min-height: 200px;
        }

        .day-card.today {
            background: var(--secondary-color-1, #6c5ce7);
        }

        .day-card.today .day-label {
            color: white !important;
        }

        .day-label {
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9f9f9f;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .day-label.today {
            color: var(--secondary-color-4, #9b00e9);
        }

        .slot-row {
            display: flex;
            flex-direction: column;
            background: #fff;
            border-radius: 8px;
            padding: 0.7rem 1rem;
            margin-bottom: 0.4rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
            width: 100%;
        }

        .slot-header {
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            padding-bottom: 0.5rem;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid #e0e0e0;
            gap: 1rem;
        }

        .slot-time-left {
            /* display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center; NOTE": Dont delete*/
        }

        .slot-time-start {
            font-size: 13px;
            font-weight: 600;
            color: #555;
            line-height: 1.2;
        }

        .slot-time-separator {
            font-size: 10px;
            color: #999;
            margin: 2px 0;
        }

        .slot-time-end {
            font-size: 13px;
            font-weight: 600;
            color: #555;
            line-height: 1.2;
        }

        .slot-time-ampm {
            color: #777;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .slot-actions-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .slot-time {
            font-size: 14px;
            color: #555;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .slot-time hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 0 0.5rem;
            flex: 1;
            max-width: 100px;
        }

        .slot-time h4 {
            font-size: 11px;
            font-weight: 600;
            color: #777;
            margin: 0;
            text-transform: uppercase;
        }

        .slot-actions {
            display: flex;
            gap: 0.25rem;
            align-items: center;
        }

        .slot-content {
            display: flex;
            flex-direction: column;
        }

        .slot-room {
            font-weight: 600;
            font-size: 18px;
            display: flex;
            align-items: center;
        }

        .slot-subject {
            font-size: 13px;
            color: var(--muted-dark);

            span {
                font-size: 10px;
            }
        }

        .extend-icon-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            cursor: pointer;
            border-radius: 8px;
            background: #e9d5ff;
            color: var(--secondary-color-1);
            transition: background 0.5s, color 0.2s;
        }

        .extend-icon-btn:hover {
            background: var(--secondary-color-1);
            color: white;
            transform: scale(1.08);
        }

        .badge-ext-pending {
            background: #fff3cd;
            color: #856404;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 14px;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .badge-ext-approved {
            background: #d1e7dd;
            color: #0f5132;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 14px;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .badge-ext-rejected {
            background: #f8d7da;
            color: #842029;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 14px;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .no-sched {
            color: #ccc;
            font-size: 13px;
            padding: 0.3rem 0;
            text-align: center;
        }

        @media (max-width: 1200px) {
            .weekly-schedule-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 768px) {
            .weekly-schedule-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .weekly-schedule-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="contrast-bg">
    <div class="parent-container">

        <?php include '../../php/includes/faculty-topbar.php'; ?>

        <div class="child-container">
            <div class="main-container homepage gap-3" style="flex-direction:column;">

                <!-- Flash messages -->
                <?php if (!empty($_SESSION['timetable_success'])): ?>
                    <div class="alert alert-success">
                        ✅ <?= htmlspecialchars($_SESSION['timetable_success']) ?>
                    </div>
                    <?php unset($_SESSION['timetable_success']); ?>
                <?php endif; ?>
                <?php if (!empty($_SESSION['timetable_error'])): ?>
                    <div class="alert alert-warning">
                        ⚠️ <?= htmlspecialchars($_SESSION['timetable_error']) ?>
                    </div>
                    <?php unset($_SESSION['timetable_error']); ?>
                <?php endif; ?>

                <!-- Weekly schedule -->
                <div class="weekly-schedule-grid">
                    <?php foreach ($days as $day):
                        $is_today = ($day === $today);
                        $slots    = $schedule_by_day[$day];
                    ?>
                        <div class="day-card <?= $is_today ? 'today' : '' ?>">
                            <div class="day-label">
                                <?= $day ?> <?= $is_today ? '· Today' : '' ?>
                            </div>

                            <?php if (empty($slots)): ?>
                                <p class="no-sched">No classes scheduled.</p>
                                <?php else: foreach ($slots as $slot):
                                    $start    = date('g:i A', strtotime($slot['start_time']));
                                    $end      = date('g:i A', strtotime($slot['end_time']));
                                    $ext      = $slot['extended_until']
                                        ? date('g:i A', strtotime($slot['extended_until']))
                                        : null;
                                    $ext_status = $slot['ext_status'];
                                ?>
                                    <div class="slot-row">
                                        <div class="slot-header">
                                            <div class="slot-time-left">
                                                <?php
                                                // Start time
                                                $start_parts = explode(' ', $start);
                                                $start_time_part = $start_parts[0];
                                                $start_ampm = isset($start_parts[1]) ? $start_parts[1] : 'AM';

                                                // End time
                                                $end_parts = explode(' ', $end);
                                                $end_time_part = $end_parts[0];
                                                $end_ampm = isset($end_parts[1]) ? $end_parts[1] : 'AM';
                                                ?>
                                                <span class="slot-time-start"><?= $start_time_part ?></span>
                                                <span class="slot-time-separator">TO</span>
                                                <span class="slot-time-end"><?= $end_time_part ?></span>
                                                <span class="slot-time-ampm"><?= $end_ampm ?></span>
                                            </div>
                                            <div class="slot-actions-right">
                                                <?php if ($ext_status === 'pending'): ?>
                                                    <span class="badge-ext-pending"
                                                        title="Extension request pending"
                                                        data-bs-toggle="tooltip">
                                                        <i class="bi bi-hourglass-bottom"></i>
                                                    </span>
                                                <?php elseif ($ext_status === 'approved'): ?>
                                                    <span class="badge-ext-approved"
                                                        title="Extension approved"
                                                        data-bs-toggle="tooltip">
                                                        <i class="bi bi-check-circle"></i>
                                                    </span>
                                                <?php elseif ($ext_status === 'rejected'): ?>
                                                    <span class="badge-ext-rejected"
                                                        title="Extension rejected"
                                                        data-bs-toggle="tooltip">
                                                        <i class="bi bi-x-circle"></i>
                                                    </span>
                                                    <button class="extend-icon-btn"
                                                        onclick="requestExtend(<?= $slot['id'] ?>, '<?= $slot['room_name'] ?>', '<?= $start ?>')"
                                                        title="Re-request Extension"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="auto">
                                                        <i class="bi bi-clock-history"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="extend-icon-btn"
                                                        onclick="requestExtend(<?= $slot['id'] ?>, '<?= $slot['room_name'] ?>', '<?= $start ?>')"
                                                        title="Request Extension"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="auto">
                                                        <i class="bi bi-clock-history"></i>
                                                    </button>
                                                    
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="slot-content">
                                            <div class="slot-room">
                                                <i class="bi bi-door-open me-1"></i><?= htmlspecialchars($slot['room_name']) ?>
                                            </div>
                                            <div class="slot-subject d-flex flex-row">
                                                <i class="bi bi-book me-1"></i>
                                                <h5>Math</h5>
                                            </div>
                                        </div>
                                    </div>
                            <?php endforeach;
                            endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>

        <!-- Extend Modal -->
        <div class="notify-modal" id="extend-modal" style="display:none;">
            <div class="modal-box">
                <div id="modal-header">
                    <h5><strong>⏱</strong> Request Extension</h5>
                </div>
                <div id="modal-body">
                    <p id="extend-label"></p>
                    <label>Extend by:</label>
                    <select id="extend-mins" class="form-select mt-1">
                        <option value="15">15 minutes</option>
                        <option value="30" selected>30 minutes</option>
                        <option value="45">45 minutes</option>
                        <option value="60">1 hour</option>
                    </select>
                </div>
                <div id="modal-footer">
                    <button class="medium" onclick="submitExtend()">CONFIRM</button>
                    <button class="medium" type="button" onclick="closeExtendModal()">CANCEL</button>
                </div>
            </div>
        </div>

        <!-- Hidden form for extend submit -->
        <form id="extend-form" method="POST" action="faculty-timetable.php" style="display:none;">
            <input type="hidden" name="schedule_id" id="extend-schedule-id">
            <input type="hidden" name="extend_mins" id="extend-mins-val">
        </form>

        <?php include '../../php/includes/faculty-sidebar.php'; ?>


        <script src="../../script/animations.js"></script>
        <script src="../../script/toggles.js"></script>
        <script src="../../script/tooltip.js"></script>
    </div>

    <script>
        let currentScheduleId = null;

        function requestExtend(scheduleId, room, time) {
            currentScheduleId = scheduleId;
            document.getElementById('extend-label').textContent = `Request extension for ${room} at ${time}?`;
            document.getElementById('extend-modal').style.display = 'flex';
        }

        function closeExtendModal() {
            document.getElementById('extend-modal').style.display = 'none';
        }

        function submitExtend() {
            document.getElementById('extend-schedule-id').value = currentScheduleId;
            document.getElementById('extend-mins-val').value = document.getElementById('extend-mins').value;
            document.getElementById('extend-form').submit();
        }
    </script>
</body>

</html>