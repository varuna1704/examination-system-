<?php
require("config.php"); // Switch to MySQL config
session_start();
if(isset($_POST['u_name']))
{
    $u_name = $_POST['u_name'];
    $u_pass = $_POST['u_pass'];
    
    // Create users table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, u_name VARCHAR(100), u_pass VARCHAR(100))");
    
    // Check if user exists using Prepared Statements
    $stmt = $conn->prepare("SELECT * FROM users WHERE u_name = ? AND u_pass = ?");
    $stmt->bind_param("ss", $u_name, $u_pass);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 1)
    {
        $_SESSION['u_name'] = $u_name;
        header("Location: subject.php");
        exit;
    }
    else
    {
        $error_msg = "Username/Password is incorrect";
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
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <form action="" method="post" name="login">
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