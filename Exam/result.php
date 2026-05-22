<?php
include 'config.php';
require_once 'lib/security.php';
require_once 'lib/result_repository.php';

require_login();

$score = $_SESSION['true_ans'] ?? 0;
$total = count($_SESSION['question_ids'] ?? []);
$perc = ($total > 0) ? ($score / $total) * 100 : 0;

// Save result to DB
$userId = (int)($_SESSION['user_id'] ?? 0);
$subname = $_SESSION['subname'] ?? 'General';
$level = $_SESSION['level'] ?? 'Easy';
$startedAt = $_POST['started_at'] ?? ($_SESSION['attempt_started_at'] ?? date("Y-m-d H:i:s"));
$attemptId = null;

$subjectId = (int)($_POST['subject_id'] ?? ($_SESSION['subject_id'] ?? 0));
if ($subjectId <= 0) {
    $subjectStmt = $conn->prepare("SELECT id FROM subjects WHERE name = ? LIMIT 1");
    $subjectStmt->bind_param("s", $subname);
    $subjectStmt->execute();
    $subjectRes = $subjectStmt->get_result();
    if ($subjectRes && $subjectRes->num_rows > 0) {
        $subjectId = (int)$subjectRes->fetch_assoc()['id'];
    } else {
        $insertSubjectStmt = $conn->prepare("INSERT INTO subjects (name) VALUES (?)");
        $insertSubjectStmt->bind_param("s", $subname);
        if ($insertSubjectStmt->execute()) {
            $subjectId = (int)$conn->insert_id;
        }
    }
}

if ($userId > 0 && $subjectId > 0 && empty($_SESSION['attempt_saved'])) {
    $responses = [];
    $questionIds = $_SESSION['question_ids'] ?? [];
    $userResponses = $_SESSION['user_responses'] ?? [];

    if (!empty($questionIds)) {
        $answerStmt = $conn->prepare("SELECT id, correct_answer FROM questions WHERE id = ?");
        foreach ($questionIds as $qid) {
            $qid = (int)$qid;
            $selected = $userResponses[$qid] ?? null;
            $isCorrect = 0;

            $answerStmt->bind_param("i", $qid);
            $answerStmt->execute();
            $correctRow = $answerStmt->get_result()->fetch_assoc();
            if ($correctRow && $selected !== null) {
                $correctNum = ord(strtoupper($correctRow['correct_answer'])) - 64;
                $isCorrect = ((int)$selected === $correctNum) ? 1 : 0;
            }

            $responses[$qid] = [
                'selected' => is_null($selected) ? null : (string)$selected,
                'is_correct' => $isCorrect
            ];
        }
    }

    $attemptId = save_attempt($conn, $userId, $subjectId, $level, $total, $score, $startedAt, $responses);
    if ($attemptId !== null) {
        $_SESSION['attempt_saved'] = true;
        $_SESSION['last_attempt_id'] = $attemptId;
    }
} else {
    $attemptId = $_SESSION['last_attempt_id'] ?? null;
}

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
            <?php if($attemptId !== null): ?>
                <p class="text-center text-muted" style="margin-top: 0.5rem;">Attempt ID: <strong>#<?php echo (int)$attemptId; ?></strong></p>
            <?php elseif($userId > 0 && $subjectId > 0): ?>
                <p class="text-center text-muted" style="margin-top: 0.5rem; color: #991b1b;">Result could not be saved. Your score: <?php echo $score; ?>/<?php echo $total; ?></p>
            <?php endif; ?>

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