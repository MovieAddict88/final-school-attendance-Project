<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../includes/database.php';

// Fetch all students with their class details
$sql = "
    SELECT s.id, s.last_name, s.first_name, s.middle_name, s.sex, s.email, g.grade_name, sec.section_name
    FROM students s
    LEFT JOIN sections sec ON s.section_id = sec.id
    LEFT JOIN grades g ON sec.grade_id = g.id
    ORDER BY s.last_name, s.first_name, s.middle_name
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/header.php'; ?>
        <div class="main-content">
            <div class="header">
                <h3>Manage Students</h3>
            </div>
            <a href="add_student.php" class="btn" style="margin-bottom: 20px; display: inline-block; width: auto;">Add New Student</a>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Sex</th>
                            <th>Email</th>
                            <th>Class</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name'] . ' ' . $row['middle_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sex']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['grade_name'] . ' - ' . $row['section_name']); ?></td>
                                    <td>
                                        <a href="edit_student.php?id=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                                        <form action="delete_student.php" method="post" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No students found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>