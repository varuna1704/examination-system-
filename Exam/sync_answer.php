<?php
session_start();
include 'config.php';
require_once 'lib/security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $attemptId = isset($_POST['attempt_id']) ? (int)$_POST['attempt_id'] : 0;
    $questionId = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
    $selected = isset($_POST['selected']) && $_POST['selected'] !== '' ? (int)$_POST['selected'] : null;

    if ($userId > 0 && $attemptId > 0 && $questionId > 0) {
        // Validate user owns this attempt
        $chk = $conn->prepare("SELECT id FROM exam_attempts WHERE id = ? AND user_id = ?");
        $chk->bind_param("ii", $attemptId, $userId);
        $chk->execute();
        $chkRes = $chk->get_result();
        
        if ($chkRes->num_rows > 0) {
            // Check correctness
            $correctNum = 0;
            $qStmt = $conn->prepare("SELECT correct_answer FROM questions WHERE id = ?");
            $qStmt->bind_param("i", $questionId);
            $qStmt->execute();
            $qRes = $qStmt->get_result()->fetch_assoc();
            if ($qRes && $selected !== null) {
                $correctNum = ord(strtoupper($qRes['correct_answer'])) - 64;
            }
            $isCorrect = ($selected === $correctNum) ? 1 : 0;

            // Delete-then-insert to avoid duplicate keys cleanly
            $del = $conn->prepare("DELETE FROM attempt_answers WHERE attempt_id = ? AND question_id = ?");
            $del->bind_param("ii", $attemptId, $questionId);
            $del->execute();

            $ins = $conn->prepare("INSERT INTO attempt_answers (attempt_id, question_id, selected_answer, is_correct) VALUES (?, ?, ?, ?)");
            $ins->bind_param("iisi", $attemptId, $questionId, $selected, $isCorrect);
            if ($ins->execute()) {
                echo json_encode(['status' => 'success', 'msg' => 'Answer synced successfully']);
                exit;
            }
        }
    }
}

echo json_encode(['status' => 'error', 'msg' => 'Invalid parameters']);
?>
