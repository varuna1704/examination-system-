<?php
require 'config.php';
require_once 'lib/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error_msg = "Security validation failed. Please try again.";
    } else {
        $f_name = trim($_POST['f_name'] ?? '');
        $m_name = trim($_POST['m_name'] ?? '');
        $l_name = trim($_POST['l_name'] ?? '');
        $u_name = trim($_POST['u_name'] ?? '');
        $u_email = trim($_POST['u_email'] ?? '');
        $u_pass = $_POST['u_pass'] ?? '';
        $u_age = (int)($_POST['u_age'] ?? 0);
        $u_mob = trim($_POST['u_mob'] ?? '');
        $u_adr = trim($_POST['u_adr'] ?? '');

        // Ensure users table exists, then align legacy schemas safely
        $conn->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            f_name VARCHAR(100),
            m_name VARCHAR(100),
            l_name VARCHAR(100),
            u_name VARCHAR(100) UNIQUE,
            u_email VARCHAR(100) UNIQUE,
            u_pass VARCHAR(255),
            u_age INT,
            u_mob VARCHAR(20),
            u_adr TEXT,
            role ENUM('admin', 'teacher', 'student') DEFAULT 'student',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS f_name VARCHAR(100)");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS m_name VARCHAR(100)");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS l_name VARCHAR(100)");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS u_email VARCHAR(100)");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS u_age INT");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS u_mob VARCHAR(20)");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS u_adr TEXT");
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('admin', 'teacher', 'student') DEFAULT 'student'");
        $conn->query("ALTER TABLE users MODIFY COLUMN u_pass VARCHAR(255)");

        $checkStmt = $conn->prepare("SELECT id FROM users WHERE u_name = ? OR u_email = ? LIMIT 1");
        if (!$checkStmt) {
            $error_msg = "Registration setup failed. Please retry.";
        } else {
            $checkStmt->bind_param("ss", $u_name, $u_email);
            $checkStmt->execute();
            $existing = $checkStmt->get_result();

            if ($existing && $existing->num_rows > 0) {
                $error_msg = "Username or email is already registered. Please use different credentials.";
            } else {
                $hashedPassword = password_hash($u_pass, PASSWORD_DEFAULT);
                $defaultRole = 'student';

                $stmt = $conn->prepare("INSERT INTO users (f_name, m_name, l_name, u_name, u_email, u_pass, u_age, u_mob, u_adr, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    $error_msg = "Registration failed. Please retry in a moment.";
                } else {
                    $stmt->bind_param("ssssssisss", $f_name, $m_name, $l_name, $u_name, $u_email, $hashedPassword, $u_age, $u_mob, $u_adr, $defaultRole);

                    if ($stmt->execute()) {
                        $success_msg = "You are registered successfully!";
                    } else {
                        $error_msg = "Registration failed. Please try again.";
                    }
                }
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
    <title>Register | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
</head>
<body>
    <?php include("modern_header.php"); ?>
    
    <div class="flex-center" style="padding: 2rem 0;">
        <div class="card card-lg">
            <h2 class="text-center">Create Account</h2>
            <p class="text-center text-muted" style="margin-bottom: 2.5rem;">Join thousands of students and start your examination journey today.</p>
            
            <?php if(isset($success_msg)): ?>
                <div style="background: #dcfce7; color: #166534; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; text-align: center;">
                    <h3 style="color: #166534; margin-bottom: 0.5rem;"><?php echo $success_msg; ?></h3>
                    <p>You can now <a href="index.php" style="font-weight: 700; color: #166534; text-decoration: underline;">Login to your account</a></p>
                </div>
            <?php else: ?>
                <?php if(isset($error_msg)): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center; font-size: 0.9rem;">
                        <?php echo e($error_msg); ?>
                    </div>
                <?php endif; ?>
                <form name="registration" action="" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" name="f_name" class="form-control" placeholder="John" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="m_name" class="form-control" placeholder="Quincy" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="l_name" class="form-control" placeholder="Doe" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" name="u_name" class="form-control" placeholder="johndoe123" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="u_email" class="form-control" placeholder="john@example.com" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="u_pass" class="form-control" placeholder="••••••••" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Age</label>
                            <input type="number" name="u_age" class="form-control" placeholder="21" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" name="u_mob" class="form-control" placeholder="9876543210" required />
                        </div>
                        <div class="form-group" style="grid-column: span 1 / -1;">
                            <label class="form-label">Full Address</label>
                            <input type="text" name="u_adr" class="form-control" placeholder="123 Main St, City, Country" required />
                        </div>
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" name="submit" class="btn">Register Account</button>
                        <p class="text-center text-muted" style="margin-top: 1rem;">Already have an account? <a href="index.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Login here</a></p>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>