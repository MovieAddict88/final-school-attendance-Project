<?php
include '../includes/database.php';

if (isset($_GET['grade_id'])) {
    $grade_id = $_GET['grade_id'];
    $sql = "SELECT id, section_name FROM sections WHERE grade_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $grade_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }
    echo json_encode($sections);
}
?>