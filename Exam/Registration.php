<?php
require('config.php');
session_start();
if(isset($_REQUEST['f_name']))
{
    $f_name = $_REQUEST['f_name'];
    $m_name = $_REQUEST['m_name'];
    $l_name = $_REQUEST['l_name'];
    $u_name = $_REQUEST['u_name'];
    $u_email = $_REQUEST['u_email'];
    $u_pass = $_REQUEST['u_pass'];
    $u_age = $_REQUEST['u_age'];
    $u_mob = $_REQUEST['u_mob'];
    $u_adr = $_REQUEST['u_adr'];

    // Ensure users table exists with all fields
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        f_name VARCHAR(100), m_name VARCHAR(100), l_name VARCHAR(100),
        u_name VARCHAR(100), u_email VARCHAR(100), u_pass VARCHAR(100),
        u_age INT, u_mob VARCHAR(20), u_adr TEXT
    )");

    $stmt = $conn->prepare("INSERT INTO users (f_name, m_name, l_name, u_name, u_email, u_pass, u_age, u_mob, u_adr) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssiss", $f_name, $m_name, $l_name, $u_name, $u_email, $u_pass, $u_age, $u_mob, $u_adr);
    
    if($stmt->execute())
    {
        $success_msg = "You are registered successfully!";
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
                <form name="registration" action="" method="post">
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