<?php
session_start();
include 'config.php';
require_once 'lib/security.php';

require_login();

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$levelsOrder = ['Easy', 'Medium', 'Hard', 'Advanced', 'Expert'];
$levelValues = ['Easy' => 20, 'Medium' => 45, 'Hard' => 65, 'Advanced' => 85, 'Expert' => 100];
$levelBadges = [
    'Easy' => '🌱 Junior Scholar (Tier I)',
    'Medium' => '🌿 Competent Associate (Tier II)',
    'Hard' => '🌳 Advanced Specialist (Tier III)',
    'Advanced' => '🌋 Elite Architect (Tier IV)',
    'Expert' => '🏆 Grandmaster Technologist (Tier V)'
];

// -------------------------------------------------------------
// State 1: Reset and Initialize a New Adaptive Placement Session
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'initiate' && isset($_POST['subject_id'])) {
    $subjectId = (int)$_POST['subject_id'];
    
    // Reset any existing adaptive quiz session state
    unset($_SESSION['adaptive_subject_id']);
    unset($_SESSION['adaptive_attempt_id']);
    unset($_SESSION['adaptive_qn']);
    unset($_SESSION['adaptive_questions']);
    unset($_SESSION['adaptive_responses']);
    unset($_SESSION['adaptive_correct']);
    unset($_SESSION['adaptive_level']);
    unset($_SESSION['adaptive_history']);
    
    // Fetch subject details
    $subStmt = $conn->prepare("SELECT name FROM subjects WHERE id = ?");
    $subStmt->bind_param("i", $subjectId);
    $subStmt->execute();
    $subRow = $subStmt->get_result()->fetch_assoc();
    
    if ($subRow) {
        // Create an official placement test attempt record
        $verification_key = "CERT-EPP-ADAP-" . uniqid() . "-" . substr(md5($userId . time()), 0, 8);
        $startedAt = date("Y-m-d H:i:s");
        
        $initStmt = $conn->prepare("
            INSERT INTO exam_attempts (verification_key, user_id, subject_id, level, total_questions, score, percentage, started_at, submitted_at, exam_mode, exam_type, proctor_status)
            VALUES (?, ?, ?, 'Medium', 10, 0, 0.00, ?, ?, 'official', 'adaptive', 'completed')
        ");
        
        if ($initStmt) {
            $initStmt->bind_param("siiss", $verification_key, $userId, $subjectId, $startedAt, $startedAt);
            $initStmt->execute();
            $attemptId = (int)$conn->insert_id;
            
            $_SESSION['adaptive_subject_id'] = $subjectId;
            $_SESSION['adaptive_subject_name'] = $subRow['name'];
            $_SESSION['adaptive_attempt_id'] = $attemptId;
            $_SESSION['adaptive_qn'] = 0;
            $_SESSION['adaptive_questions'] = [];
            $_SESSION['adaptive_responses'] = [];
            $_SESSION['adaptive_correct'] = 0;
            $_SESSION['adaptive_level'] = 'Medium';
            $_SESSION['adaptive_history'] = [];
            
            // Get first question (Medium)
            $firstQ = getNextAdaptiveQuestion($conn, $subjectId, 'Medium', []);
            if ($firstQ) {
                $_SESSION['adaptive_questions'][] = $firstQ['id'];
                header("Location: adaptive_quiz.php");
                exit;
            } else {
                $_SESSION['adaptive_error'] = "No questions are available in the question bank for this subject.";
                header("Location: adaptive_quiz.php");
                exit;
            }
        }
    }
    header("Location: adaptive_quiz.php");
    exit;
}

// Helper function to pick next question dynamically
function getNextAdaptiveQuestion($conn, $subjectId, $level, $usedIds) {
    // 1. Target Level
    $query = "SELECT * FROM questions WHERE subject_id = ? AND level = ?";
    if (!empty($usedIds)) {
        $query .= " AND id NOT IN (" . implode(',', array_map('intval', $usedIds)) . ")";
    }
    $query .= " ORDER BY RAND() LIMIT 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $subjectId, $level);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        return $res->fetch_assoc();
    }
    
    // 2. Fallback: Any unused level for this subject
    $queryFallback = "SELECT * FROM questions WHERE subject_id = ?";
    if (!empty($usedIds)) {
        $queryFallback .= " AND id NOT IN (" . implode(',', array_map('intval', $usedIds)) . ")";
    }
    $queryFallback .= " ORDER BY RAND() LIMIT 1";
    
    $stmtFallback = $conn->prepare($queryFallback);
    $stmtFallback->bind_param("i", $subjectId);
    $stmtFallback->execute();
    $resFallback = $stmtFallback->get_result();
    if ($resFallback && $resFallback->num_rows > 0) {
        return $resFallback->fetch_assoc();
    }
    
    return null;
}

