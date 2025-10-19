<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

include '../includes/database.php';

$message = '';
$error = '';
$student = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $sex = trim($_POST['sex']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);

    if (empty($last_name) || empty($first_name) || empty($sex) || empty($email)) {
        $error = "Last Name, First Name, Sex, and Email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email already exists for another student
        $sql_check_email = "SELECT id FROM students WHERE email = ? AND id != ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("si", $email, $student_id);
        $stmt_check_email->execute();
        $result_check_email = $stmt_check_email->get_result();

        if ($result_check_email->num_rows > 0) {
            $error = "This email is already in use by another student.";
        } else {
            $sql_update = "UPDATE students SET last_name = ?, first_name = ?, middle_name = ?, sex = ?, email = ?, address = ?, phone = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("sssssssi", $last_name, $first_name, $middle_name, $sex, $email, $address, $phone, $student_id);

            if ($stmt_update->execute()) {
                $section_id_redirect = isset($_GET['section_id']) ? $_GET['section_id'] : '';
                $subject_id_redirect = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';
                header("Location: manage_class.php?section_id={$section_id_redirect}&subject_id={$subject_id_redirect}&message=student_updated");
                exit();
            } else {
                $error = "Error updating student. Please try again.";
            }
        }
    }
}

// Fetch student data for the form
if (isset($_GET['student_id']) || isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'] ?? $_GET['student_id'];
    $sql_fetch_student = "SELECT * FROM students WHERE id = ?";
    $stmt_fetch_student = $conn->prepare($sql_fetch_student);
    $stmt_fetch_student->bind_param("i", $student_id);
    $stmt_fetch_student->execute();
    $result = $stmt_fetch_student->get_result();
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
    } else {
        $error = "Student not found.";
    }
} else {
    header("Location: dashboard.php"); // Redirect if no student ID is provided
    exit();
}

// Get section_id and subject_id from URL to pass back
$section_id_return = isset($_GET['section_id']) ? $_GET['section_id'] : '';
$subject_id_return = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/header.php'; ?>
        <div class="main-content">
            <div class="header">
                <h3>Edit Student Information</h3>
            </div>
            <div class="content-area">
                <div class="form-container">
                    <?php if ($message): ?>
                        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if ($student): ?>
                    <form action="edit_student.php?student_id=<?php echo $student['id']; ?>&section_id=<?php echo $section_id_return; ?>&subject_id=<?php echo $subject_id_return; ?>" method="post">
                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                        <div class="input-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                        </div>
                        <div class="input-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                        </div>
                        <div class="input-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($student['middle_name']); ?>">
                        </div>
                        <div class="input-group">
                            <label for="sex">Sex</label>
                            <select id="sex" name="sex" required>
                                <option value="">Select Sex</option>
                                <option value="M" <?php echo ($student['sex'] == 'M') ? 'selected' : ''; ?>>Male</option>
                                <option value="F" <?php echo ($student['sex'] == 'F') ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                        </div>
                        <div class="input-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($student['address']); ?>">
                        </div>
                        <div class="input-group">
                            <label for="phone">Contact Number</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>">
                        </div>
                        <button type="submit" class="btn">Update Student</button>
                        <a href="manage_class.php?section_id=<?php echo $section_id_return; ?>&subject_id=<?php echo $subject_id_return; ?>" class="btn-cancel">Cancel</a>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>