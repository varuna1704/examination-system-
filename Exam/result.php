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

$examMode = $_SESSION['exam_mode'] ?? 'official';

if ($userId > 0 && $subjectId > 0 && empty($_SESSION['final_attempt_saved'])) {
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

    if (isset($_SESSION['last_attempt_id']) && $_SESSION['last_attempt_id'] > 0) {
        $attemptId = $_SESSION['last_attempt_id'];
        $updated = update_attempt($conn, $attemptId, $score, $total, $responses);
        if ($updated) {
            $_SESSION['final_attempt_saved'] = true;
            // Set proctor_status to completed
            $statusStmt = $conn->prepare("UPDATE exam_attempts SET proctor_status = 'completed' WHERE id = ?");
            $statusStmt->bind_param("i", $attemptId);
            $statusStmt->execute();
        }
    } else {
        $attemptId = save_attempt($conn, $userId, $subjectId, $level, $total, $score, $startedAt, $responses, $examMode);
        if ($attemptId !== null) {
            $_SESSION['final_attempt_saved'] = true;
            $_SESSION['last_attempt_id'] = $attemptId;
        }
    }
} else {
    $attemptId = $_SESSION['last_attempt_id'] ?? null;
}

// Fetch details for certificate checking
$isOfficial = false;
$passScore = false;
if ($attemptId !== null) {
    $attemptQuery = $conn->prepare("SELECT exam_mode, percentage FROM exam_attempts WHERE id = ?");
    $attemptQuery->bind_param("i", $attemptId);
    $attemptQuery->execute();
    $attemptRes = $attemptQuery->get_result()->fetch_assoc();
    if ($attemptRes) {
        $isOfficial = ($attemptRes['exam_mode'] === 'official');
        $passScore = ($attemptRes['percentage'] >= 50.00);
    }
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
                <p class="text-center text-muted" style="margin-top: 0.5rem;">Attempt ID: <strong>#<?php echo (int)$attemptId; ?></strong> (<?php echo htmlspecialchars(ucfirst($examMode)); ?>)</p>
            <?php elseif($userId > 0 && $subjectId > 0): ?>
                <p class="text-center text-muted" style="margin-top: 0.5rem; color: #991b1b;">Result could not be saved. Your score: <?php echo $score; ?>/<?php echo $total; ?></p>
            <?php endif; ?>

            <div style="margin: 2rem 0; background: var(--gray-50); padding: 2rem; border-radius: var(--radius); border: 1px solid var(--gray-200);">
                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; color: var(--gray-700);"><span>Total Questions:</span> <strong><?php echo $total; ?></strong></div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; color: #166534;"><span>Correct Answers:</span> <strong><?php echo $score; ?></strong></div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; color: #991b1b;"><span>Wrong Answers:</span> <strong><?php echo ($total - $score); ?></strong></div>
                <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 700; border-top: 2px solid var(--gray-200); padding-top: 1rem; color: var(--gray-900);"><span>Percentage:</span> <span><?php echo round($perc, 2); ?>%</span></div>
            </div>

            <?php if ($isOfficial && $passScore): ?>
                <div style="margin: 1.5rem 0; padding: 1.5rem; background: #e0f2fe; border-radius: var(--radius); border-left: 5px solid var(--primary); text-align: center;">
                    <h4 style="color: #0369a1; margin-bottom: 0.5rem;">🎉 Congratulations! You Passed!</h4>
                    <p style="font-size: 0.9rem; color: #0c4a6e; margin-bottom: 1rem;">Your certificate of completion is now available for download.</p>
                    <a href="certificate.php?attempt_id=<?php echo $attemptId; ?>" target="_blank" class="btn" style="background: var(--primary); border: none; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; font-weight: 600; text-decoration: none;">
                        🎓 Get Your Certificate
                    </a>
                </div>
            <?php elseif ($isOfficial && !$passScore): ?>
                <div style="margin: 1.5rem 0; padding: 1.5rem; background: #fef3c7; border-radius: var(--radius); border-left: 5px solid #d97706; text-align: center;">
                    <h4 style="color: #92400e; margin-bottom: 0.5rem;">Study a bit more!</h4>
                    <p style="font-size: 0.9rem; color: #78350f; margin-bottom: 0;">You need 50% or higher to earn the official certification. Review the explanations below and try again!</p>
                </div>
            <?php endif; ?>

            <div class="text-center" style="margin-top: 2rem;">
                <a href="review.php" class="btn btn-secondary" style="margin-bottom: 1rem; width: 100%;">Review Answers with Explanations</a><br>
                <a href="subject.php" class="text-muted" style="text-decoration: none; font-weight: 600;">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>