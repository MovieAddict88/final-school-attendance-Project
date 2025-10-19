<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../includes/database.php';

$parent_id = $_GET['id'];

// Fetch all students for the dropdown
$sql_students = "SELECT id, last_name, first_name, middle_name FROM students ORDER BY last_name, first_name, middle_name";
$result_students = $conn->query($sql_students);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $student_id = $_POST['student_id'];

    $sql = "UPDATE parents SET full_name = ?, email = ?, phone = ?, student_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $full_name, $email, $phone, $student_id, $parent_id);

    if ($stmt->execute()) {
        header("Location: manage_parents.php");
        exit();
    } else {
        $error = "Error: " . $stmt->error;
    }
} else {
    $sql = "SELECT * FROM parents WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $parent = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Parent</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/header.php'; ?>
        <div class="main-content">
            <div class="header">
                <h3>Edit Parent</h3>
            </div>
            <div class="form-container">
                <?php if(isset($error)): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>
                <form action="edit_parent.php?id=<?php echo $parent_id; ?>" method="post">
                    <div class="input-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo $parent['full_name']; ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo $parent['email']; ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" value="<?php echo $parent['phone']; ?>">
                    </div>
                    <div class="input-group">
                        <label for="student_id">Student</label>
                        <select id="student_id" name="student_id" required>
                            <option value="">Select a Student</option>
                            <?php while($student = $result_students->fetch_assoc()): ?>
                                <option value="<?php echo $student['id']; ?>" <?php echo ($student['id'] == $parent['student_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn">Update Parent</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>