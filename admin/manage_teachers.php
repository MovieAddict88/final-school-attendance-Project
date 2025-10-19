<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../includes/database.php';

// Fetch all teachers and their assignments
$sql = "SELECT t.id, t.full_name, t.email, t.phone, g.grade_name, s.section_name, sub.subject_name
        FROM teachers t
        LEFT JOIN teacher_assignments ta ON t.id = ta.teacher_id
        LEFT JOIN sections s ON ta.section_id = s.id
        LEFT JOIN grades g ON s.grade_id = g.id
        LEFT JOIN subjects sub ON ta.subject_id = sub.id
        ORDER BY t.id";
$result = $conn->query($sql);

$teachers = [];
while ($row = $result->fetch_assoc()) {
    $teacher_id = $row['id'];
    if (!isset($teachers[$teacher_id])) {
        $teachers[$teacher_id] = [
            'id' => $row['id'],
            'full_name' => $row['full_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'assignments' => []
        ];
    }
    if ($row['grade_name']) {
        $teachers[$teacher_id]['assignments'][] = $row['grade_name'] . ' - ' . $row['section_name'] . ' (' . $row['subject_name'] . ')';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/header.php'; ?>
        <div class="main-content">
            <div class="header">
                <h3>Manage Teachers</h3>
            </div>
            <a href="add_teacher.php" class="btn" style="margin-bottom: 20px; display: inline-block; width: auto;">Add New Teacher</a>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Assignments</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($teachers)): ?>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td><?php echo $teacher['id']; ?></td>
                                    <td><?php echo $teacher['full_name']; ?></td>
                                    <td><?php echo $teacher['email']; ?></td>
                                    <td><?php echo $teacher['phone']; ?></td>
                                    <td>
                                        <?php if (!empty($teacher['assignments'])): ?>
                                            <ul>
                                                <?php foreach ($teacher['assignments'] as $assignment): ?>
                                                    <li><?php echo $assignment; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            No assignments.
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_teacher.php?id=<?php echo $teacher['id']; ?>" class="btn-edit">Edit</a>
                                        <form action="delete_teacher.php" method="post" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this teacher?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo $teacher['id']; ?>">
                                            <button type="submit" class="btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No teachers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>