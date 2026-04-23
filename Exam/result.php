<?php
session_start();
include 'config.php';

if(!isset($_SESSION['u_name'])) {
    header("Location: index.php");
    exit;
}

$score = $_SESSION['true_ans'] ?? 0;
$total = count($_SESSION['question_ids'] ?? []);
$perc = ($total > 0) ? ($score / $total) * 100 : 0;

// Save result to DB
$login = $_SESSION['u_name'];
$subname = $_SESSION['subname'] ?? 'General';
$test_date = date("Y-m-d");

// Note: In MySQL setup, we might need a results table. 
// For now, I'll assume we want to show the UI.

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
</head>
<body>
    <?php include("modern_header.php"); ?>
    
    <div class="container flex-center">
        <div class="card card-lg">
            <h2 class="text-center">Examination Result</h2>
            <p class="text-center text-muted">Subject: <?php echo htmlspecialchars($subname); ?> | Level: <?php echo htmlspecialchars($_SESSION['level'] ?? ''); ?></p>

            <div style="margin: 2rem 0; background: var(--gray-50); padding: 2rem; border-radius: var(--radius);">
                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;"><span>Total Questions:</span> <strong><?php echo $total; ?></strong></div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; color: #166534;"><span>Correct Answers:</span> <strong><?php echo $score; ?></strong></div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; color: #991b1b;"><span>Wrong Answers:</span> <strong><?php echo ($total - $score); ?></strong></div>
                <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 700; border-top: 2px solid var(--gray-200); padding-top: 1rem;"><span>Percentage:</span> <span><?php echo round($perc, 2); ?>%</span></div>
            </div>

            <div class="text-center">
                <a href="review.php" class="btn btn-secondary" style="margin-bottom: 1rem;">Review Answers with Explanations</a><br>
                <a href="subject.php" class="text-muted" style="text-decoration: none;">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>