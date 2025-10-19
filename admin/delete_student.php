<?php
require_once '../includes/session.php';
require_once '../includes/csrf.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../includes/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
    $student_id = $_POST['id'] ?? null;
    if (!$student_id) {
        header("Location: manage_students.php");
        exit();
    }
    $sql = "DELETE FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);

    if ($stmt->execute()) {
        header("Location: manage_students.php");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    http_response_code(405);
    echo "Method Not Allowed";
}
?>