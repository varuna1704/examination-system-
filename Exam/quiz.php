<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php'; // MySQL connection
include 'QuestionGenerator.php'; // Advanced Question Generator
include 'HybridQuestionManager.php'; // Hybrid System (DB, Generator, API)

$generator = new QuestionGenerator($conn);
$hybridManager = new HybridQuestionManager($conn, $generator);

// Create questions table if not exists (Smart System Behavior)
$conn->query("CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(50),
    level VARCHAR(20),
    question TEXT,
    option_a TEXT,
    option_b TEXT,
    option_c TEXT,
    option_d TEXT,
    correct_answer VARCHAR(1),
    explanation TEXT
)");

$submit = $_POST['submit'] ?? '';
$ans = $_POST['ans'] ?? null;

// DEBUG OPTION: Print subject and level values if requested
if(isset($_GET['debug'])) {
    echo "<div style='background: #333; color: #fff; padding: 1rem;'>";
    echo "DEBUG INFO:<br>";
    echo "Subject: " . ($_GET['subname'] ?? 'NULL') . "<br>";
    echo "Level: " . ($_GET['level'] ?? 'NULL') . "<br>";
    echo "Session Level: " . ($_SESSION['level'] ?? 'NULL') . "<br>";
    echo "</div>";
}

// 1. Handle Level Selection Entry
if(isset($_GET['level']) && (isset($_GET['subject_id']) || isset($_GET['subname'])))
{
    $subject_id = (int)($_GET['subject_id'] ?? 0);
    $subname = $_GET['subname'] ?? '';
    if ($subject_id > 0) {
        $subjectStmt = $conn->prepare("SELECT name FROM subjects WHERE id = ? LIMIT 1");
        $subjectStmt->bind_param("i", $subject_id);
        $subjectStmt->execute();
        $subjectRow = $subjectStmt->get_result()->fetch_assoc();
        if ($subjectRow) {
            $subname = $subjectRow['name'];
        }
    }

    $_SESSION['subject_id'] = $subject_id;
    $_SESSION['subname'] = $subname;
    $_SESSION['level'] = $_GET['level'];
    $_SESSION['exam_mode'] = $_GET['mode'] ?? 'official'; // 'official' or 'mock'
    
    // Set timer
    $durations = ['Easy' => 15, 'Medium' => 20, 'Hard' => 30, 'Advanced' => 45, 'Expert' => 60];
    $_SESSION['end_time'] = time() + ($durations[$_SESSION['level']] * 60);
    
    // Reset Quiz State
    unset($_SESSION['question_ids']);
    unset($_SESSION['qn']);
    unset($_SESSION['attempt_saved']);
    unset($_SESSION['last_attempt_id']);
    unset($_SESSION['error_message']);
    $_SESSION['attempt_started_at'] = date("Y-m-d H:i:s");
    
    header("Location: quiz.php");
    exit;
}

$subject_id = (int)($_SESSION['subject_id'] ?? 0);
$subname = $_SESSION['subname'] ?? '';
$level = $_SESSION['level'] ?? '';
$exam_mode = $_SESSION['exam_mode'] ?? 'official';

// 1.5 Smart Auto-Generation (Hybrid System)
if(!empty($subname) && !empty($level)) {
    $hybridManager->ensureQuestions($subname, $level);
    if ($subject_id <= 0) {
        $subject_id = $generator->getSubjectId($subname);
        $_SESSION['subject_id'] = $subject_id;
    }
}

// 2. Fetch Questions (Randomized, Max 25 per quiz)
if(!isset($_SESSION['question_ids']) && $subject_id > 0) {
    // Canonical schema: fetch by subject_id + level
    $stmt = $conn->prepare("SELECT id FROM questions WHERE subject_id = ? AND level = ? ORDER BY RAND() LIMIT 25");
    $stmt->bind_param("is", $subject_id, $level);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $ids = [];
    while($r = $res->fetch_assoc()) { $ids[] = $r['id']; }
    
    $_SESSION['question_ids'] = $ids;
    $_SESSION['qn'] = 0;
    $_SESSION['true_ans'] = 0;
    $_SESSION['user_responses'] = [];
}

$ids = $_SESSION['question_ids'] ?? [];
$num_rows = count($ids);

