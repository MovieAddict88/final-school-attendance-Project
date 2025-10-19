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
    $parent_id = $_POST['id'] ?? null;
    if (!$parent_id) {
        header("Location: manage_parents.php");
        exit();
    }
    $sql = "DELETE FROM parents WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $parent_id);

    if ($stmt->execute()) {
        header("Location: manage_parents.php");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    http_response_code(405);
    echo "Method Not Allowed";
}
?>