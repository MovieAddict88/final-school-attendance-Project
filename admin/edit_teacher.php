<?php
require_once '../includes/session.php';
require_once '../includes/csrf.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

include '../includes/database.php';

$teacher_id = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_or_die();
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $profile_image = $_POST['current_image'];
    if (isset($_FILES['profile_image'])) {
        if ($_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../uploads/images/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $maxBytes = 2 * 1024 * 1024; // 2MB
            if ($_FILES['profile_image']['size'] > $maxBytes) {
                throw new Exception('Profile image exceeds 2MB size limit.');
            }
            $allowedExts = ['jpg','jpeg','png','gif','webp'];
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts, true)) {
                throw new Exception('Unsupported image type.');
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['profile_image']['tmp_name']);
            $allowedMime = ['image/jpeg','image/png','image/gif','image/webp'];
            if (!in_array($mime, $allowedMime, true)) {
                throw new Exception('Invalid image content.');
            }
            $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
            $target_file = $target_dir . $randomName;
            if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                throw new Exception('Failed to move uploaded file.');
            }
            $profile_image = "uploads/images/" . $randomName;
        }
        // If no file uploaded or other non-fatal error, keep current image
    }

    $conn->begin_transaction();

    try {
        $sql = "UPDATE teachers SET full_name = ?, email = ?, phone = ?, profile_image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $full_name, $email, $phone, $profile_image, $teacher_id);
        $stmt->execute();

        $sql_delete = "DELETE FROM teacher_assignments WHERE teacher_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $teacher_id);
        $stmt_delete->execute();

        if (isset($_POST['assignments'])) {
            $assignments = $_POST['assignments'];
            $sql_assignment = "INSERT INTO teacher_assignments (teacher_id, section_id, subject_id) VALUES (?, ?, ?)";
            $stmt_assignment = $conn->prepare($sql_assignment);

            foreach ($assignments as $assignment) {
                if (!empty($assignment['section']) && !empty($assignment['subject'])) {
                    $section_id = $assignment['section'];
                    $subject_id = $assignment['subject'];
                    $stmt_assignment->bind_param("iii", $teacher_id, $section_id, $subject_id);
                    $stmt_assignment->execute();
                }
            }
        }

        $conn->commit();
        header("Location: manage_teachers.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
} else {
    $sql = "SELECT * FROM teachers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher = $result->fetch_assoc();

    $sql_assignments = "SELECT ta.section_id, ta.subject_id, s.grade_id FROM teacher_assignments ta JOIN sections s ON ta.section_id = s.id WHERE ta.teacher_id = ?";
    $stmt_assignments = $conn->prepare($sql_assignments);
    $stmt_assignments->bind_param("i", $teacher_id);
    $stmt_assignments->execute();
    $result_assignments = $stmt_assignments->get_result();
    $assignments = [];
    while ($row = $result_assignments->fetch_assoc()) {
        $assignments[] = $row;
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
    <title>Edit Teacher</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/header.php'; ?>
        <div class="main-content">
            <div class="header">
                <h3>Edit Teacher</h3>
            </div>
            <div class="form-container">
                <?php if(isset($error)): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>
                <form action="edit_teacher.php?id=<?php echo $teacher_id; ?>" method="post" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <div class="input-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo $teacher['full_name']; ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo $teacher['email']; ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" value="<?php echo $teacher['phone']; ?>">
                    </div>
                    <div class="input-group">
                        <label for="profile_image">Profile Image</label>
                        <input type="file" id="profile_image" name="profile_image">
                        <input type="hidden" name="current_image" value="<?php echo $teacher['profile_image']; ?>">
                        <?php if (!empty($teacher['profile_image'])): ?>
                            <img src="../<?php echo $teacher['profile_image']; ?>" alt="Profile Image" width="100">
                        <?php endif; ?>
                    </div>

                    <div id="assignments-container">
                        <h4>Assignments</h4>
                        <?php foreach ($assignments as $i => $assignment): ?>
                        <div class="assignment">
                            <select name="assignments[<?php echo $i; ?>][grade]" class="grade-select" data-section-id="<?php echo $assignment['section_id']; ?>">
                                <option value="">Select Grade</option>
                                <?php mysqli_data_seek($result_grades, 0); ?>
                                <?php while($row = $result_grades->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo ($row['id'] == $assignment['grade_id']) ? 'selected' : ''; ?>><?php echo $row['grade_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                            <select name="assignments[<?php echo $i; ?>][section]" class="section-select">
                                <option value="">Select Section</option>
                            </select>
                            <select name="assignments[<?php echo $i; ?>][subject]">
                                <option value="">Select Subject</option>
                                <?php mysqli_data_seek($result_subjects, 0); ?>
                                <?php while($row = $result_subjects->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo ($row['id'] == $assignment['subject_id']) ? 'selected' : ''; ?>><?php echo $row['subject_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="add-assignment">Add Another Assignment</button>
                    <button type="submit" class="btn">Update Teacher</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let assignmentIndex = <?php echo count($assignments); ?>;
            const container = document.getElementById('assignments-container');

            function fetchSections(gradeSelect, sectionIdToSelect) {
                const gradeId = gradeSelect.value;
                const sectionSelect = gradeSelect.closest('.assignment').querySelector('.section-select');
                if (!gradeId) {
                    sectionSelect.innerHTML = '<option value="">Select Section</option>';
                    return;
                }
                
                sectionSelect.innerHTML = '<option value="">Loading...</option>';

                fetch(`get_sections.php?grade_id=${gradeId}`)
                    .then(response => response.json())
                    .then(data => {
                        sectionSelect.innerHTML = '<option value="">Select Section</option>';
                        data.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section.id;
                            option.textContent = section.section_name;
                            if (section.id == sectionIdToSelect) {
                                option.selected = true;
                            }
                            sectionSelect.appendChild(option);
                        });
                    });
            }

            document.querySelectorAll('.grade-select').forEach(gradeSelect => {
                fetchSections(gradeSelect, gradeSelect.dataset.sectionId);
            });

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
                    fetchSections(e.target, null);
                }
            });
        });
    </script>
</body>
</html>