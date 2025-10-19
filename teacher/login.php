<?php
require_once '../includes/session.php';
require_once '../includes/csrf.php';
include '../includes/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_or_die();
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT id, full_name, password, profile_image FROM teachers WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            session_regenerate();
            $_SESSION['teacher_id'] = $row['id'];
            $_SESSION['teacher_name'] = $row['full_name'];
            $_SESSION['teacher_profile_image'] = $row['profile_image'];
            header("Location: dashboard.php");
            exit();
        } else {
            header("Location: index.php?error=Invalid Credentials");
            exit();
        }
    } else {
        header("Location: index.php?error=Invalid Credentials");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>