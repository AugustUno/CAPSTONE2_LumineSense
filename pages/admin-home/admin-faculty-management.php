<?php
$page_title = "Faculty Management";
require_once '../../php/includes/admin-head.php';

/** @var string $initials */
/** @var string $admin_name */
/** @var string $admin_email */
/** @var int $admin_id */

$phpRoot = realpath(__DIR__ . '/../../php');
require_once $phpRoot . '/handlers/faculty-approvals-handler.php';
require_once $phpRoot . '/handlers/admin-handlers.php';

/** @var string $message */
/** @var int $total_faculty */
/** @var int $pending_count */
/** @var int $ext_pending */
/** @var array $faculty_list */
/** @var array $extensions */
/** @var array $departments */

require_once '../../php/handlers/admin-handlers.php';
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Faculty Management & Approvals</title>

    <!--External links-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!--Relative links-->
    <link rel="icon" href="../../images/logo.png">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/containers.css">
    <link rel="stylesheet" href="../../css/modals.css">
    <link rel="stylesheet" href="../../css/admin-common.css">
    <link rel="stylesheet" href="../../css/admin-faculty-management.css">
    <link rel="stylesheet" href="../../css/tooltip.css">
</head>

<body class="contrast-bg">
    <?php include '../../php/includes/admin-topbar.php'; ?>
    <?php include '../../php/includes/admin-sidebar.php'; ?>

    <?php if (!empty($message)): ?>
        <div class="toast-wrap">
            <div class="toast-msg show" id="toastMsg"><?= htmlspecialchars($message) ?></div>
        </div>
    <?php else: ?>
        <div class="toast-wrap">
            <div class="toast-msg" id="toastMsg"></div>
        </div>
    <?php endif; ?>

    <div class="parent-container">

        <div class="child-container">
            <div class="main-container faculty-management gap-5">

                <div class="group-container">
                    <!-- Stats cards -->
                    <div style="background-color:#f8f9fa;" class="section-container py-4">
                        <div class="stat-row gap-3">
                            <div class="stat-card">
                                <span class="stat-icon"><i class="bi bi-diagram-3" style="font-size:2rem;color:var(--secondary-color-2);"></i></span>
                                <div>
                                    <div class="stat-value"><?= $total_rooms ?></div>
                                    <p class="stat-label">Total<br>Departments</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <span class="stat-icon"><i class="bi bi-person-check" style="font-size:2rem;color:var(--secondary-color-2);"></i></span>
                                <div>
                                    <div class="stat-value"><?= $pending ?></div>
                                    <p class="stat-label">Faculty Pending<br>Approval</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <span class="stat-icon"><i class="bi bi-clock-history" style="font-size:2rem;color:var(--secondary-color-2);"></i></span>
                                <div>
                                    <div class="stat-value"><?= $ext_pending ?></div>
                                    <p class="stat-label">Extension<br>Requests</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--Departments-->
                    <div style="background-color:#f8f9fa;" class="section-container system-status gap-3">
                        <div class="section-topbar d-flex my-auto gap-1 align-items-center justify-content-between">
                            <div class="d-flex mx-2 align-items-start">
                                <h2 class="bold"><i class="bi bi-diagram-3 me-2"></i>Departments</h2>
                            </div>
                            <div class="d-flex mx-2 align-items-end">
                                <button class="medium px-2 flex-grow-1"
                                    onclick="openAddDepartmentModal()"><i class="bi bi-plus-lg"></i>Add Department</button>
                            </div>
                        </div>
                        <div class="departments-scroll-container gap-2" style="max-height: 100vh; overflow-y: auto;">

                            <?php if (!empty($departments)): foreach ($departments as $dept): ?>
                            <div class="department-card">
                                <div class="department-card-accent <?= $dept['status'] === 'active' ? 'department-badge-active' : 'department-badge-pending' ?>"></div>
                                <div class="department-card-body">
                                    <div class="department-card-header">
                                        <div>
                                            <div class="department-card-name d-flex align-items-center justify-content-between">
                                                <?= htmlspecialchars($dept['name']) ?>
                                                <span class="department-status-badge <?= $dept['status'] === 'active' ? 'department-badge-active' : 'department-badge-pending' ?> bold mx-2">
                                                    <?= ucfirst(htmlspecialchars($dept['status'])) ?>
                                                </span>
                                            </div>
                                            <div class="department-card-section"><?= htmlspecialchars($dept['description']) ?></div>
                                        </div>
                                        <div class="d-flex align-items-center department-icons gap-1">
                                            <button class="btn-icon btn-icon-view d-inline-flex align-items-center justify-content-center"
                                                onclick="openViewDepartmentModal(<?= $dept['id'] ?>, '<?= addslashes($dept['name']) ?>', '<?= addslashes($dept['description']) ?>', <?= $dept['head_faculty_id'] ?? 'null' ?>)"
                                                title="View Department"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="auto">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn-icon btn-icon-edit"
                                                title="Edit Department"
                                                onclick="openEditDepartmentModal(<?= $dept['id'] ?>, '<?= addslashes($dept['name']) ?>', '<?= addslashes($dept['description']) ?>', <?= $dept['head_faculty_id'] ?? 'null' ?>)"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="auto">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn-icon btn-icon-del"
                                                title="Delete Department"
                                                onclick="openDeleteDepartmentModal(<?= $dept['id'] ?>, '<?= addslashes($dept['name']) ?>')"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="auto">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <hr class="department-card-divider">
                                    <div class="department-info-row">
                                        <i class="bi bi-person-badge"></i>
                                        <span class="department-info-label">Head:</span>
                                        <span class="department-info-val bold">
                                            <?php
                                            $head_name = 'None assigned';
                                            if (!empty($dept['head_faculty_id']) && !empty($faculty_list)) {
                                                foreach ($faculty_list as $f) {
                                                    if ($f['id'] == $dept['head_faculty_id']) {
                                                        $head_name = htmlspecialchars($f['first_name'] . ' ' . $f['last_name']);
                                                        break;
                                                    }
                                                }
                                            }
                                            echo $head_name;
                                            ?>
                                        </span>
                                    </div>
                                    <div class="department-info-row">
                                        <i class="bi bi-people"></i>
                                        <span class="department-info-label">Number of faculty:</span>
                                        <span class="department-info-val bold"><?= isset($dept['faculty_count']) ? $dept['faculty_count'] : 'â€”' ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; else: ?>
                            <p class="text-muted text-center py-4">No departments found.</p>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>


                <div class="group-container gap-3">
                    <!-- Faculty Directory -->
                    <div class="faculty-directory card border-0 shadow-sm p-4 bg-white w-100">
                        <div class="faculty-directory-container d-flex flex-column justify-content-center align-items-center p-3 mb-3">
                            <h2 class="bold mb-0"><i class="bi bi-people mb-3"></i> Faculty Directory</h2>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="light medium gap-2" style="font-size: 12px;" onclick="filterList('all')"><i class="bi bi-border-all"></i> All Records</button>
                                <button type="button" class="light gap-2" style="font-size: 12px;" onclick="filterList('approved')"><i class="bi bi-check-circle"></i> Approved</button>
                                <button type="button" class="light gap-2" style="font-size: 12px;" onclick="filterList('unverified')"><i class="bi bi-x-circle"></i> Unverified</button>
                            </div>
                        </div>
                        <div class="style-scrollbar" style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($faculty_list)): ?>
                                <p class="text-muted text-center py-4">No records found inside the active index.</p>
                                <?php else: foreach ($faculty_list as $faculty): ?>
                                    <div class="faculty-list-item d-flex align-items-start justify-content-between p-3 mb-2 border rounded" data-status="<?= $faculty['status_label'] ?>">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar bg-light rounded-circle d-flex align-items-center justify-content-center text-secondary bold">
                                                <?= strtoupper(substr($faculty['first_name'], 0, 1) . substr($faculty['last_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <h5 class="bold mb-0"><?= htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name']) ?></h5>
                                                <span class="text-muted small" style="font-size: 11px;"><?= htmlspecialchars($faculty['email']) ?></span>
                                            </div>
                                            <?php if ($faculty['status_label'] === 'approved'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa-solid fa-circle-check"></i></span>
                                            <?php elseif ($faculty['status_label'] === 'pending'): ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1"><i class="fa-solid fa-clock"></i></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1"><i class="fa-solid fa-envelope"></i></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <div>
                                                <button type="button"
                                                    class="btn-icon btn-icon-view d-inline-flex align-items-center"
                                                    onclick="window.location.href='admin-faculty-card.php?id=<?= $faculty['id'] ?>'"
                                                    title="View Profile"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="auto">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                            <?php if ($faculty['status_label'] === 'approved'): ?>
                                                <form method="POST" class="mb-0">
                                                    <input type="hidden" name="faculty_id" value="<?= $faculty['id'] ?>"><input type="hidden" name="action" value="revoke">
                                                    <button type="submit"
                                                        class="btn-icon btn-icon-revoke"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="auto"
                                                        title="Revoke Access">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <!-- Delete button now opens modal instead of submitting directly -->
                                            <button type="button"
                                                class="btn-icon btn-icon-del"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="auto"
                                                title="Delete Faculty"
                                                onclick="openDeleteFacultyModal(<?= $faculty['id'] ?>, '<?= addslashes($faculty['first_name'] . ' ' . $faculty['last_name']) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                            <?php endforeach;
                            endif; ?>
                        </div>
                    </div>

                    <!--Registration Approvals Pending-->
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm p-4 h-100" style="background-color: var(--secondary-color-1);">
                                <div class="section-topbar d-flex my-auto gap-1 align-items-center justify-content-between p-3 mb-3" style="background: var(--primary-color) !important;
                                border-radius: 8px !important;">
                                    <div class="d-flex flex-column mx-2 align-items-start">
                                        <h2 class="bold" style="font-size:24.5px;"><i class="fa-solid fa-user-clock me-2"></i>Pending Approvals</h2>
                                        <p class="subtitle">Pending registration approvals are displayed here.</p>
                                    </div>
                                </div>
                                <div class="style-scrollbar" style="max-height: 300px; overflow-y: auto;">
                                    <?php
                                    $has_pending = false;
                                    foreach ($faculty_list as $faculty):
                                        if ($faculty['status_label'] === 'pending'):
                                            $has_pending = true;
                                    ?>
                                            <div class="d-flex align-items-center justify-content-between p-3 mb-2 border border-warning-subtle rounded bg-warning-subtle bg-opacity-10">
                                                <div>
                                                    <h5 class="bold mb-0"><?= htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name']) ?></h5>
                                                    <span class="text-muted small" style="font-size: 11px;"><?= htmlspecialchars($faculty['email']) ?></span>
                                                </div>

                                                <button type="button"
                                                    class="btn-icon btn-icon-view d-inline-flex align-items-center"
                                                    onclick="window.location.href='admin-faculty-review.php?id=<?= $faculty['id'] ?>'"
                                                    title="Review Access Request"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="auto">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        <?php endif;
                                    endforeach;
                                    if (!$has_pending):
                                        ?>
                                        <p class="text-center py-4 small" style="color: #fff;">No pending registrations require attention right now.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- Schedule Extension Requests -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm p-4 h-100" style="background-color: var(--secondary-color-1);">
                                <div class="section-topbar d-flex my-auto gap-1 align-items-center justify-content-between p-3 mb-3" style="background: var(--primary-color) !important;
                                border-radius: 8px !important;">
                                    <div class="d-flex flex-column mx-2 align-items-start">
                                        <h5 class="bold" style="font-size:24.5px;"><i class="bi bi-clock-history me-2"></i>Pending Extensions</h5>
                                        <p class="subtitle">Pending schedule extensions are displayed here.</p>
                                    </div>
                                </div>
                                <div class="style-scrollbar" style="max-height: 300px; overflow-y: auto;">
                                    <?php
                                    $has_ext = false;
                                    foreach ($extensions as $ext):
                                        if ($ext['status'] === 'pending'):
                                            $has_ext = true;
                                    ?>
                                            <div class="p-3 border rounded mb-2 bg-light">
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <h6 class="bold mb-0 text-dark"><?= htmlspecialchars($ext['faculty_name']) ?></h6>
                                                    <span class="badge bg-info text-dark">+<?= $ext['extend_mins'] ?> mins</span>
                                                </div>
                                                <p class="text-secondary small mb-2">
                                                    <?= $ext['room_name'] ?> Â· <?= $ext['day_of_week'] ?> Â·
                                                    <?= date('g:i A', strtotime($ext['start_time'])) ?> â€“
                                                    <?= date('g:i A', strtotime($ext['end_time'])) ?>
                                                </p>
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <form method="POST" class="mb-0">
                                                        <input type="hidden" name="extension_id" value="<?= $ext['id'] ?>"><input type="hidden" name="action" value="ext_reject">
                                                        <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-2">Deny</button>
                                                    </form>
                                                    <form method="POST" class="mb-0">
                                                        <input type="hidden" name="extension_id" value="<?= $ext['id'] ?>"><input type="hidden" name="action" value="ext_approve">
                                                        <button type="submit" class="btn btn-xs btn-primary py-1 px-2">Grant</button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php
                                        endif;
                                    endforeach;
                                    if (!$has_ext):
                                        ?>
                                        <p class=" text-center py-4 small" style="color: #fff;">No schedule extensions are currently requested.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <?php include '../../php/includes/profile-offcanvas.php'; ?>

    <!-- â•â•â• DELETE FACULTY MODAL â•â•â• -->
    <div class="modal fade" id="deleteFacultyModal" tabindex="-1" aria-hidden="true">
        <div class="room-details-modal modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header modal-header-warning">
                    <h5 class="modal-title">Delete Faculty</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <i class="bi bi-trash" style="font-size:2.5rem;color:#c0392b;"></i>
                    <p class="mt-3 mb-0" style="font-size:15px;">
                        Are you sure you want to delete <strong id="deleteFacultyName"></strong>?
                        This will also remove all schedules and logs for this faculty.
                    </p>
                </div>
                <form method="POST" action="../../php/handlers/faculty-approvals-handler.php">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="faculty_id" id="deleteFacultyId">
                    <div class="modal-footer d-flex flex-nowrap flex-row justify-content-between gap-2">
                        <button type="button" class="light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="medium" style="background:#c0392b;">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- â•â•â• DELETE DEPARTMENT MODAL â•â•â• -->
    <div class="modal fade" id="deleteDepartmentModal" tabindex="-1" aria-hidden="true">
        <div class="room-details-modal modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header modal-header-warning">
                    <h5 class="modal-title">Delete Department</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <i class="bi bi-diagram-3" style="font-size:2.5rem;color:#c0392b;"></i>
                    <p class="mt-3 mb-0" style="font-size:15px;">
                        Are you sure you want to delete the <strong id="deleteDepartmentName"></strong> department?
                        This action cannot be undone and will unlink all associated faculty members.
                    </p>
                </div>
                <form method="POST" action="../../php/handlers/admin-handlers.php">
                    <input type="hidden" name="action" value="delete_department">
                    <input type="hidden" name="department_id" id="deleteDepartmentId">
                    <div class="modal-footer d-flex flex-nowrap flex-row justify-content-between gap-2">
                        <button type="button" class="light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="medium" style="background:#c0392b;">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- â•â•â• ADD DEPARTMENT MODAL â•â•â• -->
    <div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-hidden="true">
        <div class="room-details-modal modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-primary">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Department</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="../../php/handlers/admin-handlers.php">
                    <input type="hidden" name="action" value="add_department">
                    <div class="modal-body p-4">
                        <!-- Name Field -->
                        <div class="mb-3">
                            <label class="form-label bold">Name</label>
                            <input type="text" class="form-control" name="dept_name" placeholder="Enter department name" required>
                        </div>

                        <!-- Description Field -->
                        <div class="mb-3">
                            <label class="form-label bold">Description</label>
                            <input type="text" class="form-control" name="dept_description" placeholder="Enter department description">
                        </div>

                        <!-- Head of Department Section -->
                        <div class="mb-3">
                            <label class="form-label bold">Head of Department <span class="text-muted fw-normal" style="font-size:12px;">(Optional)</span></label>
                            <input type="text" class="form-control mb-2" placeholder="Search faculty members..." oninput="filterFacultySearch(this, 'addHodList')">
                            <div id="addHodList" class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                                <?php if (!empty($faculty_list)): foreach ($faculty_list as $f): if ($f['status_label'] === 'approved'): ?>
                                <div class="form-check py-1 faculty-search-item">
                                    <input class="form-check-input" type="radio" name="head_faculty_id" id="addHod_<?= $f['id'] ?>" value="<?= $f['id'] ?>">
                                    <label class="form-check-label" for="addHod_<?= $f['id'] ?>">
                                        <?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name']) ?>
                                        <span class="text-muted small ms-1">(<?= htmlspecialchars($f['email']) ?>)</span>
                                    </label>
                                </div>
                                <?php endif; endforeach; else: ?>
                                <p class="text-muted small mb-0 p-2">No approved faculty members available.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Faculty Members Section -->
                        <div class="mb-3">
                            <label class="form-label bold">Faculty Members <span class="text-muted fw-normal" style="font-size:12px;">(Optional)</span></label>
                            <input type="text" class="form-control mb-2" placeholder="Search faculty members..." oninput="filterFacultySearch(this, 'addMembersList')">
                            <div id="addMembersList" class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                                <?php if (!empty($faculty_list)): foreach ($faculty_list as $f): if ($f['status_label'] === 'approved'): ?>
                                <div class="form-check py-1 faculty-search-item">
                                    <input class="form-check-input" type="checkbox" name="faculty_members[]" id="addMember_<?= $f['id'] ?>" value="<?= $f['id'] ?>">
                                    <label class="form-check-label" for="addMember_<?= $f['id'] ?>">
                                        <?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name']) ?>
                                        <span class="text-muted small ms-1">(<?= htmlspecialchars($f['email']) ?>)</span>
                                    </label>
                                </div>
                                <?php endif; endforeach; else: ?>
                                <p class="text-muted small mb-0 p-2">No approved faculty members available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex flex-nowrap flex-row justify-content-between gap-2">
                        <button type="button" class="light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="medium">Add Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- â•â•â• EDIT DEPARTMENT MODAL â•â•â• -->
    <div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-hidden="true">
        <div class="room-details-modal modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-primary">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Department</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="../../php/handlers/admin-handlers.php">
                    <input type="hidden" name="action" value="edit_department">
                    <input type="hidden" name="department_id" id="editDeptId">
                    <div class="modal-body p-4">
                        <!-- Name Field -->
                        <div class="mb-3">
                            <label class="form-label bold">Name</label>
                            <input type="text" class="form-control" name="dept_name" id="editDeptName" placeholder="Enter department name" required>
                        </div>

                        <!-- Description Field -->
                        <div class="mb-3">
                            <label class="form-label bold">Description</label>
                            <input type="text" class="form-control" name="dept_description" id="editDeptDescription" placeholder="Enter department description">
                        </div>

                        <!-- Head of Department Section -->
                        <div class="mb-3">
                            <label class="form-label bold">Head of Department <span class="text-muted fw-normal" style="font-size:12px;">(Optional)</span></label>
                            <input type="text" class="form-control mb-2" placeholder="Search faculty members..." oninput="filterFacultySearch(this, 'editHodList')">
                            <div id="editHodList" class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                                <?php if (!empty($faculty_list)): foreach ($faculty_list as $f): if ($f['status_label'] === 'approved'): ?>
                                <div class="form-check py-1 faculty-search-item" data-name="<?= strtolower(htmlspecialchars($f['first_name'] . ' ' . $f['last_name'])) ?>">
                                    <input class="form-check-input edit-hod-radio" type="radio" name="head_faculty_id" id="editHod_<?= $f['id'] ?>" value="<?= $f['id'] ?>" data-faculty-id="<?= $f['id'] ?>">
                                    <label class="form-check-label" for="editHod_<?= $f['id'] ?>">
                                        <?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name']) ?>
                                        <span class="text-muted small ms-1">(<?= htmlspecialchars($f['email']) ?>)</span>
                                    </label>
                                </div>
                                <?php endif; endforeach; else: ?>
                                <p class="text-muted small mb-0 p-2">No approved faculty members available.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Faculty Members Section -->
                        <div class="mb-3">
                            <label class="form-label bold">Faculty Members <span class="text-muted fw-normal" style="font-size:12px;">(Optional)</span></label>
                            <input type="text" class="form-control mb-2" placeholder="Search faculty members..." oninput="filterFacultySearch(this, 'editMembersList')">
                            <div id="editMembersList" class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                                <?php if (!empty($faculty_list)): foreach ($faculty_list as $f): if ($f['status_label'] === 'approved'): ?>
                                <div class="form-check py-1 faculty-search-item" data-name="<?= strtolower(htmlspecialchars($f['first_name'] . ' ' . $f['last_name'])) ?>">
                                    <input class="form-check-input edit-member-checkbox" type="checkbox" name="faculty_members[]" id="editMember_<?= $f['id'] ?>" value="<?= $f['id'] ?>" data-faculty-id="<?= $f['id'] ?>">
                                    <label class="form-check-label" for="editMember_<?= $f['id'] ?>">
                                        <?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name']) ?>
                                        <span class="text-muted small ms-1">(<?= htmlspecialchars($f['email']) ?>)</span>
                                    </label>
                                </div>
                                <?php endif; endforeach; else: ?>
                                <p class="text-muted small mb-0 p-2">No approved faculty members available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex flex-nowrap flex-row justify-content-between gap-2">
                        <button type="button" class="light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="medium">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- â•â•â• VIEW DEPARTMENT MODAL â•â•â• -->
    <div class="modal fade" id="viewDepartmentModal" tabindex="-1" aria-hidden="true">
        <div class="room-details-modal modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-primary">
                    <h5 class="modal-title"><i class="bi bi-diagram-3 me-2"></i><span id="viewDeptTitle">Department</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Description -->
                    <div class="mb-4">
                        <label class="form-label bold text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Description</label>
                        <p id="viewDeptDescription" class="mb-0" style="font-size:15px;"></p>
                    </div>

                    <hr>

                    <!-- Head of Department -->
                    <div class="mb-4">
                        <label class="form-label bold"><i class="bi bi-person-badge me-1"></i>Head of Department</label>
                        <div id="viewDeptHead" class="border rounded p-3 bg-light">
                            <p class="text-muted mb-0 small">No head assigned.</p>
                        </div>
                    </div>

                    <!-- Faculty Members -->
                    <div class="mb-2">
                        <label class="form-label bold"><i class="bi bi-people me-1"></i>Faculty Members</label>
                        <div id="viewDeptMembers" class="border rounded p-3 bg-light" style="max-height:200px; overflow-y:auto;">
                            <p class="text-muted mb-0 small">No faculty members assigned.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../script/animations.js"></script>
    <script src="../../script/toggles.js"></script>
    <script src="../../script/tooltip.js"></script>

    <!-- Faculty data for JS usage -->
    <script>
        const allFaculty = <?= json_encode(array_map(function($f) {
            return [
                'id' => $f['id'],
                'name' => $f['first_name'] . ' ' . $f['last_name'],
                'email' => $f['email'],
                'status' => $f['status_label']
            ];
        }, array_filter($faculty_list, function($f) { return $f['status_label'] === 'approved'; }))) ?>;

        // Department members map (dept_id => [faculty_ids]) â€” populate from your DB query in handler
        const deptMembers = <?= isset($dept_members_map) ? json_encode($dept_members_map) : '{}' ?>;
    </script>

    <script>
        // --- Toast ---
        document.addEventListener("DOMContentLoaded", function() {
            const toast = document.getElementById('toastMsg');
            if (toast && toast.classList.contains('show')) {
                setTimeout(() => toast.classList.remove('show'), 3500);
            }
        });

        // --- Faculty List Filter ---
        function filterList(status) {
            const buttons = document.querySelectorAll('.btn-group button');
            buttons.forEach(btn => btn.classList.remove('medium'));
            event.currentTarget.classList.add('medium');

            document.querySelectorAll('.faculty-list-item').forEach(item => {
                if (status === 'all' || item.dataset.status === status) {
                    item.style.setProperty('display', 'flex', 'important');
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });
        }

        // --- Search filter for faculty lists inside modals ---
        function filterFacultySearch(inputEl, listId) {
            const query = inputEl.value.toLowerCase().trim();
            const list = document.getElementById(listId);
            if (!list) return;
            list.querySelectorAll('.faculty-search-item').forEach(item => {
                const label = item.querySelector('label');
                const name = label ? label.textContent.toLowerCase() : '';
                item.style.display = (!query || name.includes(query)) ? '' : 'none';
            });
        }

        // --- DELETE FACULTY MODAL ---
        function openDeleteFacultyModal(id, name) {
            document.getElementById('deleteFacultyId').value = id;
            document.getElementById('deleteFacultyName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteFacultyModal')).show();
        }

        // --- DELETE DEPARTMENT MODAL ---
        function openDeleteDepartmentModal(id, name) {
            document.getElementById('deleteDepartmentId').value = id;
            document.getElementById('deleteDepartmentName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteDepartmentModal')).show();
        }

        // --- ADD DEPARTMENT MODAL ---
        function openAddDepartmentModal() {
            // Reset the form
            document.getElementById('addDepartmentModal').querySelectorAll('input[type=radio], input[type=checkbox]').forEach(el => el.checked = false);
            document.getElementById('addDepartmentModal').querySelectorAll('input[type=text]').forEach(el => el.value = '');
            // Show all search items
            document.querySelectorAll('#addHodList .faculty-search-item, #addMembersList .faculty-search-item').forEach(el => el.style.display = '');
            new bootstrap.Modal(document.getElementById('addDepartmentModal')).show();
        }

        // --- EDIT DEPARTMENT MODAL ---
        function openEditDepartmentModal(id, name, description, headFacultyId) {
            // Populate fields
            document.getElementById('editDeptId').value = id;
            document.getElementById('editDeptName').value = name;
            document.getElementById('editDeptDescription').value = description || '';

            // Reset all radios & checkboxes first, reset search inputs
            document.querySelectorAll('.edit-hod-radio').forEach(r => r.checked = false);
            document.querySelectorAll('.edit-member-checkbox').forEach(c => c.checked = false);
            document.getElementById('editDepartmentModal').querySelectorAll('input[type=text]:not(#editDeptName):not(#editDeptDescription)').forEach(el => el.value = '');
            // Show all items
            document.querySelectorAll('#editHodList .faculty-search-item, #editMembersList .faculty-search-item').forEach(el => el.style.display = '');

            // Pre-select Head of Department
            if (headFacultyId) {
                const hodRadio = document.getElementById('editHod_' + headFacultyId);
                if (hodRadio) hodRadio.checked = true;
            }

            // Pre-select Faculty Members from map
            const members = deptMembers[id] || [];
            members.forEach(fid => {
                const cb = document.getElementById('editMember_' + fid);
                if (cb) cb.checked = true;
            });

            new bootstrap.Modal(document.getElementById('editDepartmentModal')).show();
        }

        // --- VIEW DEPARTMENT MODAL ---
        function openViewDepartmentModal(id, name, description, headFacultyId) {
            document.getElementById('viewDeptTitle').textContent = name;
            document.getElementById('viewDeptDescription').textContent = description || 'No description provided.';

            // Render Head
            const headContainer = document.getElementById('viewDeptHead');
            if (headFacultyId) {
                const head = allFaculty.find(f => f.id == headFacultyId);
                if (head) {
                    headContainer.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar bg-white rounded-circle d-flex align-items-center justify-content-center text-secondary bold border" style="min-width:36px;width:36px;height:36px;font-size:13px;">
                                ${head.name.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase()}
                            </div>
                            <div>
                                <div class="bold mb-0" style="font-size:14px;">${head.name}</div>
                                <div class="text-muted" style="font-size:11px;">${head.email}</div>
                            </div>
                        </div>`;
                } else {
                    headContainer.innerHTML = '<p class="text-muted mb-0 small">Faculty record not found.</p>';
                }
            } else {
                headContainer.innerHTML = '<p class="text-muted mb-0 small">No head assigned.</p>';
            }

            // Render Faculty Members
            const membersContainer = document.getElementById('viewDeptMembers');
            const members = deptMembers[id] || [];
            if (members.length > 0) {
                const memberFaculty = allFaculty.filter(f => members.includes(f.id) || members.map(Number).includes(Number(f.id)));
                if (memberFaculty.length > 0) {
                    membersContainer.innerHTML = memberFaculty.map(f => `
                        <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                            <div class="avatar bg-white rounded-circle d-flex align-items-center justify-content-center text-secondary bold border" style="min-width:36px;width:36px;height:36px;font-size:13px;">
                                ${f.name.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase()}
                            </div>
                            <div>
                                <div class="bold mb-0" style="font-size:14px;">${f.name}</div>
                                <div class="text-muted" style="font-size:11px;">${f.email}</div>
                            </div>
                        </div>`).join('');
                } else {
                    membersContainer.innerHTML = '<p class="text-muted mb-0 small">No faculty members assigned.</p>';
                }
            } else {
                membersContainer.innerHTML = '<p class="text-muted mb-0 small">No faculty members assigned.</p>';
            }

            new bootstrap.Modal(document.getElementById('viewDepartmentModal')).show();
        }
    </script>
</body>

</html>
