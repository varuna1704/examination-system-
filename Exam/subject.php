<?php
session_start();
include 'config.php';
if(!isset($_SESSION['u_name'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
</head>
<body>
    <?php include("modern_header.php"); ?>
    
    <div class="container">
        <div style="margin-bottom: 3rem;">
            <h1>Subject Selection Dashboard</h1>
            <p class="text-muted">Choose a subject below to begin your examination. Each test is timed and monitored.</p>
        </div>

        <div class="subject-grid">
            <?php
            // Ensure subject table exists in MySQL and has data
            $conn->query("CREATE TABLE IF NOT EXISTS subject (sub_id INT AUTO_INCREMENT PRIMARY KEY, sub_name VARCHAR(100))");
            $check = $conn->query("SELECT * FROM subject");
            if($check->num_rows == 0) {
                $conn->query("INSERT INTO subject (sub_name) VALUES ('Java Programming Language'), ('PHP Programming Language'), ('Python Programming Language'), ('C Language'), ('Data Structure')");
                $check = $conn->query("SELECT * FROM subject");
            }

            $icons = [
                'Java' => '☕',
                'Python' => '🐍',
                'C Language' => '💻',
                'PHP' => '🐘',
                'Data Structure' => '📊',
                'Default' => '📚'
            ];

            while($row = $check->fetch_assoc()) {
                $subject_name = $row['sub_name'];
                $sub_id = $row['sub_id'];
                $icon = '📚';
                foreach($icons as $key => $val) {
                    if(strpos($subject_name, $key) !== false) {
                        $icon = $val;
                        break;
                    }
                }
                echo "
                <a href='levels.php?subid=$sub_id&testid=$sub_id&subname=".urlencode($subject_name)."' class='subject-card'>
                    <span class='subject-icon'>$icon</span>
                    <span class='subject-title'>$subject_name</span>
                    <p class='text-muted' style='margin-top: 1rem;'>Start Examination &rarr;</p>
                </a>";
            }
            ?>
        </div>
    </div>
</body>
</html>