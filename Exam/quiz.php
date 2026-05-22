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
    
    // Set timer
    $durations = ['Easy' => 15, 'Medium' => 20, 'Hard' => 30, 'Advanced' => 45, 'Expert' => 60];
    $_SESSION['end_time'] = time() + ($durations[$_SESSION['level']] * 60);
    
    // Reset Quiz State
    unset($_SESSION['question_ids']);
    unset($_SESSION['qn']);
    unset($_SESSION['attempt_saved']);
    unset($_SESSION['last_attempt_id']);
    $_SESSION['attempt_started_at'] = date("Y-m-d H:i:s");
    
    header("Location: quiz.php");
    exit;
}

$subject_id = (int)($_SESSION['subject_id'] ?? 0);
$subname = $_SESSION['subname'] ?? '';
$level = $_SESSION['level'] ?? '';

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
    
    // Clear previous answers (Optional: handle with session or temp table)
    $_SESSION['user_responses'] = [];
}

$ids = $_SESSION['question_ids'] ?? [];
$num_rows = count($ids);

// 3. Process Answer Submission
if($submit && isset($_SESSION['qn'])) {
    $current_id = $ids[$_SESSION['qn']];
    
    // Store response
    $_SESSION['user_responses'][$current_id] = $ans;
    
    // Check if correct
    $stmt = $conn->prepare("SELECT correct_answer FROM questions WHERE id = ?");
    $stmt->bind_param("i", $current_id);
    $stmt->execute();
    $correct = $stmt->get_result()->fetch_assoc()['correct_answer'];
    
    // Convert 'A', 'B', 'C', 'D' to 1, 2, 3, 4 for compatibility
    $correct_num = ord(strtoupper($correct)) - 64; 
    
    if($ans == $correct_num) {
        $_SESSION['true_ans']++;
    }

    $_SESSION['qn']++;

    if($_SESSION['qn'] >= $num_rows || $submit == 'Get Result') {
        header("Location: result.php");
        exit;
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
                    document.getElementById('quizForm').submit();
                }
            }, 1000);
        }
        window.onload = function () {
            var remaining = <?php echo max(0, ($_SESSION['end_time'] ?? time()) - time()); ?>;
            var display = document.querySelector('#time');
            if(display) startTimer(remaining, display);
        };
    </script>
</head>
<body>
    <?php include("modern_header.php"); ?>
    
    <div class="container flex-center" style="flex-direction: column;">
        <?php if($row): ?>
        <div style="width: 100%; max-width: 800px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; color: white;">
            <div>Subject: <strong><?php echo htmlspecialchars($subname); ?></strong> | Level: <strong><?php echo htmlspecialchars($level); ?></strong></div>
            <div style="background: rgba(0,0,0,0.3); padding: 0.5rem 1rem; border-radius: 20px; font-family: monospace; font-size: 1.2rem;">
                Time Left: <span id="time">00:00</span>
            </div>
        </div>
        <?php endif; ?>

        <div class="card card-lg">
        <?php
        if(!$row) {
            echo "<div class='text-center'>";
            echo "<h2>No Questions Found</h2>";
            echo "<p class='text-muted'>We couldn't find any questions matching <strong>$subname</strong> at <strong>$level</strong> level.</p>";
            echo "<p style='font-size: 0.8rem; color: #999;'>Try checking your database or add 'debug' to URL for more info.</p>";
            echo "<br><a href='subject.php' class='btn'>Back to Dashboard</a>";
            echo "</div>";
        } else {
            $n = $_SESSION['qn'] + 1;
            echo "<div style='margin-bottom: 2rem;'><span class='text-muted'>Question $n of $num_rows</span><h3 style='margin-top: 0.5rem;'>".htmlspecialchars($row['question'])."</h3></div>";
            
            echo "<form method='post' action='quiz.php' id='quizForm'>";
            echo "<div class='form-group'>";
            $options = [
                ['id' => 1, 'text' => $row['option_a']],
                ['id' => 2, 'text' => $row['option_b']],
                ['id' => 3, 'text' => $row['option_c']],
                ['id' => 4, 'text' => $row['option_d']]
            ];
            // Optional: Shuffle options here if desired
            foreach($options as $opt) {
                echo "<label class='subject-card' style='display: block; text-align: left; padding: 1rem; margin-bottom: 1rem; cursor: pointer; border: 2px solid var(--gray-100);'>";
                echo "<input type='radio' name='ans' value='".$opt['id']."' required style='margin-right: 1rem;'> ".htmlspecialchars($opt['text'])."</label>";
            }
            echo "</div>";
            
            if($_SESSION['qn'] < $num_rows - 1) {
                echo "<button type='submit' name='submit' value='Next Question' class='btn'>Next Question &rarr;</button>";
            } else {
                echo "<button type='submit' name='submit' value='Get Result' class='btn btn-secondary'>Submit Examination</button>";
            }
            echo "</form>";
        }
        ?>
        </div>
    </div>
</body>
</html>