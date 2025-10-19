<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../includes/database.php';

if (isset($_GET['id'])) {
    $parent_id = $_GET['id'];
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
    header("Location: manage_parents.php");
    exit();
}
?>