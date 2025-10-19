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
    $teacher_id = $_POST['id'] ?? null;
    if (!$teacher_id) {
        header("Location: manage_teachers.php");
        exit();
    }
    $sql = "DELETE FROM teachers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $teacher_id);

    if ($stmt->execute()) {
        header("Location: manage_teachers.php");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    http_response_code(405);
    echo "Method Not Allowed";
}
?>