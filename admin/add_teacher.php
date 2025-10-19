<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../includes/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $profile_image = '';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "../uploads/images/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $filename = basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_image = "uploads/images/" . $filename;
        }
    }

    $conn->begin_transaction();

    try {
        $sql = "INSERT INTO teachers (full_name, email, phone, password, profile_image) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $full_name, $email, $phone, $password, $profile_image);
        $stmt->execute();
        $teacher_id = $stmt->insert_id;

        if (isset($_POST['assignments'])) {
            $assignments = $_POST['assignments'];
            $sql_assignment = "INSERT INTO teacher_assignments (teacher_id, section_id, subject_id) VALUES (?, ?, ?)";
            $stmt_assignment = $conn->prepare($sql_assignment);

            foreach ($assignments as $assignment) {
                $section_id = $assignment['section'];
                $subject_id = $assignment['subject'];
                $stmt_assignment->bind_param("iii", $teacher_id, $section_id, $subject_id);
                $stmt_assignment->execute();
            }
        }

        $conn->commit();
        header("Location: manage_teachers.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
        file_put_contents('add_teacher_errors.log', $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
}

$sql_grades = "SELECT id, grade_name FROM grades";
$result_grades = $conn->query($sql_grades);

$sql_subjects = "SELECT id, subject_name FROM subjects";
$result_subjects = $conn->query($sql_subjects);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Teacher</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/header.php'; ?>
        <div class="main-content">
            <div class="header">
                <h3>Add New Teacher</h3>
            </div>
            <div class="form-container">
                <?php if(isset($error)): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>
                <form action="add_teacher.php" method="post" enctype="multipart/form-data">
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
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="input-group">
                        <label for="profile_image">Profile Image</label>
                        <input type="file" id="profile_image" name="profile_image">
                    </div>
                    <div id="assignments-container">
                        <h4>Assignments</h4>
                        <div class="assignment">
                            <select name="assignments[0][grade]" class="grade-select">
                                <option value="">Select Grade</option>
                                <?php while($row = $result_grades->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id']; ?>"><?php echo $row['grade_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                            <select name="assignments[0][section]" class="section-select">
                                <option value="">Select Section</option>
                            </select>
                            <select name="assignments[0][subject]">
                                <option value="">Select Subject</option>
                                <?php mysqli_data_seek($result_subjects, 0); ?>
                                <?php while($row = $result_subjects->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id']; ?>"><?php echo $row['subject_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <button type="button" id="add-assignment">Add Another Assignment</button>
                    <button type="submit" class="btn">Add Teacher</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let assignmentIndex = 1;
            const container = document.getElementById('assignments-container');

            document.getElementById('add-assignment').addEventListener('click', function() {
                const newAssignment = document.createElement('div');
                newAssignment.classList.add('assignment');
                newAssignment.innerHTML = `
                    <select name="assignments[${assignmentIndex}][grade]" class="grade-select">
                        <option value="">Select Grade</option>
                        <?php mysqli_data_seek($result_grades, 0); ?>
                        <?php while($row = $result_grades->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo $row['grade_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                    <select name="assignments[${assignmentIndex}][section]" class="section-select">
                        <option value="">Select Section</option>
                    </select>
                    <select name="assignments[${assignmentIndex}][subject]">
                        <option value="">Select Subject</option>
                        <?php mysqli_data_seek($result_subjects, 0); ?>
                        <?php while($row = $result_subjects->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo $row['subject_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                `;
                container.appendChild(newAssignment);
                assignmentIndex++;
            });

            container.addEventListener('change', function(e) {
                if (e.target.classList.contains('grade-select')) {
                    const gradeId = e.target.value;
                    const sectionSelect = e.target.nextElementSibling;
                    sectionSelect.innerHTML = '<option value="">Loading...</option>';

                    fetch(`get_sections.php?grade_id=${gradeId}`)
                        .then(response => response.json())
                        .then(data => {
                            sectionSelect.innerHTML = '<option value="">Select Section</option>';
                            data.forEach(section => {
                                const option = document.createElement('option');
                                option.value = section.id;
                                option.textContent = section.section_name;
                                sectionSelect.appendChild(option);
                            });
                        });
                }
            });
        });
    </script>
</body>
</html>