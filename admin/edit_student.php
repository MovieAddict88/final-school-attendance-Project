<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../includes/database.php';

$student_id = $_GET['id'];
$error = '';

// Fetch sections for the dropdown
$sql_sections = "SELECT sections.id, sections.section_name, grades.grade_name FROM sections JOIN grades ON sections.grade_id = grades.id ORDER BY grades.grade_name, sections.section_name";
$result_sections = $conn->query($sql_sections);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $sex = trim($_POST['sex']);
    $email = trim($_POST['email']);
    $section_id = $_POST['section_id'];

    if (empty($last_name) || empty($first_name) || empty($sex) || empty($email) || empty($section_id)) {
        $error = "All fields except Middle Name are required.";
    } else {
        $sql = "UPDATE students SET last_name = ?, first_name = ?, middle_name = ?, sex = ?, email = ?, section_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssii", $last_name, $first_name, $middle_name, $sex, $email, $section_id, $student_id);

        if ($stmt->execute()) {
            header("Location: manage_students.php");
            exit();
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
    // To retain entered data on error
    $student = $_POST;
    $student['id'] = $student_id;

} else {
    $sql = "SELECT * FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    if (!$student) {
        // Redirect or show error if student not found
        header("Location: manage_students.php");
        exit();
    }
}
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
                <h3>Edit Student</h3>
            </div>
            <div class="form-container">
                <?php if(!empty($error)): ?>
                    <p class="message error"><?php echo $error; ?></p>
                <?php endif; ?>
                <form action="edit_student.php?id=<?php echo $student_id; ?>" method="post">
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
                        <label for="section_id">Class/Section</label>
                        <select id="section_id" name="section_id" required>
                            <option value="">Select Class</option>
                            <?php 
                            // Reset pointer for sections result set
                            $result_sections->data_seek(0);
                            if ($result_sections->num_rows > 0): ?>
                                <?php while($row = $result_sections->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo ($student['section_id'] == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($row['grade_name'] . ' - ' . $row['section_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn">Update Student</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>