-- 002_legacy_to_canonical.sql
-- Migrates legacy schema:
--   subject(sub_id, sub_name)
--   questions(subject text)
-- to canonical schema:
--   subjects(id, name)
--   questions(subject_id FK) plus canonical columns
--
-- Manual recovery note:
-- If anything fails before COMMIT, run:
--   ROLLBACK;

START TRANSACTION;

-- Step 1: rename subject -> subjects, sub_id -> id, sub_name -> name
ALTER TABLE subject RENAME TO subjects;
ALTER TABLE subjects CHANGE sub_id id INT NOT NULL AUTO_INCREMENT;
ALTER TABLE subjects CHANGE sub_name name VARCHAR(120) NOT NULL;
ALTER TABLE subjects ADD UNIQUE KEY uq_subjects_name (name);

-- Step 2: add subject_id column to questions (nullable first, fill then constrain)
ALTER TABLE questions ADD COLUMN subject_id INT NULL AFTER id;

-- Step 3: populate subject_id from the text subject column
UPDATE questions q
JOIN subjects s ON s.name = q.subject
SET q.subject_id = s.id;

-- Step 4: verify no NULLs remain before constraining
SELECT COUNT(*) AS unmatched FROM questions WHERE subject_id IS NULL;
SET @unmatched := (SELECT COUNT(*) FROM questions WHERE subject_id IS NULL);
SET @validate_sql := IF(
    @unmatched > 0,
    'SIGNAL SQLSTATE ''45000'' SET MESSAGE_TEXT = ''Unmatched subject names found - aborting migration''',
    'SELECT ''Subject mapping validated'' AS migration_status'
);
PREPARE validate_stmt FROM @validate_sql;
EXECUTE validate_stmt;
DEALLOCATE PREPARE validate_stmt;

-- Step 5: make subject_id NOT NULL and add FK
ALTER TABLE questions MODIFY subject_id INT NOT NULL;
ALTER TABLE questions
    ADD CONSTRAINT fk_questions_subject
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE;

-- Step 6: drop the old text column
ALTER TABLE questions DROP COLUMN subject;

-- Step 7: add any missing columns from canonical schema if not present
ALTER TABLE questions
    ADD COLUMN IF NOT EXISTS type ENUM('MCQ','TRUE_FALSE','SHORT_ANSWER') NOT NULL DEFAULT 'MCQ',
    ADD COLUMN IF NOT EXISTS explanation TEXT NULL,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Step 8: create exam_attempts and attempt_answers if they do not exist yet
CREATE TABLE IF NOT EXISTS exam_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  subject_id INT NOT NULL,
  level ENUM('Easy','Medium','Hard','Advanced','Expert') NOT NULL,
  total_questions INT NOT NULL,
  score INT NOT NULL DEFAULT 0,
  percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  started_at DATETIME NOT NULL,
  submitted_at DATETIME NOT NULL,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS attempt_answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  attempt_id INT NOT NULL,
  question_id INT NOT NULL,
  selected_answer VARCHAR(255) NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

COMMIT;