// -------------------------------------------------------------
// State 2: Process Answer and Progress Level Transition
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answer'])) {
    $subjectId = (int)$_SESSION['adaptive_subject_id'];
    $attemptId = (int)$_SESSION['adaptive_attempt_id'];
    $qn = (int)$_SESSION['adaptive_qn'];
    $qIds = $_SESSION['adaptive_questions'];
    $currentQId = $qIds[$qn];
    $selectedAns = isset($_POST['ans']) ? (int)$_POST['ans'] : 0;
    
    // Fetch question to check answer
    $qStmt = $conn->prepare("SELECT correct_answer, level FROM questions WHERE id = ?");
    $qStmt->bind_param("i", $currentQId);
    $qStmt->execute();
    $qRow = $qStmt->get_result()->fetch_assoc();
    
    $isCorrect = 0;
    $currentLevel = $_SESSION['adaptive_level'];
    
    if ($qRow) {
        $correctNum = ord(strtoupper($qRow['correct_answer'])) - 64;
        if ($selectedAns === $correctNum) {
            $isCorrect = 1;
            $_SESSION['adaptive_correct']++;
        }
        $currentLevel = $qRow['level'];
    }
    
    // Save to database attempt_answers
    $delStmt = $conn->prepare("DELETE FROM attempt_answers WHERE attempt_id = ? AND question_id = ?");
    $delStmt->bind_param("ii", $attemptId, $currentQId);
    $delStmt->execute();
    
    $insStmt = $conn->prepare("INSERT INTO attempt_answers (attempt_id, question_id, selected_answer, is_correct) VALUES (?, ?, ?, ?)");
    $insStmt->bind_param("iisi", $attemptId, $currentQId, $selectedAns, $isCorrect);
    $insStmt->execute();
    
    // Update attempt history state
    $_SESSION['adaptive_responses'][$currentQId] = $selectedAns;
    $_SESSION['adaptive_history'][] = [
        'qn' => $qn + 1,
        'question_id' => $currentQId,
        'level' => $currentLevel,
        'selected' => $selectedAns,
        'is_correct' => $isCorrect
    ];
    
    // Calculate new difficulty level
    $currentIndex = array_search($currentLevel, $levelsOrder);
    if ($isCorrect === 1) {
        $newIndex = min(4, $currentIndex + 1);
    } else {
        $newIndex = max(0, $currentIndex - 1);
    }
    $newLevel = $levelsOrder[$newIndex];
    $_SESSION['adaptive_level'] = $newLevel;
    
    // Move to next question or finalize
    if ($qn >= 9) {
        // Complete placement test!
        // Calculate dynamic competency placement score
        // Placement rating is average base value of the last 4 questions they encountered
        $lastQs = array_slice($_SESSION['adaptive_history'], -4);
        $totalWeight = 0;
        foreach ($lastQs as $h) {
            $totalWeight += $levelValues[$h['level']];
        }
        $finalPercentage = round($totalWeight / count($lastQs), 2);
        
        // Find placement level title based on score
        $finalLevel = 'Medium';
        if ($finalPercentage < 35) $finalLevel = 'Easy';
        elseif ($finalPercentage < 55) $finalLevel = 'Medium';
        elseif ($finalPercentage < 75) $finalLevel = 'Hard';
        elseif ($finalPercentage < 90) $finalLevel = 'Advanced';
        else $finalLevel = 'Expert';
        
        $finalScore = (int)$_SESSION['adaptive_correct'];
        $submittedAt = date("Y-m-d H:i:s");
        
        // Update exam attempt
        $updStmt = $conn->prepare("
            UPDATE exam_attempts 
            SET score = ?, percentage = ?, level = ?, submitted_at = ?, proctor_status = 'completed' 
            WHERE id = ?
        ");
        $updStmt->bind_param("idssi", $finalScore, $finalPercentage, $finalLevel, $submittedAt, $attemptId);
        $updStmt->execute();
        
        // Unlock badge if Expert or Perfect performance
        if ($finalLevel === 'Expert' || $finalScore === 10) {
            // Find perfect badge ID
            $badgeQ = $conn->query("SELECT id FROM badges WHERE condition_type = 'perfect_score' LIMIT 1");
            if ($badgeQ && $badgeQ->num_rows > 0) {
                $bId = (int)$badgeQ->fetch_assoc()['id'];
                $unlockStmt = $conn->prepare("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?, ?)");
                $unlockStmt->bind_param("ii", $userId, $bId);
                $unlockStmt->execute();
            }
        }
        
        $_SESSION['adaptive_completed'] = true;
        header("Location: adaptive_quiz.php?results=1");
        exit;
    } else {
        // Pick next question
        $nextQ = getNextAdaptiveQuestion($conn, $subjectId, $newLevel, $_SESSION['adaptive_questions']);
        if ($nextQ) {
            $_SESSION['adaptive_questions'][] = $nextQ['id'];
            $_SESSION['adaptive_qn']++;
            header("Location: adaptive_quiz.php");
            exit;
        } else {
            // Unused fallback
            $_SESSION['adaptive_completed'] = true;
            header("Location: adaptive_quiz.php?results=1");
            exit;
        }
    }
}