// 3. Process Answer & Navigation Submission
$auto_submit = $_POST['auto_submit'] ?? '0';
$goto_qn = isset($_POST['goto_qn']) ? (int)$_POST['goto_qn'] : null;
$prev_question = isset($_POST['prev_question']);
$next_question = isset($_POST['next_question']) || ($submit === 'Next Question');

if (($submit || $auto_submit === '1' || $goto_qn !== null || $prev_question || $next_question) && isset($_SESSION['qn'])) {
    $current_id = $ids[$_SESSION['qn']];
    
    // Store response if provided
    if (isset($_POST['ans']) && $_POST['ans'] !== '') {
        $_SESSION['user_responses'][$current_id] = (int)$_POST['ans'];
    }
    
    // Handle Auto-submit (timer end) or Manual Final Submit
    if ($auto_submit === '1' || $submit === 'Get Result') {
        // If manual submit in official mode, we require all questions to be answered
        if ($auto_submit !== '1' && $exam_mode === 'official') {
            $answered_count = 0;
            foreach ($ids as $qid) {
                if (isset($_SESSION['user_responses'][$qid]) && $_SESSION['user_responses'][$qid] !== null && $_SESSION['user_responses'][$qid] !== '') {
                    $answered_count++;
                }
            }
            if ($answered_count < $num_rows) {
                $_SESSION['error_message'] = "You must answer all questions before submitting the exam. You have answered $answered_count out of $num_rows.";
                header("Location: quiz.php");
                exit;
            }
        }
        
        // Recalculate Final Score
        $score = 0;
        $answerStmt = $conn->prepare("SELECT correct_answer FROM questions WHERE id = ?");
        foreach ($ids as $qid) {
            $selected = $_SESSION['user_responses'][$qid] ?? null;
            if ($selected !== null) {
                $answerStmt->bind_param("i", $qid);
                $answerStmt->execute();
                $correctRow = $answerStmt->get_result()->fetch_assoc();
                if ($correctRow) {
                    $correctNum = ord(strtoupper($correctRow['correct_answer'])) - 64;
                    if ((int)$selected === $correctNum) {
                        $score++;
                    }
                }
            }
        }
        $_SESSION['true_ans'] = $score;
        
        header("Location: result.php");
        exit;
    }
    
    // Process standard navigation
    if ($goto_qn !== null) {
        if ($goto_qn >= 0 && $goto_qn < $num_rows) {
            $_SESSION['qn'] = $goto_qn;
        }
    } elseif ($prev_question) {
        if ($_SESSION['qn'] > 0) {
            $_SESSION['qn']--;
        }
    } elseif ($next_question) {
        if ($_SESSION['qn'] < $num_rows - 1) {
            $_SESSION['qn']++;
        } else {
            // Mock mode sequential final submit auto-triggers result
            if ($exam_mode === 'mock') {
                // Auto-calculate score for mock mode submission
                $score = 0;
                $answerStmt = $conn->prepare("SELECT correct_answer FROM questions WHERE id = ?");
                foreach ($ids as $qid) {
                    $selected = $_SESSION['user_responses'][$qid] ?? null;
                    if ($selected !== null) {
                        $answerStmt->bind_param("i", $qid);
                        $answerStmt->execute();
                        $correctRow = $answerStmt->get_result()->fetch_assoc();
                        if ($correctRow) {
                            $correctNum = ord(strtoupper($correctRow['correct_answer'])) - 64;
                            if ((int)$selected === $correctNum) {
                                $score++;
                            }
                        }
                    }
                }
                $_SESSION['true_ans'] = $score;
                header("Location: result.php");
                exit;
            }
        }
    }
    
    header("Location: quiz.php");
    exit;
}

