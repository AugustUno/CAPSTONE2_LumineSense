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
    <link rel="stylesheet" href="../../css/modals.css">
    <link rel="stylesheet" href="../../css/faculty-timetable.css">
    <link rel="stylesheet" href="../../css/faculty-common.css">

    <title>Class Schedule – LumineSense</title>
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
                                                    <button class="btn-icon btn-icon-view"
                                                        title="View Details"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="auto">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                <?php elseif ($ext_status === 'approved'): ?>
                                                    <span class="badge-ext-approved"
                                                        title="Extension approved"
                                                        data-bs-toggle="tooltip">
                                                        <i class="bi bi-check-circle"></i>
                                                    </span>
                                                    <button class="btn-icon btn-icon-view"
                                                        title="View Details"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="auto">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                <?php elseif ($ext_status === 'rejected'): ?>
                                                    <span class="badge-ext-rejected"
                                                        title="Extension rejected"
                                                        data-bs-toggle="tooltip">
                                                        <i class="bi bi-x-circle"></i>
                                                    </span>
                                                    <button class="extend-icon-btn"
                                                        onclick="requestExtend(<?= $slot['id'] ?>, '<?= $slot['room_name'] ?>', '<?= $start ?>', '<?= $end ?>')"
                                                        title="Re-request Extension"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="auto">
                                                        <i class="bi bi-clock-history"></i>
                                                    </button>
                                                    <button class="btn-icon btn-icon-view"
                                                        title="View Details"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="auto">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="extend-icon-btn"
                                                        onclick="requestExtend(<?= $slot['id'] ?>, '<?= $slot['room_name'] ?>', '<?= $start ?>', '<?= $end ?>')"
                                                        title="Request Extension"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="auto">
                                                        <i class="bi bi-clock-history"></i>
                                                    </button>
                                                    <button class="btn-icon btn-icon-view"
                                                        title="View Details"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="auto">
                                                        <i class="bi bi-eye"></i>
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

        <!-- Request Extension Modal -->
        <div class="profile-details-modal modal fade" id="extendModal" tabindex="-1" aria-labelledby="extendModalLabel" aria-hidden="true">
            <div class="d-flex justify-content-center modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title bold" id="extendModalLabel">
                            <i class="bi bi-clock-history me-2"></i>Request Time Extension
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="extend-description">
                            <span class="emphasis">
                                Requesting extension for
                                <span id="extend-room"></span>
                                from <span id="extend-start-time"></span>
                                to <span id="extend-end-time"></span>
                            </span>
                            <br>How many extra minutes do you need?
                        </p>
                        <div class="extend-modal-content d-flex gap-4">
                            <!-- LEFT DIV: Timer -->
                            <div class="extend-left-div">
                                <h2 class="time-elapsed-title">Time Elapsed</h2>
                                <h1 class="timer-display">
                                    <input type="text" class="timer-input" id="timer-hours" value="00" maxlength="2" />:
                                    <input type="text" class="timer-input" id="timer-minutes" value="00" maxlength="2" />:
                                    <input type="text" class="timer-input" id="timer-seconds" value="00" maxlength="2" />
                                </h1>
                                <div class="timer-labels d-flex gap-3 justify-content-center">
                                    <h6 class="timer-label">HOURS</h6>
                                    <h6 class="timer-label">MINUTES</h6>
                                    <h6 class="timer-label">SECONDS</h6>
                                </div>
                                <p class="extend-description mt-3" id="extend-description">
                                    Extending time for Math discussion at <span id="extend-room"></span> for <span id="extend-time-range"></span>
                                </p>
                            </div>

                            <!-- RIGHT DIV: Extend Buttons -->
                            <div class="extend-right-div d-flex flex-column align-items-center gap-3">

                                <h2 class="time-elapsed-title">Extend Time</h2>
                                <p class="extend-description mb-0">Add desired time:</p>

                                <div class="d-flex flex-column gap-2" id="extendPills">
                                    <?php foreach ([15, 30, 45, 60] as $mins): ?>
                                        <button class="btn btn-outline-primary extend-pill" data-mins="<?= $mins ?>">
                                            +<?= $mins ?> min
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex flex-row flex-nowrap justify-content-between gap-2">
                        <button type="button" class="light bold w-100" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="medium w-100" id="submitExtendBtn" disabled>
                            Send Request
                        </button>
                    </div>
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
        // Initialize Bootstrap modal for extend request
        const extendModalEl = document.getElementById('extendModal');
        const extendModal = new bootstrap.Modal(extendModalEl);

        let currentScheduleId = null;
        let currentRoom = '';
        let currentStartTime = '';
        let currentEndTime = '';
        let totalExtensionMinutes = 0;

        // Parse time string (e.g., "1:00 PM") to Date object for today
        function parseTime(timeStr) {
            const now = new Date();
            const [time, ampm] = timeStr.trim().split(' ');
            let [hours, minutes] = time.split(':').map(Number);
            if (ampm === 'PM' && hours !== 12) hours += 12;
            if (ampm === 'AM' && hours === 12) hours = 0;
            now.setHours(hours, minutes, 0, 0);
            return now;
        }

        // Format time to 12-hour format (e.g., "1:00 PM")
        function formatTime(date) {
            let hours = date.getHours();
            const minutes = date.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            if (hours === 0) hours = 12;
            const minStr = minutes.toString().padStart(2, '0');
            return `${hours}:${minStr} ${ampm}`;
        }

        // Calculate elapsed time between start and end
        function calculateElapsedMinutes(startTime, endTime) {
            const start = parseTime(startTime);
            const end = parseTime(endTime);
            const diffMs = end - start;
            return Math.floor(diffMs / 60000);
        }

        // Update timer display from total seconds
        function updateTimerDisplay(totalSeconds) {
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            document.getElementById('timer-hours').value = hours.toString().padStart(2, '0');
            document.getElementById('timer-minutes').value = minutes.toString().padStart(2, '0');
            document.getElementById('timer-seconds').value = seconds.toString().padStart(2, '0');
        }

        // Get total seconds from timer inputs
        function getTotalSecondsFromInputs() {
            const hours = parseInt(document.getElementById('timer-hours').value) || 0;
            const minutes = parseInt(document.getElementById('timer-minutes').value) || 0;
            const seconds = parseInt(document.getElementById('timer-seconds').value) || 0;
            return hours * 3600 + minutes * 60 + seconds;
        }

        // Update the description text with extended time
        function updateDescription() {
            const totalSeconds = getTotalSecondsFromInputs();
            const elapsedMinutes = calculateElapsedMinutes(currentStartTime, currentEndTime);
            const extraMinutes = Math.max(0, Math.floor(totalSeconds / 60) - elapsedMinutes);

            document.getElementById('extend-room').textContent = currentRoom;
            document.getElementById('extend-start-time').textContent = currentStartTime;

            if (currentEndTime) {
                const endDateTime = parseTime(currentEndTime);
                endDateTime.setMinutes(endDateTime.getMinutes() + extraMinutes);
                const newEndTime = formatTime(endDateTime);
                document.getElementById('extend-end-time').textContent = newEndTime;
                document.getElementById('extend-time-range').textContent = `${currentStartTime} - ${newEndTime}`;
            }

            // Disable send button if timer is 00:00:00
            document.getElementById('submitExtendBtn').disabled = totalSeconds === 0;
        }

        // Reset timer to elapsed time based on slot
        function resetTimerToElapsed() {
            if (currentStartTime && currentEndTime) {
                const elapsedMinutes = calculateElapsedMinutes(currentStartTime, currentEndTime);
                totalExtensionMinutes = 0;
                updateTimerDisplay(elapsedMinutes * 60);
                updateDescription();
            } else {
                totalExtensionMinutes = 0;
                updateTimerDisplay(0);
                updateDescription();
            }
        }

        function requestExtend(scheduleId, room, startTime, endTime) {
            currentScheduleId = scheduleId;
            currentRoom = room;
            currentStartTime = startTime;
            currentEndTime = endTime;

            document.getElementById('submitExtendBtn').disabled = true;

            // Reset pills
            document.querySelectorAll('.extend-pill').forEach(btn => {
                btn.classList.remove('active', 'btn-primary');
                btn.classList.add('btn-outline-primary');
            });

            // Reset timer to elapsed time
            resetTimerToElapsed();

            extendModal.show();
        }

        // Handle pill selection - adds minutes to timer
        document.querySelectorAll('.extend-pill').forEach(btn => {
            btn.addEventListener('click', () => {
                const minsToAdd = parseInt(btn.dataset.mins);

                // Read current values directly from the inputs
                let currentHours = parseInt(document.getElementById('timer-hours').value) || 0;
                let currentMinutes = parseInt(document.getElementById('timer-minutes').value) || 0;
                let currentSeconds = parseInt(document.getElementById('timer-seconds').value) || 0;

                // Add to minutes
                currentMinutes += minsToAdd;

                // Cascade overflow upward
                if (currentMinutes >= 60) {
                    currentHours += Math.floor(currentMinutes / 60);
                    currentMinutes = currentMinutes % 60;
                }
                if (currentHours > 99) currentHours = 99;

                // Write back
                document.getElementById('timer-hours').value = currentHours.toString().padStart(2, '0');
                document.getElementById('timer-minutes').value = currentMinutes.toString().padStart(2, '0');
                document.getElementById('timer-seconds').value = currentSeconds.toString().padStart(2, '0');

                // Visual state
                document.querySelectorAll('.extend-pill').forEach(b => {
                    b.classList.remove('active', 'btn-primary');
                    b.classList.add('btn-outline-primary');
                });
                // Visual state - flash active then revert (push button behavior)
                btn.classList.add('active', 'btn-primary');
                btn.classList.remove('btn-outline-primary');

                setTimeout(() => {
                    btn.classList.remove('active', 'btn-primary');
                    btn.classList.add('btn-outline-primary');
                }, 150);

                updateDescription();
                document.getElementById('submitExtendBtn').disabled = false;
            });
        });

        // Handle timer input changes
        document.querySelectorAll('.timer-input').forEach(input => {
            input.addEventListener('focus', (e) => {
                e.target.select();
            });

            input.addEventListener('blur', (e) => {
                let val = parseInt(e.target.value) || 0;

                if (e.target.id === 'timer-hours') {
                    if (val > 99) val = 99;
                    e.target.value = val.toString().padStart(2, '0');
                } else if (e.target.id === 'timer-minutes') {
                    if (val >= 60) {
                        const carryHours = Math.floor(val / 60);
                        const remMinutes = val % 60;
                        const hoursInput = document.getElementById('timer-hours');
                        let currentHours = parseInt(hoursInput.value) || 0;
                        currentHours = Math.min(99, currentHours + carryHours);
                        hoursInput.value = currentHours.toString().padStart(2, '0');
                        val = remMinutes;
                    }
                    e.target.value = val.toString().padStart(2, '0');
                } else if (e.target.id === 'timer-seconds') {
                    if (val >= 60) {
                        const carryMinutes = Math.floor(val / 60);
                        const remSeconds = val % 60;
                        const minutesInput = document.getElementById('timer-minutes');
                        let currentMinutes = parseInt(minutesInput.value) || 0;
                        currentMinutes += carryMinutes;
                        // Seconds carry may itself push minutes over 60, cascade up
                        if (currentMinutes >= 60) {
                            const carryHours = Math.floor(currentMinutes / 60);
                            currentMinutes = currentMinutes % 60;
                            const hoursInput = document.getElementById('timer-hours');
                            let currentHours = parseInt(hoursInput.value) || 0;
                            currentHours = Math.min(99, currentHours + carryHours);
                            hoursInput.value = currentHours.toString().padStart(2, '0');
                        }
                        minutesInput.value = currentMinutes.toString().padStart(2, '0');
                        val = remSeconds;
                    }
                    e.target.value = val.toString().padStart(2, '0');
                }

                updateDescription();
            });

            input.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') e.target.blur();
            });
        });

        // Handle submit button click
        document.getElementById('submitExtendBtn').addEventListener('click', () => {
            const totalSeconds = getTotalSecondsFromInputs();
            const elapsedMinutes = calculateElapsedMinutes(currentStartTime, currentEndTime);
            const extensionMinutes = Math.floor(totalSeconds / 60) - elapsedMinutes;

            if (extensionMinutes > 0) {
                document.getElementById('extend-schedule-id').value = currentScheduleId;
                document.getElementById('extend-mins-val').value = extensionMinutes;
                document.getElementById('extend-form').submit();
            }
        });

        // Close modal on hide
        extendModalEl.addEventListener('hidden.bs.modal', () => {
            currentScheduleId = null;
            currentRoom = '';
            currentStartTime = '';
            currentEndTime = '';
            totalExtensionMinutes = 0;
        });
    </script>
</body>

</html>