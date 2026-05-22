<?php
function save_attempt(mysqli $conn, int $userId, int $subjectId, string $level, int $total, int $score, string $startedAt, array $responses, string $examMode = 'official'): ?int
{
    $percentage = $total > 0 ? round(($score / $total) * 100, 2) : 0.00;
    $submittedAt = date('Y-m-d H:i:s');

    $conn->begin_transaction();

    try {
        $attemptStmt = $conn->prepare(
            "INSERT INTO exam_attempts (user_id, subject_id, level, total_questions, score, percentage, started_at, submitted_at, exam_mode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $attemptStmt->bind_param(
            "iisiiisss",
            $userId,
            $subjectId,
            $level,
            $total,
            $score,
            $percentage,
            $startedAt,
            $submittedAt,
            $examMode
        );
        $attemptStmt->execute();

        $attemptId = (int)$conn->insert_id;

        $answerStmt = $conn->prepare(
            "INSERT INTO attempt_answers (attempt_id, question_id, selected_answer, is_correct) VALUES (?, ?, ?, ?)"
        );

        foreach ($responses as $questionId => $response) {
            $questionId = (int)$questionId;
            $selectedAnswer = null;
            $isCorrect = 0;

            if (is_array($response)) {
                $selectedAnswer = isset($response['selected']) ? (string)$response['selected'] : null;
                $isCorrect = !empty($response['is_correct']) ? 1 : 0;
            } else {
                $selectedAnswer = is_null($response) ? null : (string)$response;
            }

            $answerStmt->bind_param("iisi", $attemptId, $questionId, $selectedAnswer, $isCorrect);
            $answerStmt->execute();
        }

        $conn->commit();
        return $attemptId;
    } catch (Throwable $e) {
        $conn->rollback();
        return null;
    }
}

function update_attempt(mysqli $conn, int $attemptId, int $score, int $total, array $responses): bool
{
    $percentage = $total > 0 ? round(($score / $total) * 100, 2) : 0.00;
    $submittedAt = date('Y-m-d H:i:s');

    $conn->begin_transaction();

    try {
        $attemptStmt = $conn->prepare(
            "UPDATE exam_attempts SET score = ?, percentage = ?, submitted_at = ? WHERE id = ?"
        );
        $attemptStmt->bind_param(
            "idsi",
            $score,
            $percentage,
            $submittedAt,
            $attemptId
        );
        $attemptStmt->execute();

        $delStmt = $conn->prepare("DELETE FROM attempt_answers WHERE attempt_id = ?");
        $delStmt->bind_param("i", $attemptId);
        $delStmt->execute();

        $answerStmt = $conn->prepare(
            "INSERT INTO attempt_answers (attempt_id, question_id, selected_answer, is_correct) VALUES (?, ?, ?, ?)"
        );

        foreach ($responses as $questionId => $response) {
            $questionId = (int)$questionId;
            $selectedAnswer = null;
            $isCorrect = 0;

            if (is_array($response)) {
                $selectedAnswer = isset($response['selected']) ? (string)$response['selected'] : null;
                $isCorrect = !empty($response['is_correct']) ? 1 : 0;
            } else {
                $selectedAnswer = is_null($response) ? null : (string)$response;
            }

            $answerStmt->bind_param("iisi", $attemptId, $questionId, $selectedAnswer, $isCorrect);
            $answerStmt->execute();
        }

        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        return false;
    }
}
?>
