<?php
require_once 'lib/security.php';
require_login();

// Restrict access to teachers and admins only
if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: subject.php");
    exit;
}

require 'config.php';

$success_msg = '';
$error_msg = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error_msg = "Security validation failed. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_question' || $action === 'edit_question') {
            $q_id = (int)($_POST['question_id'] ?? 0);
            $subject_id = (int)($_POST['subject_id'] ?? 0);
            $level = trim($_POST['level'] ?? '');
            $type = trim($_POST['type'] ?? 'MCQ');
            $question = trim($_POST['question'] ?? '');
            $option_a = trim($_POST['option_a'] ?? '');
            $option_b = trim($_POST['option_b'] ?? '');
            $option_c = trim($_POST['option_c'] ?? '');
            $option_d = trim($_POST['option_d'] ?? '');
            $correct_answer = trim($_POST['correct_answer'] ?? '');
            $explanation = trim($_POST['explanation'] ?? '');

            // Validation
            $allowed_levels = ['Easy', 'Medium', 'Hard', 'Advanced', 'Expert'];
            $allowed_types = ['MCQ', 'TRUE_FALSE', 'SHORT_ANSWER'];

            if ($subject_id <= 0 || !in_array($level, $allowed_levels) || !in_array($type, $allowed_types) || empty($question) || empty($correct_answer)) {
                $error_msg = "All fields except options/explanations (depending on question type) are required.";
            } else {
                if ($action === 'add_question') {
                    $stmt = $conn->prepare("INSERT INTO questions (subject_id, level, type, question, option_a, option_b, option_c, option_d, correct_answer, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssssssss", $subject_id, $level, $type, $question, $option_a, $option_b, $option_c, $option_d, $correct_answer, $explanation);
                    if ($stmt->execute()) {
                        $success_msg = "Successfully added new question to the bank!";
                    } else {
                        $error_msg = "Database error: Failed to add question.";
                    }
                } else {
                    $stmt = $conn->prepare("UPDATE questions SET subject_id = ?, level = ?, type = ?, question = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_answer = ?, explanation = ? WHERE id = ?");
                    $stmt->bind_param("isssssssssi", $subject_id, $level, $type, $question, $option_a, $option_b, $option_c, $option_d, $correct_answer, $explanation, $q_id);
                    if ($stmt->execute()) {
                        $success_msg = "Successfully updated question details!";
                    } else {
                        $error_msg = "Database error: Failed to update question.";
                    }
                }
            }
        } elseif ($action === 'delete_question') {
            $q_id = (int)($_POST['question_id'] ?? 0);
            if ($q_id > 0) {
                $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
                $stmt->bind_param("i", $q_id);
                if ($stmt->execute()) {
                    $success_msg = "Question successfully removed from the question bank.";
                } else {
                    $error_msg = "Database error: Failed to delete question.";
                }
            }
        }
    }
}

// Fetch subjects for drop-downs
$subjects_query = $conn->query("SELECT id, name FROM subjects ORDER BY name ASC");
$subjects = [];
while ($row = $subjects_query->fetch_assoc()) {
    $subjects[] = $row;
}

