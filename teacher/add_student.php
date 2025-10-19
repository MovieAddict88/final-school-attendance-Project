<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

include '../includes/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $sex = trim($_POST['sex']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $section_id = !empty($_POST['section_id']) ? $_POST['section_id'] : null;

    if (empty($last_name) || empty($first_name) || empty($sex) || empty($email)) {
        $error = "Last Name, First Name, Sex, and Email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email already exists
        $sql_check_email = "SELECT id FROM students WHERE email = ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("s", $email);
        $stmt_check_email->execute();
        $result_check_email = $stmt_check_email->get_result();

        if ($result_check_email->num_rows > 0) {
            $error = "A student with this email already exists.";
        } else {
            $password = password_hash('password123', PASSWORD_DEFAULT); // Default password

            $sql_insert = "INSERT INTO students (last_name, first_name, middle_name, sex, email, password, address, phone, section_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ssssssssi", $last_name, $first_name, $middle_name, $sex, $email, $password, $address, $phone, $section_id);

            if ($stmt_insert->execute()) {
                // Get subject_id from the form action URL for the redirect
                $subject_id_redirect = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';
                header("Location: manage_class.php?section_id={$section_id}&subject_id={$subject_id_redirect}&message=student_added");
                exit();
            } else {
                $error = "Error adding student. Please try again.";
            }
        }
    }
}

// Get section_id from URL to pass back if user cancels
$section_id_return = isset($_GET['section_id']) ? $_GET['section_id'] : '';
$subject_id_return = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Student</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/header.php'; ?>
        <div class="main-content">
            <div class="header">
                <h3>Add New Student</h3>
            </div>
            <div class="content-area">
                <div class="form-container">
                    <?php if ($message): ?>
                        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form action="add_student.php?section_id=<?php echo $section_id_return; ?>&subject_id=<?php echo $subject_id_return; ?>" method="post">
                        <div class="input-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                        <div class="input-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        <div class="input-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name">
                        </div>
                        <div class="input-group">
                            <label for="sex">Sex</label>
                            <select id="sex" name="sex" required>
                                <option value="">Select Sex</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="input-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address">
                        </div>
                        <div class="input-group">
                            <label for="phone">Contact Number</label>
                            <input type="text" id="phone" name="phone">
                        </div>
                        <input type="hidden" name="section_id" value="<?php echo htmlspecialchars($section_id_return); ?>">
                        <button type="submit" class="btn">Add Student</button>
                        <a href="manage_class.php?section_id=<?php echo $section_id_return; ?>&subject_id=<?php echo $subject_id_return; ?>" class="btn-cancel">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>