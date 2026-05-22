<?php
session_start();
include 'config.php';
if(!isset($_SESSION['u_name'])) {
    header("Location: index.php");
    exit;
}

$subid = $_GET['subid'] ?? ($_GET['subject_id'] ?? 0);
$testid = $_GET['testid'] ?? 0;

// Fetch subject name using canonical subjects table
$stmt = $conn->prepare("SELECT name FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subid);
$stmt->execute();
$sub_res = $stmt->get_result();
$sub_row = $sub_res->fetch_row();
$sub_name = $sub_row[0] ?? 'Subject';

$levels = [
    ['name' => 'Easy', 'questions' => 25, 'marks' => 25, 'class' => 'level-easy', 'icon' => '🌱', 'color' => '#22c55e'],
    ['name' => 'Medium', 'questions' => 25, 'marks' => 50, 'class' => 'level-medium', 'icon' => '🌿', 'color' => '#eab308'],
    ['name' => 'Hard', 'questions' => 25, 'marks' => 75, 'class' => 'level-hard', 'icon' => '🌳', 'color' => '#f97316'],
    ['name' => 'Advanced', 'questions' => 25, 'marks' => 100, 'class' => 'level-advanced', 'icon' => '🌋', 'color' => '#a855f7'],
    ['name' => 'Expert', 'questions' => 10, 'marks' => 100, 'class' => 'level-expert', 'icon' => '🏆', 'color' => '#ef4444']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Level | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        .level-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .level-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            text-align: center;
            text-decoration: none;
            color: var(--gray-900);
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border-top: 5px solid transparent;
        }
        .level-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }
        .level-icon { font-size: 2.5rem; margin-bottom: 1rem; display: block; }
        .level-name { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; display: block; }
        .level-stats { font-size: 0.85rem; color: var(--gray-600); }
    </style>
</head>
<body>
    <?php include("modern_header.php"); ?>
    
    <div class="container">
        <div style="margin-bottom: 3rem;">
            <h1><?php echo $sub_name; ?> - Difficulty Levels</h1>
            <p class="text-muted">Select your proficiency level to start the examination. Higher levels have more challenging questions and more marks.</p>
        </div>

        <div class="level-grid">
            <?php foreach($levels as $l): ?>
                <div class="level-card" style="border-top-color: <?php echo $l['color']; ?>">
                    <span class="level-icon"><?php echo $l['icon']; ?></span>
                    <span class="level-name" style="color: <?php echo $l['color']; ?>"><?php echo $l['name']; ?></span>
                    <div class="level-stats" style="margin-bottom: 1rem;">
                        <p><?php echo $l['questions']; ?> Questions</p>
                        <p><?php echo $l['marks']; ?> Total Marks</p>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <a href="quiz.php?subject_id=<?php echo (int)$subid; ?>&subname=<?php echo urlencode($_GET['subname'] ?? $sub_name); ?>&level=<?php echo $l['name']; ?>&mode=official" 
                           class="btn" style="background: <?php echo $l['color']; ?>; color: white; display: block; text-decoration: none; font-size: 0.9rem; padding: 0.6rem 1rem; border: none; font-weight: 600;">
                           Official Exam
                        </a>
                        <a href="quiz.php?subject_id=<?php echo (int)$subid; ?>&subname=<?php echo urlencode($_GET['subname'] ?? $sub_name); ?>&level=<?php echo $l['name']; ?>&mode=mock" 
                           class="btn btn-secondary" style="display: block; text-decoration: none; font-size: 0.9rem; padding: 0.6rem 1rem; border: 1px solid var(--gray-300); background: transparent; color: var(--gray-700); font-weight: 500;">
                           Mock Practice
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
