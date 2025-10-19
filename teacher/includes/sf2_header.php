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
<div class="sf2-sheet" role="region" aria-label="SF2 header">
    <div class="sf2-top-row">
        <div class="sf2-logo" aria-hidden="true">
            <img src="/uploads/images/logo.png" alt="School Logo">
        </div>

        <div class="sf2-title-wrap">
            <h1>School Form 2 (SF2) — Daily Attendance Report of Learners</h1>
            <p class="sub">(This replaced Form 1, Form 2 &amp; STS Form 4 - Absenteeism and Dropout Profile)</p>
        </div>

        <div class="sf2-logo" aria-hidden="true">
            <img src="/uploads/images/seal.png" alt="Department of Education Seal">
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
</div>