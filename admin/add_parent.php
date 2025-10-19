<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../includes/database.php';

// Fetch all students for the dropdown
$sql_students = "SELECT id, last_name, first_name, middle_name FROM students ORDER BY last_name, first_name, middle_name";
$result_students = $conn->query($sql_students);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $student_id = $_POST['student_id'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO parents (full_name, email, phone, student_id, password) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssis", $full_name, $email, $phone, $student_id, $password);

    if ($stmt->execute()) {
        header("Location: manage_parents.php");
        exit();
    } else {
        $error = "Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Parent</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/header.php'; ?>
        <div class="main-content">
            <div class="header">
                <h3>Add New Parent</h3>
            </div>
            <div class="form-container">
                <?php if(isset($error)): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>
                <form action="add_parent.php" method="post">
                    <div class="input-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="input-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone">
                    </div>
                    <div class="input-group">
                        <label for="student_id">Student</label>
                        <select id="student_id" name="student_id" required>
                            <option value="">Select a Student</option>
                            <?php while($student = $result_students->fetch_assoc()): ?>
                                <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn">Add Parent</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>