<?php
require_once __DIR__ . '/../../config.php';

echo "Seeding candidate (student) dataset and exam attempts...\n";

// List of realistic Indian and global candidates
$candidates = [
    ['f_name' => 'Aarav', 'm_name' => 'Kumar', 'l_name' => 'Sharma', 'u_name' => 'aarav_sharma', 'u_email' => 'aarav.sharma@example.com', 'age' => 20, 'mob' => '9876543210', 'adr' => 'Mumbai, Maharashtra'],
    ['f_name' => 'Diya', 'm_name' => '', 'l_name' => 'Patel', 'u_name' => 'diya_patel', 'u_email' => 'diya.patel@example.com', 'age' => 21, 'mob' => '9123456789', 'adr' => 'Ahmedabad, Gujarat'],
    ['f_name' => 'Kabir', 'm_name' => 'Singh', 'l_name' => 'Mehta', 'u_name' => 'kabir_mehta', 'u_email' => 'kabir.mehta@example.com', 'age' => 22, 'mob' => '9887766554', 'adr' => 'New Delhi, Delhi'],
    ['f_name' => 'Ananya', 'm_name' => '', 'l_name' => 'Rao', 'u_name' => 'ananya_rao', 'u_email' => 'ananya.rao@example.com', 'age' => 19, 'mob' => '9345678120', 'adr' => 'Bengaluru, Karnataka'],
    ['f_name' => 'Rohan', 'm_name' => 'Prasad', 'l_name' => 'Joshi', 'u_name' => 'rohan_joshi', 'u_email' => 'rohan.joshi@example.com', 'age' => 21, 'mob' => '9554433221', 'adr' => 'Pune, Maharashtra'],
    ['f_name' => 'Isha', 'm_name' => '', 'l_name' => 'Gupta', 'u_name' => 'isha_gupta', 'u_email' => 'isha.gupta@example.com', 'age' => 20, 'mob' => '9988776655', 'adr' => 'Kolkata, West Bengal'],
    ['f_name' => 'Aditya', 'm_name' => 'Nath', 'l_name' => 'Verma', 'u_name' => 'aditya_verma', 'u_email' => 'aditya.verma@example.com', 'age' => 23, 'mob' => '9112233445', 'adr' => 'Lucknow, Uttar Pradesh'],
    ['f_name' => 'Sanya', 'm_name' => '', 'l_name' => 'Iyer', 'u_name' => 'sanya_iyer', 'u_email' => 'sanya.iyer@example.com', 'age' => 22, 'mob' => '9443322110', 'adr' => 'Chennai, Tamil Nadu'],
    ['f_name' => 'Dev', 'm_name' => 'Raj', 'l_name' => 'Kapoor', 'u_name' => 'dev_kapoor', 'u_email' => 'dev.kapoor@example.com', 'age' => 21, 'mob' => '9001122334', 'adr' => 'Chandigarh, Punjab'],
    ['f_name' => 'Meera', 'm_name' => '', 'l_name' => 'Nair', 'u_name' => 'meera_nair', 'u_email' => 'meera.nair@example.com', 'age' => 20, 'mob' => '9223344556', 'adr' => 'Kochi, Kerala'],
    ['f_name' => 'John', 'm_name' => 'Michael', 'l_name' => 'Smith', 'u_name' => 'john_smith', 'u_email' => 'john.smith@example.com', 'age' => 22, 'mob' => '9888877776', 'adr' => 'San Francisco, CA'],
    ['f_name' => 'Alice', 'm_name' => 'Grace', 'l_name' => 'Johnson', 'u_name' => 'alice_j', 'u_email' => 'alice.johnson@example.com', 'age' => 21, 'mob' => '9666655554', 'adr' => 'Seattle, WA']
];

$hashed_pass = password_hash('student123', PASSWORD_DEFAULT);

$user_ids = [];

foreach ($candidates as $cand) {
    // Check if user already exists
    $check = $conn->prepare("SELECT id FROM users WHERE u_name = ? OR u_email = ?");
    $check->bind_param("ss", $cand['u_name'], $cand['u_email']);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows > 0) {
        $user_row = $res->fetch_assoc();
        $user_ids[$cand['u_name']] = $user_row['id'];
        echo "Candidate '{$cand['u_name']}' already exists. Skipping insertion.\n";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (f_name, m_name, l_name, u_name, u_email, u_pass, u_age, u_mob, u_adr, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student')");
        $stmt->bind_param("ssssssiss", $cand['f_name'], $cand['m_name'], $cand['l_name'], $cand['u_name'], $cand['u_email'], $hashed_pass, $cand['age'], $cand['mob'], $cand['adr']);
        if ($stmt->execute()) {
            $user_ids[$cand['u_name']] = $stmt->insert_id;
            echo "Candidate '{$cand['u_name']}' created successfully.\n";
        } else {
            echo "Error creating candidate '{$cand['u_name']}': " . $stmt->error . "\n";
        }
    }
}

