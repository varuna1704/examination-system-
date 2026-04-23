<link rel="stylesheet" href="modern-style.css">
<nav class="navbar">
    <div class="navbar-brand">ExamPortal Pro</div>
    <div class="navbar-links">
        <?php if(isset($_SESSION['u_name'])): ?>
            <span style="margin-right: 1rem;">Welcome, <strong><?php echo $_SESSION['u_name']; ?></strong></span>
            <a href="subject.php">Dashboard</a>
            <a href="result.php">My Results</a>
            <a href="Logout.php" style="color: #fca5a5;">Logout</a>
        <?php else: ?>
            <a href="index.php">Login</a>
            <a href="Registration.php">Register</a>
        <?php endif; ?>
    </div>
</nav>
