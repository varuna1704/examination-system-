<?php
session_start();
include 'config.php';
require_once 'lib/security.php';

header('Content-Type: application/json');

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$attemptId = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;

if ($userId > 0 && $attemptId > 0) {
    $stmt = $conn->prepare("SELECT id, proctor_status, proctor_paused FROM exam_attempts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $attemptId, $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        echo json_encode([
            'status' => 'success',
            'proctor_status' => $row['proctor_status'],
            'proctor_paused' => (int)$row['proctor_paused']
        ]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'msg' => 'Unauthorized or invalid session']);
?>
