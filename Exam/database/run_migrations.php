<?php
require_once __DIR__ . '/../config.php';

echo "Starting migration...\n";

// 1. Rename subject -> subjects, check if subjects exists first
$result = $conn->query("SHOW TABLES LIKE 'subjects'");
if ($result->num_rows == 0) {
    echo "Renaming subject to subjects...\n";
    if (!$conn->query("RENAME TABLE subject TO subjects")) {
        die("Error renaming subject to subjects: " . $conn->error . "\n");
    }
}

// 2. Change columns in subjects
echo "Modifying subjects table...\n";
$conn->query("ALTER TABLE subjects CHANGE sub_id id INT NOT NULL AUTO_INCREMENT");
$conn->query("ALTER TABLE subjects CHANGE sub_name name VARCHAR(120) NOT NULL");
$conn->query("ALTER TABLE subjects ADD UNIQUE KEY uq_subjects_name (name)");

// 3. Add subject_id column to questions if not exists
$result = $conn->query("SHOW COLUMNS FROM questions LIKE 'subject_id'");
if ($result->num_rows == 0) {
    echo "Adding subject_id to questions...\n";
    if (!$conn->query("ALTER TABLE questions ADD COLUMN subject_id INT NULL AFTER id")) {
        die("Error adding subject_id: " . $conn->error . "\n");
    }
}

// 4. Populate subject_id from the text subject column with mapping
echo "Populating subject_id...\n";
$conn->query("UPDATE questions q
JOIN subjects s ON (
    LOWER(s.name) = LOWER(q.subject) OR
    (q.subject = 'Java' AND s.name = 'Java Programming Language') OR
    (q.subject = 'Python' AND s.name = 'Python Programming Language') OR
    (q.subject = 'PHP' AND s.name = 'PHP Programming Language') OR
    (q.subject = 'C' AND s.name = 'C Language') OR
    (q.subject = 'DS' AND s.name = 'Data Structure')
)
SET q.subject_id = s.id");

// Check if any unmatched rows remain
$result = $conn->query("SELECT COUNT(*) AS unmatched FROM questions WHERE subject_id IS NULL");
$unmatched = $result->fetch_assoc()['unmatched'];
if ($unmatched > 0) {
    echo "Warning: There are $unmatched unmatched questions where subject_id is NULL!\n";
} else {
    echo "All questions mapped successfully!\n";
}

// 5. Modify subject_id to NOT NULL
echo "Modifying subject_id to NOT NULL...\n";
$conn->query("ALTER TABLE questions MODIFY subject_id INT NOT NULL");

// Add foreign key if not exists
$result = $conn->query("SHOW KEYS FROM questions WHERE Key_name = 'fk_questions_subject'");
if ($result->num_rows == 0) {
    echo "Adding foreign key fk_questions_subject...\n";
    $conn->query("ALTER TABLE questions ADD CONSTRAINT fk_questions_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE");
}

// 6. Drop the old text column subject if it exists
$result = $conn->query("SHOW COLUMNS FROM questions LIKE 'subject'");
if ($result->num_rows > 0) {
    echo "Dropping old 'subject' column...\n";
    $conn->query("ALTER TABLE questions DROP COLUMN subject");
}

// 7. Add other missing columns to questions
echo "Adding missing columns to questions...\n";
$conn->query("ALTER TABLE questions ADD COLUMN IF NOT EXISTS type ENUM('MCQ','TRUE_FALSE','SHORT_ANSWER') NOT NULL DEFAULT 'MCQ'");
$conn->query("ALTER TABLE questions ADD COLUMN IF NOT EXISTS explanation TEXT NULL");
$conn->query("ALTER TABLE questions ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// Change type of level column to ENUM
echo "Modifying 'level' column in questions to ENUM...\n";
$conn->query("ALTER TABLE questions MODIFY COLUMN level ENUM('Easy','Medium','Hard','Advanced','Expert') NOT NULL");

// 8. Create exam_attempts and attempt_answers tables if not exist
echo "Creating exam_attempts and attempt_answers tables...\n";
$conn->query("CREATE TABLE IF NOT EXISTS exam_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  subject_id INT NOT NULL,
  level ENUM('Easy','Medium','Hard','Advanced','Expert') NOT NULL,
  total_questions INT NOT NULL,
  score INT NOT NULL DEFAULT 0,
  percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  started_at DATETIME NOT NULL,
  submitted_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
)");

// Check and add exam_mode column if not exists
$modeCheck = $conn->query("SHOW COLUMNS FROM exam_attempts LIKE 'exam_mode'");
if ($modeCheck->num_rows == 0) {
    echo "Adding exam_mode column to exam_attempts table...\n";
    $conn->query("ALTER TABLE exam_attempts ADD COLUMN exam_mode ENUM('official','mock') NOT NULL DEFAULT 'official'");
}

$conn->query("CREATE TABLE IF NOT EXISTS attempt_answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  attempt_id INT NOT NULL,
  question_id INT NOT NULL,
  selected_answer VARCHAR(255) NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
)");

echo "Migration finished successfully!\n";
?>
