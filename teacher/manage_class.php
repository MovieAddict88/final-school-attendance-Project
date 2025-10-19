<?php
require_once '../includes/session.php';
require_once '../includes/csrf.php';
if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

include '../includes/database.php';

if (!isset($_GET['section_id']) || !isset($_GET['subject_id'])) {
    header("Location: dashboard.php");
    exit();
}

$section_id = $_GET['section_id'];
$subject_id = $_GET['subject_id'];


// Fetch class details
$sql_class_details = "
    SELECT
        g.grade_name,
        s.section_name,
        sub.subject_name
    FROM sections s
    JOIN grades g ON s.grade_id = g.id
    JOIN subjects sub ON sub.id = ?
    WHERE s.id = ?
";
$stmt_class_details = $conn->prepare($sql_class_details);
$stmt_class_details->bind_param("ii", $subject_id, $section_id);
$stmt_class_details->execute();
$result_class_details = $stmt_class_details->get_result();
$class_details = $result_class_details->fetch_assoc();

if (!$class_details) {
    echo "Class details not found.";
    exit();
}

// Fetch students in this section
$sql_students = "SELECT * FROM students WHERE section_id = ? ORDER BY last_name, first_name, middle_name";
$stmt_students = $conn->prepare($sql_students);
$stmt_students->bind_param("i", $section_id);
$stmt_students->execute();
$result_students = $stmt_students->get_result();

// Fetch students not in any section
$sql_unassigned_students = "SELECT * FROM students WHERE section_id IS NULL";
$result_unassigned_students = $conn->query($sql_unassigned_students);

// --- Attendance Sheet Logic ---
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Boundary checks for year to keep it within a reasonable range
if ($year < 2010) $year = 2010;
if ($year > 2030) $year = 2030;

// --- Navigation Logic for Month ---
$current_date = new DateTime("$year-$month-01");
$month_name = $current_date->format('F');

// Previous Month Calculation
$prev_month_date = clone $current_date;
$prev_month_date->modify('-1 month');
$prev_month = $prev_month_date->format('m');
$prev_year = $prev_month_date->format('Y');

// Next Month Calculation
$next_month_date = clone $current_date;
$next_month_date->modify('+1 month');
$next_month = $next_month_date->format('m');
$next_year = $next_month_date->format('Y');


// --- Date Calculation for Display ---
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$month_dates = [];
for ($day = 1; $day <= $days_in_month; $day++) {
    $date = new DateTime("$year-$month-$day");
    // We only care about weekdays for attendance
    if ((int)$date->format('N') < 6) { // 1 (Mon) to 5 (Fri)
        $month_dates[] = $date;
    }
}


// --- Fetch Attendance Data for the Entire Month ---
$attendance_data = [];
if (!empty($month_dates)) {
    // Get the first and last day of the month for the query
    $start_date = (new DateTime("$year-$month-01"))->format('Y-m-d');
    $end_date = (new DateTime("$year-$month-$days_in_month"))->format('Y-m-d');

    $sql_attendance = "
        SELECT student_id, class_date, status
        FROM attendance
        WHERE subject_id = ?
        AND class_date BETWEEN ? AND ?
    ";
    $stmt_attendance = $conn->prepare($sql_attendance);
    $stmt_attendance->bind_param("iss", $subject_id, $start_date, $end_date);
    $stmt_attendance->execute();
    $result_attendance = $stmt_attendance->get_result();

    while ($row = $result_attendance->fetch_assoc()) {
        $attendance_data[$row['student_id']][$row['class_date']] = $row['status'];
    }
}

