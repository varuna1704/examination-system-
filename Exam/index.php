<?php
require "config.php";
require_once 'lib/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error_msg = "Security validation failed. Please try again.";
    } else {
        $u_name = trim($_POST['u_name'] ?? '');
        $u_pass = $_POST['u_pass'] ?? '';

        // Create users table if not exists
        $conn->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            u_name VARCHAR(100) UNIQUE,
            u_pass VARCHAR(255),
            role ENUM('admin', 'teacher', 'student') DEFAULT 'student'
        )");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('admin', 'teacher', 'student') DEFAULT 'student'");
        $conn->query("ALTER TABLE users MODIFY COLUMN u_pass VARCHAR(255)");

        // Fetch user by username only, then verify hashed password
        $stmt = $conn->prepare("SELECT id, u_name, u_pass, role FROM users WHERE u_name = ? LIMIT 1");
        if (!$stmt) {
            $error_msg = "Login is temporarily unavailable. Please try again.";
        } else {
            $stmt->bind_param("s", $u_name);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;

            if ($row && password_verify($u_pass, $row['u_pass'])) {
                $_SESSION['user_id'] = (int)$row['id'];
                $_SESSION['u_name'] = $row['u_name'];
                $_SESSION['role'] = $row['role'] ?? 'student';
                header("Location: subject.php");
                exit;
            } else {
                $error_msg = "Username/Password is incorrect";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
</head>
<body>
    <?php include("modern_header.php"); ?>
    
    <div class="flex-center">
        <div class="card">
            <h2 class="text-center">Account Login</h2>
            <p class="text-center text-muted" style="margin-bottom: 2rem;">Please enter your credentials to continue</p>
            
            <?php if(isset($error_msg)): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center; font-size: 0.9rem;">
                    <?php echo e($error_msg); ?>
                </div>
            <?php endif; ?>

            <form action="" method="post" name="login">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="u_name" class="form-control" placeholder="Enter your username" required />
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="u_pass" class="form-control" placeholder="••••••••" required />
                </div>

                <button type="submit" name="submit" class="btn">Login to Dashboard</button>
            </form>

            <div class="text-center" style="margin-top: 1.5rem;">
                <p class="text-muted">Don't have an account? <a href="Registration.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Sign up now</a></p>
            </div>
        </div>
    </div>
</body>
</html>