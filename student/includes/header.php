<?php $page = basename($_SERVER['PHP_SELF'], ".php"); ?>
<div class="hamburger">
    <span></span>
    <span></span>
    <span></span>
</div>
<div class="sidebar">
    <div class="sidebar-header">
        <h2>Student Panel</h2>
    </div>
    <ul>
        <li><a href="dashboard.php" class="<?php if($page == 'dashboard') echo 'active'; ?>">Dashboard</a></li>
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