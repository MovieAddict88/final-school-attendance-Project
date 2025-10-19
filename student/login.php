<?php
session_start();
include '../includes/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT id, password FROM students WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['student_id'] = $row['id'];
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