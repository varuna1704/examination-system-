<link rel="stylesheet" href="modern-style.css">
<nav class="navbar">
    <div class="navbar-brand">ExamPortal Pro</div>
    <div class="navbar-links">
        <?php if(isset($_SESSION['u_name'])): ?>
            <span style="margin-right: 1rem;">Welcome, <strong><?php echo $_SESSION['u_name']; ?></strong></span>
            <a href="subject.php">Dashboard</a>
            <a href="adaptive_quiz.php" style="background: linear-gradient(90deg, #fbbf24, #f59e0b); color: #1e1b4b; font-weight: bold; padding: 0.25rem 0.6rem; border-radius: 6px; box-shadow: 0 0 10px rgba(245, 158, 11, 0.4); text-shadow: none;">⚡ AI Placement Test</a>
            <a href="history.php">My Results</a>
            <a href="leaderboard.php">Leaderboard</a>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin_dashboard.php" style="color: #cbd5e1; font-weight: 700; border: 1px solid rgba(255,255,255,0.25); padding: 0.3rem 0.75rem; border-radius: 6px; background: rgba(255,255,255,0.08); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Admin Portal</a>
            <?php endif; ?>
            <a href="Logout.php" style="color: #fca5a5;">Logout</a>
        <?php else: ?>
            <a href="index.php">Login</a>
            <a href="Registration.php">Register</a>
        <?php endif; ?>
    </div>
</nav>
