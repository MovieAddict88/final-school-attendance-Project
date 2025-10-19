<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}

include '../includes/database.php';

$student_id = $_SESSION['student_id'];

// For demonstration, we'll just show student's own info.
$sql = "
    SELECT s.first_name, s.last_name, s.middle_name, s.email, sec.section_name, g.grade_name
    FROM students s
    LEFT JOIN sections sec ON s.section_id = sec.id
    LEFT JOIN grades g ON sec.grade_id = g.id
    WHERE s.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/header.php'; ?>
        <div class="main-content">
            <div class="header">
                <h3>Welcome, <?php echo htmlspecialchars($student['first_name']); ?>!</h3>
            </div>
            <div class="content-area">
                <h4>Your Information</h4>
                <?php
                    $full_name = htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']);
                    $class_info = $student['grade_name'] && $student['section_name']
                                  ? htmlspecialchars($student['grade_name'] . ' - ' . $student['section_name'])
                                  : 'N/A';
                ?>
                <p><strong>Name:</strong> <?php echo $full_name; ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                <p><strong>Class:</strong> <?php echo $class_info; ?></p>

                <h4 style="margin-top: 20px;">Your Grades</h4>
                <p>Grades functionality coming soon.</p>

                <h4 style="margin-top: 20px;">Your Attendance</h4>
                <p>Attendance functionality coming soon.</p>
            </div>
        </div>
    </div>
</body>
</html>