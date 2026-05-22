<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/security.php';
require_login();

$attemptId = (int)($_GET['attempt_id'] ?? ($_SESSION['last_attempt_id'] ?? 0));
if ($attemptId <= 0) {
    http_response_code(400);
    exit('Invalid attempt.');
}

$ownershipStmt = $conn->prepare("SELECT id FROM exam_attempts WHERE id = ? AND user_id = ?");
$ownershipStmt->bind_param("ii", $attemptId, $_SESSION['user_id']);
$ownershipStmt->execute();
$ownershipRes = $ownershipStmt->get_result();
if (!$ownershipRes || $ownershipRes->num_rows === 0) {
    http_response_code(403);
    exit('You do not have access to this attempt.');
}

$ids = [];
$responses = [];
$answersStmt = $conn->prepare("SELECT question_id, selected_answer FROM attempt_answers WHERE attempt_id = ? ORDER BY id ASC");
$answersStmt->bind_param("i", $attemptId);
$answersStmt->execute();
$answersRes = $answersStmt->get_result();
while ($answerRow = $answersRes->fetch_assoc()) {
    $qid = (int)$answerRow['question_id'];
    $ids[] = $qid;
    $responses[$qid] = is_null($answerRow['selected_answer']) ? null : (int)$answerRow['selected_answer'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Answers | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        .review-item { margin-bottom: 2rem; padding: 1.5rem; border-radius: var(--radius); background: white; box-shadow: var(--shadow); border-left: 10px solid transparent; }
        .correct-border { border-left-color: #22c55e; }
        .wrong-border { border-left-color: #ef4444; }
        .ans-option { padding: 0.75rem; border-radius: 8px; margin: 0.5rem 0; }
        .correct-ans { background: #dcfce7; color: #166534; }
        .wrong-ans { background: #fee2e2; color: #991b1b; }
        .explanation-box { margin-top: 1rem; padding: 1rem; background: var(--gray-50); border-radius: 8px; font-style: italic; font-size: 0.95rem; }
    </style>
</head>
<body>
    <?php include("modern_header.php"); ?>
    
    <div class="container">
        <div style="margin-bottom: 3rem;">
            <h1>Review Your Answers</h1>
            <p class="text-muted">Analyze your performance and learn from the detailed explanations below.</p>
        </div>

        <?php 
        foreach($ids as $index => $qid): 
            $stmt = $conn->prepare("SELECT * FROM questions WHERE id = ?");
            $stmt->bind_param("i", $qid);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            
            $user_ans = $responses[$qid] ?? null;
            $correct_letter = strtoupper($row['correct_answer']);
            $correct_num = ord($correct_letter) - 64; 
            
            $is_correct = ($user_ans == $correct_num);
            $border_class = $is_correct ? 'correct-border' : 'wrong-border';
        ?>
            <div class="review-item <?php echo $border_class; ?>">
                <h3>Que <?php echo ($index + 1); ?>: <?php echo htmlspecialchars($row['question']); ?></h3>
                
                <?php
                $opts = [1 => $row['option_a'], 2 => $row['option_b'], 3 => $row['option_c'], 4 => $row['option_d']];
                foreach($opts as $num => $text):
                    $is_user_choice = ($user_ans == $num);
                    $is_this_correct = ($correct_num == $num);
                    $class = "";
                    if($is_this_correct) $class = "correct-ans";
                    else if($is_user_choice) $class = "wrong-ans";
                ?>
                    <div class="ans-option <?php echo $class; ?>">
                        <?php echo $num; ?>. <?php echo htmlspecialchars($text); ?> 
                        <?php if($is_user_choice) echo " <strong>(Your Choice)</strong>"; ?>
                        <?php if($is_this_correct && !$is_user_choice) echo " <strong>(Correct Answer)</strong>"; ?>
                    </div>
                <?php endforeach; ?>

                <div class="explanation-box">
                    <strong>Explanation:</strong> <?php echo htmlspecialchars($row['explanation']); ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="text-center" style="margin-top: 3rem;">
            <a href="subject.php" class="btn">Finish Review & Go to Dashboard</a>
        </div>
    </div>
</body>
</html>