<?php
require_once '../includes/session.php';
require_once '../includes/csrf.php';
if (!isset($_SESSION['teacher_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'User not authenticated']);
    exit();
}

include '../includes/database.php';

header('Content-Type: application/json');

// Verify CSRF token via header
verify_csrf_or_die();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit();
}

$student_id = $data['student_id'] ?? null;
$subject_id = $data['subject_id'] ?? null;
$class_date = $data['class_date'] ?? null;
$status = $data['status'] ?? ''; // Default to empty string
$teacher_id = $_SESSION['teacher_id'];

if (!$student_id || !$subject_id || !$class_date) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

if (!in_array($status, ['present', 'absent', ''])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status value']);
    exit();
}

// If status is an empty string, it means we want to clear the attendance.
// With the new schema, we can represent this as NULL.
$final_status = $status === '' ? null : $status;

// Use a single query to handle insert, update, and "clearing" (by setting status to NULL)
$sql = "
    INSERT INTO attendance (student_id, subject_id, teacher_id, class_date, status)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE status = VALUES(status)
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit();
}
// Note: Binding a NULL value with type 's' is handled correctly by mysqli
$stmt->bind_param("iiiss", $student_id, $subject_id, $teacher_id, $class_date, $final_status);

if ($stmt->execute()) {
    // After a successful update, fetch the new monthly totals for the student
    $month = $data['month'] ?? date('m');
    $year = $data['year'] ?? date('Y');

    // Calculate start and end dates of the month
    $start_date = new DateTime("$year-$month-01");
    $end_date = clone $start_date;
    $end_date->modify('last day of this month');

    $start_date_str = $start_date->format('Y-m-d');
    $end_date_str = $end_date->format('Y-m-d');

    // Calculate the number of school days (weekdays) in the month
    $total_school_days_in_month = 0;
    $current_date = clone $start_date;
    while ($current_date <= $end_date) {
        if ((int)$current_date->format('N') < 6) { // 1 (Mon) to 5 (Fri)
            $total_school_days_in_month++;
        }
        $current_date->modify('+1 day');
    }

    // Fetch the summary for the entire month
    $sql_monthly_summary = "
        SELECT
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as total_present,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as total_absent
        FROM attendance
        WHERE student_id = ? AND subject_id = ? AND class_date BETWEEN ? AND ?
    ";
    $stmt_summary = $conn->prepare($sql_monthly_summary);
    $stmt_summary->bind_param("iiss", $student_id, $subject_id, $start_date_str, $end_date_str);
    $stmt_summary->execute();
    $result_summary = $stmt_summary->get_result();
    $summary = $result_summary->fetch_assoc();

    $total_present = (int)($summary['total_present'] ?? 0);
    $total_absent = (int)($summary['total_absent'] ?? 0);
    $remarks = '';

    if ($total_school_days_in_month > 0) {
        $percentage = ($total_present / $total_school_days_in_month) * 100;
        $remarks = number_format($percentage, 2) . '%';
    }

    echo json_encode([
        'success' => true,
        'totals' => [
            'total_present' => $total_present,
            'total_absent' => $total_absent,
            'remarks' => $remarks
        ]
    ]);

} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update attendance: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>