// Monthly summary logic removed as it's not needed for weekly view.
// Totals are now calculated per-student inside the table loop.

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Class</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/sf2-header.css">
    <link rel="stylesheet" href="/css/print.css" media="print">
    <style>
        /* General Styles */
        .attendance-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
        }
        .attendance-nav {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .attendance-nav a, .attendance-nav span {
            padding: 8px 15px;
            text-decoration: none;
            background-color: #f0f0f0;
            border-radius: 5px;
        }
        .week-nav-buttons button {
            padding: 8px 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .week-nav-buttons button:hover {
            background-color: #0056b3;
        }
        .week-nav-buttons button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        /* Scrollable Table Styles */
        .table-container-scrollable {
            overflow-x: auto;
            max-width: 100%;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .attendance-table-monthly {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
        }
        .attendance-table-monthly th,
        .attendance-table-monthly td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            min-width: 55px; /* Ensure days have a minimum width */
        }
        .attendance-table-monthly th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .sticky-col {
            position: -webkit-sticky; /* For Safari */
            position: sticky;
            left: 0;
            background-color: #fff;
            z-index: 10;
        }
        .sticky-col.name-col {
            left: 55px; /* Adjust based on the width of the 'No.' column */
            min-width: 250px;
        }
        .sticky-col:nth-child(3) {
            left: 305px; /* Adjust based on previous columns' widths */
        }
        .total-col, .action-col {
            position: -webkit-sticky;
            position: sticky;
            background-color: #fff; /* Match row background */
            z-index: 10;
        }

        /* Set right positions for the sticky columns from the right */
        .action-col { right: 0; }
        .total-remarks { right: 60px; } /* Approximate width of Actions column */
        .total-absent { right: 145px; }  /* Width of Actions + Remarks */
        .total-present { right: 230px; } /* Width of Actions + Remarks + Absent */


        /* Cell Styles */
        .attendance-cell {
            cursor: pointer;
            vertical-align: middle;
            font-size: 1.5em;
            height: 50px;
        }
        .attendance-cell.present { color: green; }
        .attendance-cell.absent { color: red; }

        .btn-edit {
            padding: 5px 10px;
            background-color: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-size: 0.9em;
        }
        .btn-edit:hover { background-color: #1976D2; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/header.php'; ?>
        <div class="main-content">
            <div class="content-area">
                <?php
                // This file is intended to be included in manage_class.php,
                // so it assumes that the following variables are available in its scope:
                // - $class_details: array with 'grade_name' and 'section_name'
                // - $month_name: string, e.g., "October"
                // - $year: int, e.g., 2024

                // School Details (as per user request)
                $school_id = '50678';
                $school_name = 'FAGMMMU INSTITUTE';
                $division = 'CDO';

                // Calculate School Year automatically
                $school_year = $year . '-' . ($year + 1);

                // Get Grade and Section
                $grade_section = htmlspecialchars($class_details['grade_name']) . ' - ' . htmlspecialchars($class_details['section_name']);

                // Get Month
                $month_for_header = htmlspecialchars($month_name);

                ?>
                <?php if(isset($_GET['message']) && $_GET['message'] == 'students_added'): ?>
                    <div class="message success">Students added successfully!</div>
                <?php elseif(isset($_GET['message']) && $_GET['message'] == 'student_updated'): ?>
                    <div class="message success">Student information updated successfully!</div>
                <?php endif; ?>

                <h4>Enrolled Students</h4>
                <div class="sf2-sheet" role="region" aria-label="SF2 header">
                    <div class="sf2-top-row">
                        <div class="sf2-logo" aria-hidden="true">
                            <img src="../uploads/images/seal.png" alt="School Logo">
                        </div>

                        <div class="sf2-title-wrap">
                            <h1>School Form 2 (SF2) — Daily Attendance Report of Learners</h1>
                            <p class="sub">(This replaced Form 1, Form 2 &amp; STS Form 4 - Absenteeism and Dropout Profile)</p>
                        </div>

                        <div class="sf2-logo" aria-hidden="true">
                            <img src="../uploads/images/logo.png" alt="Department of Education Seal">
                        </div>
                    </div>

                    <div class="sf2-meta">
                        <div class="left">
                            <label>School ID</label>
                            <div class="sf2-box" id="school-id">
                                <?php echo $school_id; ?>
                            </div>
                        </div>

                        <div class="center">
                            <div style="font-size: clamp(10px, 2.5vw, 12px); color: var(--muted);">Name of School</div>
                            <div class="sf2-box" id="school-name" style="min-width: 0; margin: 0 auto; max-width: 420px;">
                                <?php echo $school_name; ?>
                            </div>
                        </div>

                        <div class="right">
                            <label>School Year</label>
                            <div class="sf2-box" id="school-year">
                                <?php echo $school_year; ?>
                            </div>
                        </div>
                    </div>

                    <div class="sf2-info-grid">
                        <div class="field">
                            <div class="label">Grade / Section</div>
                            <div class="value" id="grade-section"><?php echo $grade_section; ?></div>
                        </div>
                        <div class="field">
                            <div class="label">Month</div>
                            <div class="value" id="month"><?php echo $month_for_header; ?></div>
                        </div>
                        <div class="field">
                            <div class="label">District / Division</div>
                            <div class="value" id="district"><?php echo $division; ?></div>
                        </div>
                    </div>
                    <div class="attendance-controls">
                        <div class="attendance-nav">
                             <a href="?section_id=<?php echo $section_id; ?>&subject_id=<?php echo $subject_id; ?>&month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" <?php if ($prev_year < 2010) echo 'style="visibility: hidden;"'; ?>>&laquo; Previous Month</a>
                            <span><?php echo "$month_name $year"; ?></span>
                            <a href="?section_id=<?php echo $section_id; ?>&subject_id=<?php echo $subject_id; ?>&month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" <?php if ($next_year > 2030) echo 'style="visibility: hidden;"'; ?>>Next Month &raquo;</a>
                        </div>
                        <div class="week-nav-buttons">
                            <button id="scroll-prev-week">&lsaquo; Prev Week</button>
                            <button id="scroll-next-week">Next Week &rsaquo;</button>
                        </div>
                        <button id="print-button" class="btn" style="margin-left: 10px;">Print</button>
                    </div>
                    <div class="table-container-scrollable" id="attendance-table-container">
                        <table class="attendance-table-monthly">
                            <thead>
                                <tr>
                                    <th class="sticky-col">No.</th>
                                    <th class="sticky-col name-col">LEARNER'S NAME<br><span class="small">(Last Name, First Name, Middle Name)</span></th>
                                    <th class="sticky-col">SEX</th>
                                    <?php foreach ($month_dates as $d): ?>
                                        <th class="day-header" data-date="<?php echo $d->format('Y-m-d'); ?>">
                                            <?php echo $d->format('D') . '<br>' . $d->format('j'); ?>
                                        </th>
                                    <?php endforeach; ?>
                                    <th class="total-col total-present">TOTAL<br>PRESENT</th>
                                    <th class="total-col total-absent">TOTAL<br>ABSENT</th>
                                    <th class="total-col total-remarks">REMARKS</th>
                                    <th class="action-col">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_students->num_rows > 0): ?>
                                    <?php $count = 1; ?>
                                    <?php while($row = $result_students->fetch_assoc()): ?>
                                        <tr>
                                            <td class="sticky-col"><?php echo $count++; ?></td>
                                            <td class="sticky-col name-col">
                                                <?php
                                                    $name_parts = [$row['last_name'] . ',', $row['first_name']];
                                                    if (!empty(trim($row['middle_name']))) {
                                                        $name_parts[] = $row['middle_name'];
                                                    }
                                                    $full_name = htmlspecialchars(implode(' ', $name_parts));
                                                    echo $full_name;
                                                ?>
                                            </td>
                                            <td class="sticky-col"><?php echo htmlspecialchars(strtoupper(substr($row['sex'], 0, 1))); ?></td>
                                            <?php
                                                $total_present_month = 0;
                                                $total_absent_month = 0;
                                            ?>
                                            <?php foreach ($month_dates as $d):
                                                $date_str = $d->format('Y-m-d');
                                                $status = $attendance_data[$row['id']][$date_str] ?? '';
                                                $icon = '';
                                                if ($status === 'present') {
                                                    $icon = '&#10004;'; // Checkmark
                                                    $total_present_month++;
                                                } else if ($status === 'absent') {
                                                    $icon = '&#10006;'; // X
                                                    $total_absent_month++;
                                                }
                                            ?>
                                                <td class="attendance-cell <?php echo $status; ?>"
                                                    data-student-id="<?php echo $row['id']; ?>"
                                                    data-date="<?php echo $date_str; ?>"
                                                    data-status="<?php echo $status; ?>">
                                                    <?php echo $icon; ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <?php
                                                $total_school_days_in_month = count($month_dates);
                                                $remarks = '';
                                                if ($total_school_days_in_month > 0) {
                                                    $percentage = ($total_present_month / $total_school_days_in_month) * 100;
                                                    $remarks = number_format($percentage, 2) . '%';
                                                }
                                            ?>
                                            <td id="present-<?php echo $row['id']; ?>" class="total-col total-present"><?php echo $total_present_month; ?></td>
                                            <td id="absent-<?php echo $row['id']; ?>" class="total-col total-absent"><?php echo $total_absent_month; ?></td>
                                            <td id="remarks-<?php echo $row['id']; ?>" class="total-col total-remarks"><?php echo $remarks; ?></td>
                                            <td class="action-col">
                                                <a href="edit_student.php?student_id=<?php echo $row['id']; ?>&section_id=<?php echo $section_id; ?>&subject_id=<?php echo $subject_id; ?>" class="btn-edit">Edit</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php $result_students->data_seek(0); // Reset result set pointer ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?php echo count($month_dates) + 7; ?>">No students enrolled in this class yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <br>
                        <table style="width:100%; border:1px solid black;">
                            <tr>
                                <td style="width:50%; padding:8px;">Prepared by:<br><br><b>__________________________</b><br><small>Class Adviser</small></td>
                                <td style="width:50%; padding:8px;">Checked by:<br><br><b>__________________________</b><br><small>School Head</small></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="add-students-form" style="margin-top: 30px;">
                    <h4>Add New Student to Class</h4>
                    <div style="margin-bottom: 20px;">
                        <a href="add_student.php?section_id=<?php echo $section_id; ?>&subject_id=<?php echo $subject_id; ?>" class="btn" style="width: auto; display: inline-block; text-decoration: none; padding: 10px 15px;">Add New Student</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = <?php echo json_encode(csrf_token()); ?>;
        const subjectId = <?php echo json_encode($subject_id); ?>;
        const container = document.getElementById('attendance-table-container');
        const prevWeekBtn = document.getElementById('scroll-prev-week');
        const nextWeekBtn = document.getElementById('scroll-next-week');
        const dayHeaders = document.querySelectorAll('.day-header');
        const printBtn = document.getElementById('print-button');

        // --- Print Button Logic ---
        if (printBtn) {
            printBtn.addEventListener('click', function () {
                window.print();
            });
        }

        // --- Attendance Cell Click Logic ---
        document.querySelectorAll('.attendance-cell').forEach(cell => {
            cell.addEventListener('click', function () {
                if (this.dataset.busy === 'true') return;
                this.dataset.busy = 'true';

                const studentId = this.dataset.studentId;
                const classDate = this.dataset.date;
                let currentStatus = this.dataset.status;
                let nextStatus = (currentStatus === '') ? 'present' : (currentStatus === 'present' ? 'absent' : '');

                updateUICell(this, nextStatus);

                fetch('update_attendance.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({
                        student_id: studentId,
                        subject_id: subjectId,
                        class_date: classDate,
                        status: nextStatus,
                        month: <?php echo json_encode($month); ?>,
                        year: <?php echo json_encode($year); ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.totals) {
                        updateUITotals(studentId, data.totals);
                    } else {
                        console.error('Failed to update attendance');
                        updateUICell(this, currentStatus); // Revert on failure
                    }
                })
                .catch(error => console.error('Error:', error))
                .finally(() => {
                    this.dataset.busy = 'false';
                });
            });
        });

        function updateUICell(cell, status) {
            cell.dataset.status = status;
            cell.className = 'attendance-cell'; // Reset classes
            if (status) cell.classList.add(status);
            cell.innerHTML = (status === 'present') ? '&#10004;' : (status === 'absent' ? '&#10006;' : '');
        }

        function updateUITotals(studentId, totals) {
            document.getElementById('present-' + studentId).textContent = totals.total_present;
            document.getElementById('absent-' + studentId).textContent = totals.total_absent;
            document.getElementById('remarks-' + studentId).textContent = totals.remarks;
        }

        // --- Week-by-Week Scrolling Logic ---
        if (container && prevWeekBtn && nextWeekBtn && dayHeaders.length > 0) {
            const dayCellWidth = dayHeaders[0].offsetWidth;
            const scrollAmount = dayCellWidth * 5; // 5 days for a typical school week

            function updateScrollButtons() {
                const maxScroll = container.scrollWidth - container.clientWidth;
                prevWeekBtn.disabled = container.scrollLeft <= 0;
                nextWeekBtn.disabled = container.scrollLeft >= maxScroll;
            }

            nextWeekBtn.addEventListener('click', () => {
                container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            });

            prevWeekBtn.addEventListener('click', () => {
                container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            });

            // Update buttons on initial load and on scroll
            container.addEventListener('scroll', updateScrollButtons);
            updateScrollButtons(); // Initial check
             // Re-check on window resize as clientWidth might change
            window.addEventListener('resize', updateScrollButtons);
        }
    });
    </script>
</body>
</html>