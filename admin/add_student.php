<?php
require_once '../includes/session.php';
require_once '../includes/csrf.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../includes/database.php';

// Fetch sections for the dropdown
$sql_sections = "SELECT sections.id, sections.section_name, grades.grade_name FROM sections JOIN grades ON sections.grade_id = grades.id ORDER BY grades.grade_name, sections.section_name";
$result_sections = $conn->query($sql_sections);

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_or_die();
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $sex = trim($_POST['sex']);
    $email = trim($_POST['email']);
    $section_id = $_POST['section_id'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if (empty($last_name) || empty($first_name) || empty($sex) || empty($email) || empty($section_id) || empty($_POST['password'])) {
        $error = "All fields except Middle Name are required.";
    } else {
        $sql = "INSERT INTO students (last_name, first_name, middle_name, sex, email, section_id, password) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssis", $last_name, $first_name, $middle_name, $sex, $email, $section_id, $password);

        if ($stmt->execute()) {
            header("Location: manage_students.php");
            exit();
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/header.php'; ?>
        <div class="main-content">
            <div class="header">
                <h3>Add New Student</h3>
            </div>
            <div class="form-container">
                <?php if(!empty($error)): ?>
                    <p class="message error"><?php echo $error; ?></p>
                <?php endif; ?>
                <form action="add_student.php" method="post">
                    <?php echo csrf_field(); ?>
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
                        <label for="section_id">Class/Section</label>
                        <select id="section_id" name="section_id" required>
                            <option value="">Select Class</option>
                            <?php if ($result_sections->num_rows > 0): ?>
                                <?php while($row = $result_sections->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id']; ?>">
                                        <?php echo htmlspecialchars($row['grade_name'] . ' - ' . $row['section_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn">Add Student</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>