// -------------------------------------------------------------
// State 3: Display Results or Assessment Screen
// -------------------------------------------------------------
$resultsMode = isset($_GET['results']) && isset($_SESSION['adaptive_completed']);
$quizActive = isset($_SESSION['adaptive_attempt_id']) && !$resultsMode;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Placement Center | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        .adaptive-header {
            text-align: center;
            margin-bottom: 2.5rem;
            color: white;
            animation: fadeIn 0.4s ease;
        }
        .ai-badge {
            background: linear-gradient(90deg, #fbbf24, #f59e0b);
            color: #1e1b4b;
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.4);
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-bottom: 0.75rem;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            color: white;
            animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .subject-opt-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .subject-opt-card:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: #fbbf24;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        .subject-opt-card.selected {
            border-color: #fbbf24;
            background: rgba(245, 158, 11, 0.12);
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.25);
        }
        .subject-opt-radio {
            position: absolute;
            opacity: 0;
        }
        .check-indicator {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .subject-opt-card.selected .check-indicator {
            border-color: #fbbf24;
            background: #fbbf24;
            color: #1e1b4b;
        }
        .pathway-step {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .pathway-badge {
            font-weight: bold;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
        }
        .adaptive-level-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            padding: 0.5rem 1.25rem;
            border-radius: 30px;
            font-size: 0.9rem;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: var(--shadow-sm);
        }
        .level-Easy { background: rgba(34, 197, 94, 0.2); border-color: rgba(34, 197, 94, 0.4); color: #4ade80; }
        .level-Medium { background: rgba(234, 179, 8, 0.2); border-color: rgba(234, 179, 8, 0.4); color: #fef08a; }
        .level-Hard { background: rgba(249, 115, 22, 0.2); border-color: rgba(249, 115, 22, 0.4); color: #fed7aa; }
        .level-Advanced { background: rgba(168, 85, 247, 0.2); border-color: rgba(168, 85, 247, 0.4); color: #e9d5ff; }
        .level-Expert { background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4); color: #fca5a5; }

        .progress-indicator {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.7);
            margin-bottom: 0.5rem;
        }
        .progress-bar-bg {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            height: 8px;
            width: 100%;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .progress-bar-fill {
            background: linear-gradient(90deg, #6366f1, #a855f7);
            height: 100%;
            border-radius: 10px;
            transition: width 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include("modern_header.php"); ?>
    
    <div class="container" style="max-width: 900px;">
        
        <!-- ------------------------------------------------------------- -->
        <!-- STATE A: RESULTS MODE                                         -->
        <!-- ------------------------------------------------------------- -->
        <?php if ($resultsMode): 
            $subjectId = (int)$_SESSION['adaptive_subject_id'];
            $subjectName = $_SESSION['adaptive_subject_name'];
            $attemptId = (int)$_SESSION['adaptive_attempt_id'];
            $score = (int)$_SESSION['adaptive_correct'];
            
            // Fetch final metrics from DB to ensure sync
            $attemptQuery = $conn->prepare("SELECT level, percentage, verification_key FROM exam_attempts WHERE id = ?");
            $attemptQuery->bind_param("i", $attemptId);
            $attemptQuery->execute();
            $attemptRow = $attemptQuery->get_result()->fetch_assoc();
            
            $placementLevel = $attemptRow ? $attemptRow['level'] : 'Medium';
            $placementPercentage = $attemptRow ? $attemptRow['percentage'] : 50.00;
            $placementBadgeName = $levelBadges[$placementLevel] ?? $placementLevel;
            $verKey = $attemptRow ? $attemptRow['verification_key'] : '';
        ?>
            <div class="adaptive-header">
                <span class="ai-badge">🧬 Assessment Complete</span>
                <h1>Your Competency Placement Profile</h1>
                <p style="color: rgba(255,255,255,0.8);">Our AI model has successfully resolved and verified your skill level profile.</p>
            </div>

            <div class="glass-card text-center" style="margin-bottom: 2rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">🎓</div>
                <h3 style="color: white; font-size: 1.5rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($subjectName); ?></h3>
                <p style="color: rgba(255,255,255,0.6); font-size: 0.85rem; font-family: monospace; margin-bottom: 1.5rem;">Verification Key: <?php echo htmlspecialchars($verKey); ?></p>
                
                <div style="background: rgba(255,255,255,0.06); padding: 2rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); margin-bottom: 2rem;">
                    <span style="display: block; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.6); margin-bottom: 0.5rem;">AI Competency Placement Level</span>
                    <h2 style="color: #fbbf24; font-size: 2rem; font-weight: 800; margin: 0 0 1rem 0;"><?php echo $placementBadgeName; ?></h2>
                    <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                        <div>
                            <span style="display: block; font-size: 0.72rem; color: rgba(255,255,255,0.5);">Verified Index Rating</span>
                            <strong style="font-size: 1.1rem; color: white;"><?php echo $placementPercentage; ?>%</strong>
                        </div>
                        <div style="width: 1px; height: 30px; background: rgba(255,255,255,0.15);"></div>
                        <div>
                            <span style="display: block; font-size: 0.72rem; color: rgba(255,255,255,0.5);">Correct Solutions</span>
                            <strong style="font-size: 1.1rem; color: white;"><?php echo $score; ?> / 10</strong>
                        </div>
                    </div>
                </div>

                <div style="text-align: left; margin-bottom: 2rem;">
                    <h4 style="color: white; font-size: 1.1rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">🧬 AI-Driven Assessment Pathway Logs</h4>
                    <?php if (isset($_SESSION['adaptive_history'])): ?>
                        <?php foreach ($_SESSION['adaptive_history'] as $hist): 
                            $badgeClass = 'level-' . $hist['level'];
                            $correctIcon = $hist['is_correct'] === 1 ? '✅ Correct' : '❌ Incorrect';
                            $correctColor = $hist['is_correct'] === 1 ? '#4ade80' : '#f87171';
                        ?>
                            <div class="pathway-step">
                                <div>
                                    <strong style="color: rgba(255,255,255,0.7); margin-right: 0.5rem;">Q<?php echo $hist['qn']; ?>:</strong>
                                    <span class="pathway-badge <?php echo $badgeClass; ?>"><?php echo $hist['level']; ?></span>
                                </div>
                                <span style="font-weight: 600; color: <?php echo $correctColor; ?>; font-size: 0.85rem;"><?php echo $correctIcon; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="skills_passport.php" class="btn" style="flex: 1; background: linear-gradient(90deg, #6366f1, #a855f7); border: none;">🏆 View Skills Passport</a>
                    <a href="subject.php" class="btn btn-secondary" style="flex: 1; border: 1px solid rgba(255,255,255,0.2); background: transparent; color: white;">Return to Dashboard</a>
                </div>
            </div>

        <!-- ------------------------------------------------------------- -->
        <!-- STATE B: QUIZ ACTIVE                                          -->
        <!-- ------------------------------------------------------------- -->
        <?php elseif ($quizActive): 
            $subjectId = (int)$_SESSION['adaptive_subject_id'];
            $subjectName = $_SESSION['adaptive_subject_name'];
            $attemptId = (int)$_SESSION['adaptive_attempt_id'];
            $qn = (int)$_SESSION['adaptive_qn'];
            $qIds = $_SESSION['adaptive_questions'];
            $currentQId = $qIds[$qn];
            
            // Load current question from DB
            $qStmt = $conn->prepare("SELECT * FROM questions WHERE id = ?");
            $qStmt->bind_param("i", $currentQId);
            $qStmt->execute();
            $row = $qStmt->get_result()->fetch_assoc();
            
            $progressPercent = round(($qn / 10) * 100);
            $currentLevel = $_SESSION['adaptive_level'];
        ?>
            <div class="adaptive-header">
                <span class="ai-badge">⚡ AI Adaptive Placement Test</span>
                <h1><?php echo htmlspecialchars($subjectName); ?></h1>
                <p style="color: rgba(255,255,255,0.85); font-size: 0.95rem;">Testing capability profile dynamically by adjusting subsequent question difficulties.</p>
            </div>

            <?php if ($row): ?>
                <form method="post" action="adaptive_quiz.php" id="adaptiveForm">
                    <div class="glass-card">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                            <div class="progress-indicator" style="margin: 0; width: auto;">
                                <span>Question <strong><?php echo ($qn + 1); ?></strong> of <strong>10</strong></span>
                            </div>
                            <div class="adaptive-level-indicator level-<?php echo $currentLevel; ?>">
                                🧠 AI-Estimated Level: <strong><?php echo $currentLevel; ?></strong>
                            </div>
                        </div>

                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: <?php echo $progressPercent; ?>%;"></div>
                        </div>

                        <div style="margin-bottom: 2rem;">
                            <h3 style="color: white; font-size: 1.25rem; line-height: 1.6; font-weight: 600;"><?php echo htmlspecialchars($row['question']); ?></h3>
                        </div>

                        <div class="form-group" style="margin-bottom: 2rem;">
                            <?php
                            $options = [
                                ['id' => 1, 'text' => $row['option_a']],
                                ['id' => 2, 'text' => $row['option_b']],
                                ['id' => 3, 'text' => $row['option_c']],
                                ['id' => 4, 'text' => $row['option_d']]
                            ];
                            foreach ($options as $opt) {
                                echo "
                                <label class='subject-opt-card' id='opt-label-" . $opt['id'] . "' onclick='selectOption(" . $opt['id'] . ")' style='margin-bottom: 1rem; color: white;'>
                                    <span>" . htmlspecialchars($opt['text']) . "</span>
                                    <div class='check-indicator'></div>
                                    <input type='radio' name='ans' value='" . $opt['id'] . "' id='opt-radio-" . $opt['id'] . "' required class='subject-opt-radio'>
                                </label>";
                            }
                            ?>
                        </div>

                        <button type="submit" name="submit_answer" class="btn" style="background: linear-gradient(90deg, #6366f1, #a855f7); border: none; font-size: 1rem; font-weight: 700; padding: 0.9rem;">
                            Submit Response &rarr;
                        </button>
                    </div>
                </form>
                
                <script>
                    function selectOption(id) {
                        // Clear active states
                        document.querySelectorAll('.subject-opt-card').forEach(function(card) {
                            card.classList.remove('selected');
                        });
                        
                        // Select radio button
                        const radio = document.getElementById('opt-radio-' + id);
                        if (radio) radio.checked = true;
                        
                        // Highlight selected card
                        const card = document.getElementById('opt-label-' + id);
                        if (card) card.classList.add('selected');
                    }
                </script>

            <?php else: ?>
                <div class="glass-card text-center">
                    <h2>Error loading placement question</h2>
                    <p style="color: rgba(255,255,255,0.7); margin-bottom: 1.5rem;">Could not load question pool sequence.</p>
                    <a href="subject.php" class="btn">Back to Dashboard</a>
                </div>
            <?php endif; ?>

        <!-- ------------------------------------------------------------- -->
        <!-- STATE C: INITIAL SUBJECT SELECTION                            -->
        <!-- ------------------------------------------------------------- -->
        <?php else: 
            // Load available subjects
            $subQuery = $conn->query("SELECT * FROM subjects ORDER BY name ASC");
            $subjects = [];
            if ($subQuery) {
                while ($sRow = $subQuery->fetch_assoc()) {
                    $subjects[] = $sRow;
                }
            }
        ?>
            <div class="adaptive-header">
                <span class="ai-badge">🧬 Neural Evaluation Engine</span>
                <h1>AI-Driven Placement & Competency Center</h1>
                <p style="color: rgba(255,255,255,0.85); font-size: 0.95rem;">Locate your skills ceiling instantly. The AI will serve harder or easier questions based on your correctness in real-time.</p>
            </div>

            <form method="post" action="adaptive_quiz.php?action=initiate" id="initiationForm">
                <div class="glass-card">
                    <h2 style="color: white; font-size: 1.35rem; margin-bottom: 1.5rem; text-align: center;">Select Assessment Subject</h2>
                    
                    <?php if (isset($_SESSION['adaptive_error'])): ?>
                        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 1rem; color: #fca5a5; font-size: 0.85rem; margin-bottom: 1.5rem; text-align: center;">
                            ⚠️ <?php echo htmlspecialchars($_SESSION['adaptive_error']); unset($_SESSION['adaptive_error']); ?>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;">
                        <?php foreach ($subjects as $s): ?>
                            <label class="subject-opt-card" id="sub-label-<?php echo $s['id']; ?>" onclick="selectSubject(<?php echo $s['id']; ?>)">
                                <div>
                                    <span style="font-size: 1.25rem; margin-right: 0.5rem;">📚</span>
                                    <strong style="font-size: 1rem; font-weight: 600; color: white;"><?php echo htmlspecialchars($s['name']); ?></strong>
                                </div>
                                <div class="check-indicator"></div>
                                <input type="radio" name="subject_id" value="<?php echo $s['id']; ?>" id="sub-radio-<?php echo $s['id']; ?>" required class="subject-opt-radio">
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn" style="background: linear-gradient(90deg, #fbbf24, #f59e0b); color: #1e1b4b; border: none; font-size: 1rem; font-weight: 700; padding: 0.9rem;">
                        Initiate Neural Placement Test &rarr;
                    </button>
                </div>
            </form>

            <script>
                function selectSubject(id) {
                    document.querySelectorAll('.subject-opt-card').forEach(function(card) {
                        card.classList.remove('selected');
                    });
                    
                    const radio = document.getElementById('sub-radio-' + id);
                    if (radio) radio.checked = true;
                    
                    const card = document.getElementById('sub-label-' + id);
                    if (card) card.classList.add('selected');
                }
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