// Seed mock attempt data to populate the leaderboard dynamically
// subject_id mapping from database: 1 = Java, 2 = PHP, 3 = Python, 4 = C Language, 5 = Data Structure
// Levels: Easy, Medium, Hard, Advanced, Expert
$attempts_data = [
    // Java Programming Language (subject_id = 1)
    ['u_name' => 'aarav_sharma', 'subject_id' => 1, 'level' => 'Advanced', 'total' => 25, 'score' => 23, 'perc' => 92.00, 'minutes_offset' => 180], // 3 minutes taken
    ['u_name' => 'diya_patel', 'subject_id' => 1, 'level' => 'Advanced', 'total' => 25, 'score' => 22, 'perc' => 88.00, 'minutes_offset' => 240], // 4 minutes
    ['u_name' => 'kabir_mehta', 'subject_id' => 1, 'level' => 'Medium', 'total' => 25, 'score' => 20, 'perc' => 80.00, 'minutes_offset' => 600], // 10 minutes
    ['u_name' => 'ananya_rao', 'subject_id' => 1, 'level' => 'Easy', 'total' => 25, 'score' => 18, 'perc' => 72.00, 'minutes_offset' => 450],
    
    // Python Programming Language (subject_id = 3)
    ['u_name' => 'diya_patel', 'subject_id' => 3, 'level' => 'Expert', 'total' => 25, 'score' => 24, 'perc' => 96.00, 'minutes_offset' => 150], // 2.5 minutes! Turbo speed
    ['u_name' => 'rohan_joshi', 'subject_id' => 3, 'level' => 'Expert', 'total' => 25, 'score' => 23, 'perc' => 92.00, 'minutes_offset' => 210],
    ['u_name' => 'aarav_sharma', 'subject_id' => 3, 'level' => 'Hard', 'total' => 25, 'score' => 21, 'perc' => 84.00, 'minutes_offset' => 320],
    ['u_name' => 'isha_gupta', 'subject_id' => 3, 'level' => 'Medium', 'total' => 25, 'score' => 19, 'perc' => 76.00, 'minutes_offset' => 500],
    
    // PHP Programming Language (subject_id = 2)
    ['u_name' => 'aditya_verma', 'subject_id' => 2, 'level' => 'Hard', 'total' => 25, 'score' => 23, 'perc' => 92.00, 'minutes_offset' => 280],
    ['u_name' => 'sanya_iyer', 'subject_id' => 2, 'level' => 'Hard', 'total' => 25, 'score' => 22, 'perc' => 88.00, 'minutes_offset' => 310],
    ['u_name' => 'dev_kapoor', 'subject_id' => 2, 'level' => 'Medium', 'total' => 25, 'score' => 19, 'perc' => 76.00, 'minutes_offset' => 420],
    
    // C Language (subject_id = 4)
    ['u_name' => 'meera_nair', 'subject_id' => 4, 'level' => 'Expert', 'total' => 25, 'score' => 25, 'perc' => 100.00, 'minutes_offset' => 120], // 100% Score! 2 minutes
    ['u_name' => 'john_smith', 'subject_id' => 4, 'level' => 'Advanced', 'total' => 25, 'score' => 21, 'perc' => 84.00, 'minutes_offset' => 350],
    ['u_name' => 'alice_j', 'subject_id' => 4, 'level' => 'Medium', 'total' => 25, 'score' => 20, 'perc' => 80.00, 'minutes_offset' => 400],
    
    // Data Structure (subject_id = 5)
    ['u_name' => 'rohan_joshi', 'subject_id' => 5, 'level' => 'Expert', 'total' => 25, 'score' => 24, 'perc' => 96.00, 'minutes_offset' => 180],
    ['u_name' => 'aarav_sharma', 'subject_id' => 5, 'level' => 'Advanced', 'total' => 25, 'score' => 22, 'perc' => 88.00, 'minutes_offset' => 290],
    ['u_name' => 'sanya_iyer', 'subject_id' => 5, 'level' => 'Hard', 'total' => 25, 'score' => 21, 'perc' => 84.00, 'minutes_offset' => 380]
];

foreach ($attempts_data as $att) {
    $u_name = $att['u_name'];
    if (!isset($user_ids[$u_name])) {
        continue;
    }
    $uid = $user_ids[$u_name];
    $sub_id = $att['subject_id'];
    $lvl = $att['level'];
    
    // Check if this attempt already exists to prevent duplicate seeding
    $check_att = $conn->prepare("SELECT id FROM exam_attempts WHERE user_id = ? AND subject_id = ? AND level = ? AND exam_mode = 'official'");
    $check_att->bind_param("iis", $uid, $sub_id, $lvl);
    $check_att->execute();
    $res_att = $check_att->get_result();
    
    if ($res_att->num_rows > 0) {
        echo "Attempt for candidate '{$u_name}' on subject #{$sub_id} level {$lvl} already exists. Skipping.\n";
    } else {
        $started = date("Y-m-d H:i:s", time() - 3600); // 1 hour ago
        $submitted = date("Y-m-d H:i:s", strtotime($started) + $att['minutes_offset']);
        
        $stmt = $conn->prepare("INSERT INTO exam_attempts (user_id, subject_id, level, total_questions, score, percentage, started_at, submitted_at, exam_mode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'official')");
        $stmt->bind_param("iisiisss", $uid, $sub_id, $lvl, $att['total'], $att['score'], $att['perc'], $started, $submitted);
        if ($stmt->execute()) {
            echo "Attempt seeded successfully for candidate '{$u_name}'.\n";
        } else {
            echo "Error seeding attempt for candidate '{$u_name}': " . $stmt->error . "\n";
        }
    }
}

echo "All mock student records and leaderboard attempts seeded successfully!\n";
?>
