<?php
function log_admin_action(
    mysqli $conn,
    int    $admin_id,
    string $action,
    string $target_name = '',
    string $notes       = ''
): void {
    $stmt = $conn->prepare(
        'INSERT INTO admin_logs (admin_id, action, target_name, notes)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('isss', $admin_id, $action, $target_name, $notes);
    $stmt->execute();
    $stmt->close();
}

// ── Departments ──────────────────────────────────────────────────────────────
$departments = [];
$result = $conn->query("SELECT * FROM departments");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (!isset($row['status'])) {
            $row['status'] = 'active';
        }
        $departments[] = $row;
    }
}


