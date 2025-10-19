<?php $page = basename($_SERVER['PHP_SELF'], ".php"); ?>
<div class="hamburger">
    <span></span>
    <span></span>
    <span></span>
</div>
<div class="sidebar">
    <div class="sidebar-header">
        <h2>Admin Panel</h2>
    </div>
    <ul>
        <li><a href="dashboard.php" class="<?php if($page == 'dashboard') echo 'active'; ?>">Dashboard</a></li>
        <li><a href="manage_teachers.php" class="<?php if(in_array($page, ['manage_teachers', 'add_teacher', 'edit_teacher'])) echo 'active'; ?>">Manage Teachers</a></li>
        <li><a href="manage_students.php" class="<?php if(in_array($page, ['manage_students', 'add_student', 'edit_student'])) echo 'active'; ?>">Manage Students</a></li>
        <li><a href="manage_parents.php" class="<?php if(in_array($page, ['manage_parents', 'add_parent', 'edit_parent'])) echo 'active'; ?>">Manage Parents</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const sidebar = document.querySelector('.sidebar');

    hamburger.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
});
</script>