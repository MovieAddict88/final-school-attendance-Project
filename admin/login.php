<?php
session_start();
include '../includes/database.php';

// Check if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if there are any admins in the database
    $sql_check_admin = "SELECT id FROM admins LIMIT 1";
    $result_check_admin = $conn->query($sql_check_admin);

    if ($result_check_admin->num_rows == 0) {
        // No admins exist, create a default admin
        $default_username = 'admin';
        $default_password = 'password'; // In a real application, use a more secure password
        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

        $sql_insert_admin = "INSERT INTO admins (username, password) VALUES ('$default_username', '$hashed_password')";
        if ($conn->query($sql_insert_admin) === TRUE) {
            // Log in the new admin automatically
            $last_id = $conn->insert_id;
            $_SESSION['admin_id'] = $last_id;
            $_SESSION['admin_username'] = $default_username;
            header("Location: dashboard.php");
            exit();
        } else {
            echo "Error creating default admin: " . $conn->error;
        }
    }

    // Proceed with login if admins exist
    $sql = "SELECT id, username, password FROM admins WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_username'] = $row['username'];
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