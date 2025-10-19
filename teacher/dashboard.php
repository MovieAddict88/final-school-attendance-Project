<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

include '../includes/database.php';

$teacher_id = $_SESSION['teacher_id'];

// Fetch teacher's assignments
$sql_assignments = "
    SELECT 
        g.grade_name, 
        s.section_name, 
        sub.subject_name,
        ta.section_id,
        ta.subject_id
    FROM teacher_assignments ta
    JOIN sections s ON ta.section_id = s.id
    JOIN grades g ON s.grade_id = g.id
    JOIN subjects sub ON ta.subject_id = sub.id
    WHERE ta.teacher_id = ?
";

$stmt = $conn->prepare($sql_assignments);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result_assignments = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .assignments-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .assignment-card {
            background-color: #f0f0f0;
            border-left: 5px solid #4CAF50;
            padding: 20px;
            border-radius: 8px;
            width: 250px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .assignment-card:hover {
            transform: translateY(-5px);
        }
        .assignment-card h4 {
            margin-top: 0;
        }
        .assignment-card a {
            text-decoration: none;
            color: inherit;
        }
        /* Some color variations */
        .assignment-card.color-1 { border-color: #4CAF50; }
        .assignment-card.color-2 { border-color: #2196F3; }
        .assignment-card.color-3 { border-color: #f44336; }
        .assignment-card.color-4 { border-color: #ff9800; }
        .assignment-card.color-5 { border-color: #9C27B0; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/header.php'; ?>
        <div class="main-content">
            <div class="header">
                <h3>Welcome, <?php echo htmlspecialchars($_SESSION['teacher_name']); ?>!</h3>
            </div>
            <div class="content-area">
                <h4>Your Assigned Classes</h4>
                <div class="assignments-container">
                    <?php if ($result_assignments->num_rows > 0): ?>
                        <?php $color_index = 1; ?>
                        <?php while($row = $result_assignments->fetch_assoc()): ?>
                            <?php
                                $card_color_class = 'color-' . (($color_index - 1) % 5 + 1);
                            ?>
                            <div class="assignment-card <?php echo $card_color_class; ?>">
                                <a href="manage_class.php?section_id=<?php echo $row['section_id']; ?>&subject_id=<?php echo $row['subject_id']; ?>">
                                    <h4><?php echo htmlspecialchars($row['subject_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($row['grade_name']); ?> - <?php echo htmlspecialchars($row['section_name']); ?></p>
                                </a>
                            </div>
                            <?php $color_index++; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>You have not been assigned to any classes yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>