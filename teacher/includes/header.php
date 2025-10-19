<?php $page = basename($_SERVER['PHP_SELF'], ".php"); ?>
<div class="hamburger">
    <span></span>
    <span></span>
    <span></span>
</div>
<div class="sidebar">
    <div class="sidebar-header">
        <h2>Teacher Panel</h2>
    </div>
    <div class="profile-info">
        <?php if (isset($_SESSION['teacher_profile_image']) && !empty($_SESSION['teacher_profile_image'])): ?>
            <img src="../<?php echo $_SESSION['teacher_profile_image']; ?>" alt="Profile Image" class="profile-image">
        <?php endif; ?>
        <div class="profile-text">
            <h3><?php echo $_SESSION['teacher_name']; ?></h3>
            <p>ID: <?php echo $_SESSION['teacher_id']; ?></p>
        </div>
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