// 4. Fetch Current Question Data
$row = null;
if($num_rows > 0 && $_SESSION['qn'] < $num_rows) {
    $current_id = $ids[$_SESSION['qn']];
    $stmt = $conn->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->bind_param("i", $current_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
    <script>
        function startTimer(duration, display) {
            var timer = duration, minutes, seconds;
            var interval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);
                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;
                display.textContent = minutes + ":" + seconds;
                if (--timer < 0) {
                    clearInterval(interval);
                    alert("Time is up! Your exam will be submitted automatically.");
                    document.getElementById('auto_submit').value = "1";
                    document.getElementById('quizForm').submit();
                }
            }, 1000);
        }
        
        <?php if ($exam_mode === 'official'): ?>
        var focusWarnings = 0;
        var maxWarnings = 3;
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                focusWarnings++;
                if (focusWarnings >= maxWarnings) {
                    alert("Violations limit exceeded (tab switching detected)! Your exam is being submitted automatically.");
                    document.getElementById('auto_submit').value = "1";
                    document.getElementById('quizForm').submit();
                } else {
                    alert("WARNING: Tab switching is strictly prohibited during the official exam. Violation " + focusWarnings + " of " + maxWarnings + ".");
                }
            }
        });
        <?php endif; ?>

        window.onload = function () {
            var remaining = <?php echo max(0, ($_SESSION['end_time'] ?? time()) - time()); ?>;
            var display = document.querySelector('#time');
            if(display) startTimer(remaining, display);
        };
    </script>
    <style>
        .quiz-layout {
            display: flex;
            gap: 2rem;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            flex-wrap: wrap;
        }
        .main-panel {
            flex: 3;
            min-width: 300px;
        }
        .sidebar-panel {
            flex: 1;
            min-width: 280px;
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            color: var(--gray-800);
            height: fit-content;
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.5rem;
            margin-top: 1.2rem;
        }
        .grid-btn {
            padding: 0.5rem 0;
            border-radius: 6px;
            border: 1px solid var(--gray-300);
            background: white;
            color: var(--gray-700);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .grid-btn:hover {
            border-color: var(--primary);
            background: var(--gray-50);
        }
        .grid-btn.current {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
        }
        .grid-btn.answered {
            background: #dcfce7;
            color: #166534;
            border-color: #bbf7d0;
        }
        .progress-bar-bg {
            background: var(--gray-200);
            border-radius: 10px;
            height: 8px;
            width: 100%;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        .progress-bar-fill {
            background: #22c55e;
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            border-left: 5px solid #ef4444;
            font-size: 0.9rem;
        }
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <?php include("modern_header.php"); ?>
    
    <div class="container">
        <?php if($row): ?>
        <div style="width: 100%; max-width: 1200px; margin: 0 auto 1.5rem auto; display: flex; justify-content: space-between; align-items: center; color: white;">
            <div>
                Subject: <strong><?php echo htmlspecialchars($subname); ?></strong> 
                | Level: <strong><?php echo htmlspecialchars($level); ?></strong>
                | Mode: <strong><?php echo htmlspecialchars(ucfirst($exam_mode)); ?></strong>
            </div>
            <div style="background: rgba(0,0,0,0.3); padding: 0.5rem 1rem; border-radius: 20px; font-family: monospace; font-size: 1.2rem;">
                Time Left: <span id="time">00:00</span>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert-danger" style="max-width: 1200px; margin: 0 auto 1.5rem auto;">
                <?php 
                echo htmlspecialchars($_SESSION['error_message']); 
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if(!$row): ?>
            <div class="card card-lg" style="margin: 0 auto; max-width: 800px; text-align: center;">
                <div class='text-center'>
                    <h2>No Questions Found</h2>
                    <p class='text-muted'>We couldn't find any questions matching <strong><?php echo htmlspecialchars($subname); ?></strong> at <strong><?php echo htmlspecialchars($level); ?></strong> level.</p>
                    <br><a href='subject.php' class='btn'>Back to Dashboard</a>
                </div>
            </div>
        <?php else: 
            // Calculate answered vs remaining
            $answered_count = 0;
            foreach ($ids as $qid) {
                if (isset($_SESSION['user_responses'][$qid]) && $_SESSION['user_responses'][$qid] !== null && $_SESSION['user_responses'][$qid] !== '') {
                    $answered_count++;
                }
            }
            $remaining_count = $num_rows - $answered_count;
            $progress_percent = $num_rows > 0 ? round(($answered_count / $num_rows) * 100) : 0;
            $current_response = $_SESSION['user_responses'][$current_id] ?? null;
        ?>
            <form method="post" action="quiz.php" id="quizForm">
                <input type="hidden" name="auto_submit" id="auto_submit" value="0">
                
                <div class="quiz-layout">
                    <!-- Left main card -->
                    <div class="main-panel">
                        <div class="card card-lg" style="width: 100%; margin: 0;">
                            <div style="margin-bottom: 2rem;">
                                <span class="text-muted">Question <?php echo ($_SESSION['qn'] + 1); ?> of <?php echo $num_rows; ?></span>
                                <h3 style="margin-top: 0.5rem; color: var(--gray-900);"><?php echo htmlspecialchars($row['question']); ?></h3>
                            </div>
                            
                            <div class="form-group">
                                <?php
                                $options = [
                                    ['id' => 1, 'text' => $row['option_a']],
                                    ['id' => 2, 'text' => $row['option_b']],
                                    ['id' => 3, 'text' => $row['option_c']],
                                    ['id' => 4, 'text' => $row['option_d']]
                                ];
                                foreach($options as $opt) {
                                    $checked = ($current_response === $opt['id']) ? 'checked' : '';
                                    // Radio is required only in mock mode to lock sequential answering
                                    $required_attr = ($exam_mode === 'mock') ? 'required' : '';
                                    echo "
                                    <label class='subject-card' style='display: block; text-align: left; padding: 1.2rem; margin-bottom: 1rem; cursor: pointer; border: 2px solid var(--gray-100); border-radius: 10px; transition: all 0.2s;'>
                                        <input type='radio' name='ans' value='".$opt['id']."' $checked $required_attr style='margin-right: 1rem;'> 
                                        ".htmlspecialchars($opt['text'])."
                                    </label>";
                                }
                                ?>
                            </div>
                            
                            <div class="nav-buttons">
                                <div>
                                    <?php if ($exam_mode === 'official' && $_SESSION['qn'] > 0): ?>
                                        <button type="submit" name="prev_question" class="btn btn-secondary">&larr; Previous</button>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ($_SESSION['qn'] < $num_rows - 1): ?>
                                        <button type="submit" name="next_question" class="btn">Next Question &rarr;</button>
                                    <?php else: ?>
                                        <button type="submit" name="submit" value="Get Result" class="btn btn-success" style="background: #166534; border: none;">Submit Examination</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right sidebar (Only in Official mode) -->
                    <?php if ($exam_mode === 'official'): ?>
                        <div class="sidebar-panel">
                            <h3 style="margin-bottom: 0.5rem; font-size: 1.2rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                                📊 Exam Progress
                            </h3>
                            <div style="font-size: 0.9rem; margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span>Answered:</span>
                                    <strong><?php echo $answered_count; ?> / <?php echo $num_rows; ?></strong>
                                </div>
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill" style="width: <?php echo $progress_percent; ?>%;"></div>
                                </div>
                            </div>

                            <hr style="border: 0; border-top: 1px solid var(--gray-200); margin: 1rem 0;">

                            <h4 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 0.75rem; color: var(--gray-600);">Question Navigator</h4>
                            <p style="font-size: 0.75rem; color: var(--gray-500); margin-bottom: 1rem;">Click any number to jump to that question. Your current selection will be auto-saved.</p>
                            
                            <div class="grid-container">
                                <?php for ($i = 0; $i < $num_rows; $i++): 
                                    $btn_class = 'grid-btn';
                                    if ($i === $_SESSION['qn']) {
                                        $btn_class .= ' current';
                                    } elseif (isset($_SESSION['user_responses'][$ids[$i]]) && $_SESSION['user_responses'][$ids[$i]] !== null && $_SESSION['user_responses'][$ids[$i]] !== '') {
                                        $btn_class .= ' answered';
                                    }
                                ?>
                                    <button type="submit" name="goto_qn" value="<?php echo $i; ?>" class="<?php echo $btn_class; ?>">
                                        <?php echo ($i + 1); ?>
                                    </button>
                                <?php endfor; ?>
                            </div>
                            
                            <hr style="border: 0; border-top: 1px solid var(--gray-200); margin: 1.5rem 0;">
                            
                            <button type="submit" name="submit" value="Get Result" class="btn btn-block btn-success" style="width: 100%; background: #166534; border: none; font-size: 0.9rem; padding: 0.75rem;">
                                Submit Exam
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>