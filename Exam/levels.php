<?php
session_start();
include 'config.php';
if(!isset($_SESSION['u_name'])) {
    header("Location: index.php");
    exit;
}

$subid = $_GET['subid'] ?? 0;
$testid = $_GET['testid'] ?? 0;

// Fetch subject name using MySQL
$stmt = $conn->prepare("SELECT sub_name FROM subject WHERE sub_id = ?");
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
                <a href="quiz.php?subname=<?php echo urlencode($_GET['subname'] ?? $sub_name); ?>&level=<?php echo $l['name']; ?>" 
                   class="level-card" style="border-top-color: <?php echo $l['color']; ?>">
                    <span class="level-icon"><?php echo $l['icon']; ?></span>
                    <span class="level-name" style="color: <?php echo $l['color']; ?>"><?php echo $l['name']; ?></span>
                    <div class="level-stats">
                        <p><?php echo $l['questions']; ?> Questions</p>
                        <p><?php echo $l['marks']; ?> Total Marks</p>
                    </div>
                    <div style="margin-top: 1rem; font-weight: 600; color: <?php echo $l['color']; ?>">Start &rarr;</div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