// Fetch current question pool
$questions_query = $conn->query("
    SELECT q.id, q.question, q.level, q.type, q.correct_answer, q.option_a, q.option_b, q.option_c, q.option_d, q.explanation, s.name as subject_name, s.id as subject_id 
    FROM questions q
    JOIN subjects s ON s.id = q.subject_id
    ORDER BY q.created_at DESC
");
$questions = [];
while ($row = $questions_query->fetch_assoc()) {
    $questions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Editor Panel | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        .questions-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        @media (min-width: 992px) {
            .questions-row {
                grid-template-columns: 1.2fr 2fr;
            }
        }
    </style>
</head>
<body>
    <?php include("modern_header.php"); ?>

    <div class="container" style="animation: fadeIn 0.4s ease;">
        <div style="margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="color: var(--white); margin-bottom: 0.25rem;">Question Bank Editor</h1>
                <p style="color: rgba(255,255,255,0.85); font-size: 0.95rem;">Curate, edit, and audit assessment questions dynamically.</p>
            </div>
            <a href="admin_dashboard.php" class="btn btn-inline" style="background: rgba(255,255,255,0.2); color: var(--white); border: 1px solid rgba(255,255,255,0.4); font-size: 0.9rem;">
                &larr; Back to Admin Center
            </a>
        </div>

        <!-- Feedback Messages -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">
                <span>✅</span>
                <div><strong>Success!</strong> <?php echo e($success_msg); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error">
                <span>⚠️</span>
                <div><strong>Failed!</strong> <?php echo e($error_msg); ?></div>
            </div>
        <?php endif; ?>

        <div class="questions-row">
            <!-- Left Side: Form Panel (Add / Edit) -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 id="formTitle">Add New Question</h2>
                    <button type="button" class="btn btn-sm-danger" id="resetBtn" style="display: none; padding: 0.25rem 0.5rem; font-size: 0.75rem; background: var(--gray-600);" onclick="resetForm()">Cancel Edit</button>
                </div>

                <form action="" method="post" id="questionForm">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="action" id="formAction" value="add_question">
                    <input type="hidden" name="question_id" id="questionId" value="">

                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <select name="subject_id" id="subjectIdSelect" class="role-select" style="width: 100%; padding: 0.75rem 1rem;" required>
                            <option value="">Select subject...</option>
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?php echo $sub['id']; ?>"><?php echo e($sub['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-grid" style="grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Difficulty Level</label>
                            <select name="level" id="levelSelect" class="role-select" style="width: 100%;" required>
                                <option value="Easy">Easy</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="Hard">Hard</option>
                                <option value="Advanced">Advanced</option>
                                <option value="Expert">Expert</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Question Type</label>
                            <select name="type" id="typeSelect" class="role-select" style="width: 100%;" onchange="toggleOptionFields()" required>
                                <option value="MCQ" selected>MCQ (Multiple Choice)</option>
                                <option value="TRUE_FALSE">True / False</option>
                                <option value="SHORT_ANSWER">Short / Subjective</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Question Text</label>
                        <textarea name="question" id="questionText" class="form-control" placeholder="Type question content here..." required style="height: 100px; resize: vertical; font-family: monospace;"></textarea>
                    </div>

                    <!-- MCQ Option blocks -->
                    <div id="optionsContainer">
                        <div class="form-grid" style="grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Option A</label>
                                <input type="text" name="option_a" id="optA" class="form-control" placeholder="Option A value">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Option B</label>
                                <input type="text" name="option_b" id="optB" class="form-control" placeholder="Option B value">
                            </div>
                        </div>
                        <div class="form-grid" style="grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Option C</label>
                                <input type="text" name="option_c" id="optC" class="form-control" placeholder="Option C value">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Option D</label>
                                <input type="text" name="option_d" id="optD" class="form-control" placeholder="Option D value">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Correct Answer</label>
                        <input type="text" name="correct_answer" id="correctAnswer" class="form-control" placeholder="A, B, C, D (or True/False or explicit subjective keywords)" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Explanation</label>
                        <textarea name="explanation" id="explanationText" class="form-control" placeholder="Add an analytical explanation of the answer..." style="height: 80px; resize: vertical;"></textarea>
                    </div>

                    <button type="submit" id="submitBtn" class="btn" style="margin-top: 1rem;">Add Question</button>
                </form>
            </div>

            <!-- Right Side: Directory Panel (List with Filters & Search) -->
            <div class="admin-card" style="display: flex; flex-direction: column;">
                <div class="admin-card-header">
                    <h2>Question Pool Directory</h2>
                    <span style="font-size: 0.85rem; color: var(--gray-600); font-weight: 600;">
                        Pool Size: <strong><?php echo count($questions); ?></strong>
                    </span>
                </div>

                <!-- Instant client-side search box -->
                <div class="search-container">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="questionSearch" class="search-input" placeholder="Search by question description, answers, or subject name..." onkeyup="filterQuestions()">
                </div>

                <!-- Question Pool Table -->
                <div class="admin-table-container" style="max-height: 700px; overflow-y: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 70px;">ID</th>
                                <th>Subject & Level</th>
                                <th>Type</th>
                                <th>Question Snippet</th>
                                <th>Answer Key</th>
                                <th style="text-align: center; width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="questionTableBody">
                            <?php if (empty($questions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted" style="padding: 3rem;">The question database is currently empty. Add one using the form!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($questions as $q): 
                                    $badgeStyle = 'badge-student';
                                    if ($q['level'] === 'Hard') $badgeStyle = 'badge-teacher';
                                    elseif (in_array($q['level'], ['Advanced', 'Expert'])) $badgeStyle = 'badge-admin';
                                ?>
                                    <tr class="q-row" data-json="<?php echo e(json_encode($q)); ?>">
                                        <td style="font-weight: 600; color: var(--gray-600);"><?php echo $q['id']; ?></td>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo e($q['subject_name']); ?></div>
                                            <span class="badge <?php echo $badgeStyle; ?>" style="font-size: 0.7rem; padding: 0.15rem 0.4rem; margin-top: 0.25rem;"><?php echo $q['level']; ?></span>
                                        </td>
                                        <td style="font-family: monospace; font-size: 0.75rem; font-weight: bold;"><?php echo $q['type']; ?></td>
                                        <td>
                                            <div style="font-family: monospace; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.85rem;" title="<?php echo e($q['question']); ?>">
                                                <?php echo e($q['question']); ?>
                                            </div>
                                        </td>
                                        <td style="font-family: monospace; font-weight: bold; color: #10b981;"><?php echo e($q['correct_answer']); ?></td>
                                        <td style="text-align: center;">
                                            <div style="display: flex; gap: 0.4rem; justify-content: center;">
                                                <button type="button" class="btn" style="padding: 0.35rem 0.6rem; font-size: 0.75rem; width: auto; background: var(--secondary);" onclick="loadEdit(this)">Edit</button>
                                                <form action="" method="post" style="margin:0;" onsubmit="return confirm('Are you sure you want to permanently delete this question?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                                    <input type="hidden" name="action" value="delete_question">
                                                    <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                                                    <button type="submit" class="btn-sm-danger" style="padding: 0.35rem 0.6rem;">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Filters question list in real-time
        function filterQuestions() {
            const query = document.getElementById('questionSearch').value.toLowerCase();
            const rows = document.querySelectorAll('.q-row');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Toggles display of option fields based on selected Question Type
        function toggleOptionFields() {
            const type = document.getElementById('typeSelect').value;
            const container = document.getElementById('optionsContainer');
            const correctAnswer = document.getElementById('correctAnswer');

            if (type === 'MCQ') {
                container.style.display = 'block';
                correctAnswer.placeholder = 'A, B, C, or D';
            } else if (type === 'TRUE_FALSE') {
                container.style.display = 'none';
                correctAnswer.placeholder = 'True or False';
            } else {
                container.style.display = 'none';
                correctAnswer.placeholder = 'Type subjective answer key...';
            }
        }

        // Loads a question's data into the form for editing
        function loadEdit(button) {
            const row = button.closest('tr');
            const data = JSON.parse(row.getAttribute('data-json'));

            document.getElementById('formTitle').innerText = 'Edit Question ID: ' + data.id;
            document.getElementById('formAction').value = 'edit_question';
            document.getElementById('questionId').value = data.id;
            document.getElementById('subjectIdSelect').value = data.subject_id;
            document.getElementById('levelSelect').value = data.level;
            document.getElementById('typeSelect').value = data.type;
            document.getElementById('questionText').value = data.question;
            
            document.getElementById('optA').value = data.option_a || '';
            document.getElementById('optB').value = data.option_b || '';
            document.getElementById('optC').value = data.option_c || '';
            document.getElementById('optD').value = data.option_d || '';
            
            document.getElementById('correctAnswer').value = data.correct_answer;
            document.getElementById('explanationText').value = data.explanation || '';

            document.getElementById('submitBtn').innerText = 'Update Question Details';
            document.getElementById('resetBtn').style.display = 'inline-block';
            
            toggleOptionFields();
            window.scrollTo({ top: document.getElementById('questionForm').offsetTop - 50, behavior: 'smooth' });
        }

        // Resets the editing form back to Add Question mode
        function resetForm() {
            document.getElementById('formTitle').innerText = 'Add New Question';
            document.getElementById('formAction').value = 'add_question';
            document.getElementById('questionId').value = '';
            document.getElementById('questionForm').reset();
            
            document.getElementById('submitBtn').innerText = 'Add Question';
            document.getElementById('resetBtn').style.display = 'none';
            toggleOptionFields();
        }
    </script>
</body>
</html>
