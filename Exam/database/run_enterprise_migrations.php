<?php
require_once __DIR__ . '/../config.php';

echo "Starting Enterprise LMS Extensions Migration...\n";

// 1. Create Cohorts table
echo "Creating cohorts table...\n";
$conn->query("CREATE TABLE IF NOT EXISTS cohorts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Create Cohort Members table
echo "Creating cohort_members table...\n";
$conn->query("CREATE TABLE IF NOT EXISTS cohort_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cohort_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cohort_id) REFERENCES cohorts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_cohort_user (cohort_id, user_id)
)");

// 3. Create Subject Cohorts Scheduled Releases table
echo "Creating subject_cohorts table...\n";
$conn->query("CREATE TABLE IF NOT EXISTS subject_cohorts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    cohort_id INT NOT NULL,
    opens_at DATETIME NULL,
    closes_at DATETIME NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (cohort_id) REFERENCES cohorts(id) ON DELETE CASCADE,
    UNIQUE KEY uq_subject_cohort (subject_id, cohort_id)
)");

// 4. Create Badges table
echo "Creating badges table...\n";
$conn->query("CREATE TABLE IF NOT EXISTS badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    icon VARCHAR(10) NOT NULL,
    condition_type VARCHAR(50) NOT NULL,
    condition_value VARCHAR(100) NOT NULL
)");

// 5. Create User Badges table
echo "Creating user_badges table...\n";
$conn->query("CREATE TABLE IF NOT EXISTS user_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_badge (user_id, badge_id)
)");

// 6. Alter Exam Attempts for verification and proctor controls
echo "Altering exam_attempts table for proctor & verification data...\n";
$conn->query("ALTER TABLE exam_attempts ADD COLUMN IF NOT EXISTS verification_key VARCHAR(100) UNIQUE AFTER id");
$conn->query("ALTER TABLE exam_attempts ADD COLUMN IF NOT EXISTS exam_type ENUM('official', 'mock', 'adaptive') NOT NULL DEFAULT 'official' AFTER exam_mode");
$conn->query("ALTER TABLE exam_attempts ADD COLUMN IF NOT EXISTS proctor_status ENUM('monitoring', 'warning_1', 'warning_2', 'suspended', 'completed') NOT NULL DEFAULT 'monitoring' AFTER exam_type");
$conn->query("ALTER TABLE exam_attempts ADD COLUMN IF NOT EXISTS proctor_paused TINYINT(1) NOT NULL DEFAULT 0 AFTER proctor_status");
$conn->query("ALTER TABLE exam_attempts ADD COLUMN IF NOT EXISTS time_remaining_sec INT NOT NULL DEFAULT 3600 AFTER proctor_paused");

// 7. Seed baseline achievements / badges if empty
$checkBadges = $conn->query("SELECT COUNT(*) as count FROM badges");
if ($checkBadges && $checkBadges->fetch_assoc()['count'] == 0) {
    echo "Seeding default Achievements/Badges...\n";
    $defaultBadges = [
        ['Java Artisan', 'Pass any Official Exam under the Java Programming Language subject track.', '☕', 'subject_pass', 'Java Programming Language'],
        ['Python Specialist', 'Pass any Official Exam under the Python Programming Language subject track.', '🐍', 'subject_pass', 'Python Programming Language'],
        ['PHP Master', 'Pass any Official Exam under the PHP Programming Language subject track.', '🐘', 'subject_pass', 'PHP Programming Language'],
        ['Structure Expert', 'Pass any Official Exam under the Data Structure subject track.', '📊', 'subject_pass', 'Data Structure'],
        ['Turbo Speedster', 'Pass any Official Exam in record time (completed in less than 5 minutes / 300 seconds).', '⚡', 'speed_run', '300'],
        ['Absolute Perfection', 'Attain a flawless 100% accuracy score on any Official Certification Exam.', '🏆', 'perfect_score', '100'],
        ['High Achiever', 'Showcase continuous learning by completing at least 5 certification/exam attempts.', '📚', 'attempts_count', '5']
    ];

    $stmt = $conn->prepare("INSERT INTO badges (name, description, icon, condition_type, condition_value) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        foreach ($defaultBadges as $badge) {
            $stmt->bind_param("sssss", $badge[0], $badge[1], $badge[2], $badge[3], $badge[4]);
            $stmt->execute();
        }
        echo "Successfully seeded default Achievements!\n";
    }
}

// 8. Seed default baseline Cohort groups
$checkCohorts = $conn->query("SELECT COUNT(*) as count FROM cohorts");
if ($checkCohorts && $checkCohorts->fetch_assoc()['count'] == 0) {
    echo "Seeding default Cohort groups...\n";
    $defaultCohorts = [
        'Computer Science - Section A',
        'Computer Science - Section B',
        'Information Technology - Cohort 2026',
        'Advanced Software Engineering'
    ];
    $stmt = $conn->prepare("INSERT INTO cohorts (name) VALUES (?)");
    if ($stmt) {
        foreach ($defaultCohorts as $cohort) {
            $stmt->bind_param("s", $cohort);
            $stmt->execute();
        }
        echo "Successfully seeded default Cohorts!\n";
    }
}

// 9. Assign some existing users to default cohorts
$users = $conn->query("SELECT id FROM users LIMIT 50");
$cohorts = $conn->query("SELECT id FROM cohorts");
if ($users && $cohorts && $cohorts->num_rows > 0) {
    $cohortList = [];
    while($c = $cohorts->fetch_assoc()) {
        $cohortList[] = (int)$c['id'];
    }
    
    $stmt = $conn->prepare("INSERT IGNORE INTO cohort_members (cohort_id, user_id) VALUES (?, ?)");
    if ($stmt) {
        $i = 0;
        while ($u = $users->fetch_assoc()) {
            $c_id = $cohortList[$i % count($cohortList)];
            $u_id = (int)$u['id'];
            $stmt->bind_param("ii", $c_id, $u_id);
            $stmt->execute();
            $i++;
        }
        echo "Assigned initial 50 students to cohorts randomly for mock directory data.\n";
    }
}

// 10. Generate and update historical verification_key codes if NULL
echo "Assigning retroactive verification keys to past attempts...\n";
$attempts = $conn->query("SELECT id, user_id FROM exam_attempts WHERE verification_key IS NULL");
if ($attempts && $attempts->num_rows > 0) {
    $updateStmt = $conn->prepare("UPDATE exam_attempts SET verification_key = ? WHERE id = ?");
    if ($updateStmt) {
        $k = 0;
        while ($att = $attempts->fetch_assoc()) {
            $att_id = (int)$att['id'];
            $uid = (int)$att['user_id'];
            $vkey = "CERT-EPP-" . $att_id . "-" . substr(md5($uid . $att_id . "salt123"), 0, 8);
            $updateStmt->bind_param("si", $vkey, $att_id);
            $updateStmt->execute();
            $k++;
        }
        echo "Retroactively populated {$k} validation keys for existing certificate verification tests.\n";
    }
}

echo "Enterprise LMS Extensions Migration finished successfully!\n";
?>
