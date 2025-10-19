<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../includes/database.php';

// Fetch counts
$sql_students = "SELECT COUNT(id) as total_students FROM students";
$result_students = $conn->query($sql_students);
$total_students = $result_students->fetch_assoc()['total_students'];

$sql_teachers = "SELECT COUNT(id) as total_teachers FROM teachers";
$result_teachers = $conn->query($sql_teachers);
$total_teachers = $result_teachers->fetch_assoc()['total_teachers'];

$sql_parents = "SELECT COUNT(id) as total_parents FROM parents";
$result_parents = $conn->query($sql_parents);
$total_parents = $result_parents->fetch_assoc()['total_parents'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/header.php'; ?>
        <div class="main-content">
            <div class="header">
                <h3>Welcome, <?php echo $_SESSION['admin_username']; ?>!</h3>
            </div>
            <div class="dashboard-cards">
                <div class="card">
                    <h3>Total Students</h3>
                    <p><?php echo $total_students; ?></p>
                </div>
                <div class="card">
                    <h3>Total Teachers</h3>
                    <p><?php echo $total_teachers; ?></p>
                </div>
                <div class="card">
                    <h3>Total Parents</h3>
                    <p><?php echo $total_parents; ?></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>