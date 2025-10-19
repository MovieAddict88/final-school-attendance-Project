<?php
$servername = "sql312.infinityfree.com";
$username = "if0_40086614";
$password = "3z61mIXR0Ws";
$dbname = "if0_40086614_test";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    // Try to create the database and user if they don't exist
    // This requires a root-level connection temporarily.
    $root_conn = new mysqli($servername, 'root', '');
    if ($root_conn->connect_error) {
        die("Connection failed: " . $conn->connect_error . " and could not connect as root to fix: " . $root_conn->connect_error);
    }

    // Create database
    $sql_create_db = "CREATE DATABASE IF NOT EXISTS $dbname";
    if (!$root_conn->query($sql_create_db)) {
        die("Error creating database: " . $root_conn->error);
    }

    // Create user
    $sql_create_user = "CREATE USER IF NOT EXISTS '$username'@'localhost' IDENTIFIED BY '$password'";
    if (!$root_conn->query($sql_create_user)) {
        die("Error creating user: " . $root_conn->error);
    }

    // Grant privileges
    $sql_grant_privileges = "GRANT ALL PRIVILEGES ON $dbname.* TO '$username'@'localhost'";
    if (!$root_conn->query($sql_grant_privileges)) {
        die("Error granting privileges: " . $root_conn->error);
    }

    $root_conn->close();

    // Retry initial connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed even after setup attempt: " . $conn->connect_error);
    }
}

// Select the database
$conn->select_db($dbname);

// SQL to create tables
$sql_admins = "CREATE TABLE IF NOT EXISTS admins (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL,
    password VARCHAR(255) NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$sql_teachers = "CREATE TABLE IF NOT EXISTS teachers (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    profile_image VARCHAR(255),
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$sql_grades = "CREATE TABLE IF NOT EXISTS grades (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    grade_name VARCHAR(50) NOT NULL UNIQUE
)";

$sql_sections = "CREATE TABLE IF NOT EXISTS sections (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(50) NOT NULL,
    grade_id INT(6) UNSIGNED,
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE CASCADE
)";

$sql_subjects = "CREATE TABLE IF NOT EXISTS subjects (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL UNIQUE
)";

$sql_teacher_assignments = "CREATE TABLE IF NOT EXISTS teacher_assignments (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT(6) UNSIGNED,
    section_id INT(6) UNSIGNED,
    subject_id INT(6) UNSIGNED,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY (teacher_id, section_id, subject_id)
)";

$sql_students = "CREATE TABLE IF NOT EXISTS students (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    sex VARCHAR(10) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    address VARCHAR(255),
    phone VARCHAR(20),
    section_id INT(6) UNSIGNED,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$sql_parents = "CREATE TABLE IF NOT EXISTS parents (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    student_id INT(6) UNSIGNED,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$sql_attendance = "CREATE TABLE IF NOT EXISTS attendance (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT(6) UNSIGNED NOT NULL,
    subject_id INT(6) UNSIGNED NOT NULL,
    teacher_id INT(6) UNSIGNED NOT NULL,
    class_date DATE NOT NULL,
    status ENUM('present', 'absent') DEFAULT NULL,
    UNIQUE KEY unique_attendance (student_id, subject_id, class_date),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
)";


// Execute all table creation queries
$conn->query($sql_admins);
$conn->query($sql_teachers);
$conn->query($sql_grades);
$conn->query($sql_sections);
$conn->query($sql_subjects);
$conn->query($sql_teacher_assignments);
$conn->query($sql_students);
$conn->query($sql_parents);
$conn->query($sql_attendance);

// Also run an alter statement to be sure. This is safe to run multiple times.
$conn->query("ALTER TABLE attendance MODIFY status ENUM('present', 'absent') DEFAULT NULL");


// --- Seeding Data ---

// Seed Grades
$grades = ["Grade 1", "Grade 2", "Grade 3", "Grade 4", "Grade 5", "Grade 6"];
$stmt_grade = $conn->prepare("INSERT INTO grades (grade_name) VALUES (?) ON DUPLICATE KEY UPDATE grade_name=grade_name");
foreach ($grades as $grade) {
    $stmt_grade->bind_param("s", $grade);
    $stmt_grade->execute();
}

// Seed Sections
$sql_check_sections = "SELECT id FROM sections LIMIT 1";
$result_check_sections = $conn->query($sql_check_sections);
if ($result_check_sections->num_rows == 0) {
    $sql_get_grades = "SELECT id FROM grades";
    $grades_result = $conn->query($sql_get_grades);
    if ($grades_result->num_rows > 0) {
        $stmt_section = $conn->prepare("INSERT INTO sections (section_name, grade_id) VALUES (?, ?)");
        $sections = ['Section A', 'Section B', 'Section C'];
        while ($grade_row = $grades_result->fetch_assoc()) {
            foreach ($sections as $section) {
                $stmt_section->bind_param("si", $section, $grade_row['id']);
                $stmt_section->execute();
            }
        }
    }
}

// Seed Subjects
$subjects = ["Mathematics", "Science", "English", "Filipino", "Araling Panlipunan", "Edukasyon sa Pagpapakatao", "Music, Arts, Physical Education, and Health (MAPEH)"];
$stmt_subject = $conn->prepare("INSERT INTO subjects (subject_name) VALUES (?) ON DUPLICATE KEY UPDATE subject_name=subject_name");
foreach ($subjects as $subject) {
    $stmt_subject->bind_param("s", $subject);
    $stmt_subject->execute();
}

?>