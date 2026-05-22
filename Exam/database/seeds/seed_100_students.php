<?php
require_once __DIR__ . '/../../config.php';

echo "Generating and seeding at least 100 unique candidate records with competitive exam results...\n";

// Disable foreign key checks temporarily to speed up bulk inserts
$conn->query("SET FOREIGN_KEY_CHECKS = 0;");

$firsts = [
    'Aarav', 'Vihaan', 'Aditya', 'Arjun', 'Kabir', 'Rohan', 'Ishaan', 'Dev', 'Shaurya', 'Karan', 
    'Rahul', 'Yash', 'Siddharth', 'Amit', 'Vikram', 'Raj', 'Aryan', 'Samar', 'Diya', 'Ananya', 
    'Aanya', 'Priya', 'Meera', 'Riya', 'Kavya', 'Sanya', 'Aditi', 'Neha', 'Pooja', 'Shruti', 
    'Deepika', 'Priyanka', 'Sneha', 'Tanvi', 'Anjali', 'Kriti', 'Nisha', 'Vidya', 'Liam', 'Emma', 
    'Noah', 'Olivia', 'Ava', 'Oliver', 'Sophia', 'Elijah', 'Isabella', 'James', 'Mia', 'Lucas', 
    'Charlotte', 'Amelia', 'Ethan', 'Harper', 'Alexander', 'Evelyn'
];

$lasts = [
    'Sharma', 'Patel', 'Singh', 'Mehta', 'Rao', 'Joshi', 'Gupta', 'Verma', 'Iyer', 'Kapoor', 
    'Nair', 'Reddy', 'Choudhury', 'Das', 'Sen', 'Banerjee', 'Mishra', 'Trivedi', 'Shah', 'Deshmukh', 
    'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis', 'Wilson', 'Anderson', 'Taylor'
];

$cities = [
    'Mumbai, Maharashtra', 'Bengaluru, Karnataka', 'Pune, Maharashtra', 'New Delhi, Delhi', 
    'Hyderabad, Telangana', 'Chennai, Tamil Nadu', 'Kolkata, West Bengal', 'San Francisco, CA', 
    'New York, NY', 'Seattle, WA', 'Austin, TX', 'Boston, MA', 'London, UK', 'Toronto, ON'
];

$hashed_pass = password_hash('student123', PASSWORD_DEFAULT);

$user_ids = [];
$total_students = 115; // Generates 115 unique candidates to satisfy "at least 100" completely

$inserted_count = 0;

for ($i = 1; $i <= $total_students; $i++) {
    // Generate mathematically unique combinations of first name and last name
    $first = $firsts[($i - 1) % count($firsts)];
    $last = $lasts[floor(($i - 1) / count($firsts)) % count($lasts)];
    
    // Middle name
    $middle = ($i % 3 === 0) ? 'Kumar' : (($i % 5 === 0) ? 'Prasad' : '');
    
    // Usernames and emails are guaranteed completely unique due to the suffix $i
    $uname = strtolower($first . '_' . $last . '_' . $i);
    $uemail = strtolower($first . '.' . $last . '.' . $i . '@example.com');
    
    $age = 18 + ($i % 8);
    $mob = '9' . str_pad($i, 9, '0', STR_PAD_LEFT);
    $adr = $cities[$i % count($cities)];
    
    // Check if user already exists
    $check = $conn->prepare("SELECT id FROM users WHERE u_name = ? OR u_email = ?");
    $check->bind_param("ss", $uname, $uemail);
    $check->execute();
    $res = $check->get_result();
    
    if ($res->num_rows > 0) {
        $user_row = $res->fetch_assoc();
        $user_ids[] = $user_row['id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO users (f_name, m_name, l_name, u_name, u_email, u_pass, u_age, u_mob, u_adr, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student')");
        $stmt->bind_param("ssssssiss", $first, $middle, $last, $uname, $uemail, $hashed_pass, $age, $mob, $adr);
        if ($stmt->execute()) {
            $user_ids[] = $stmt->insert_id;
            $inserted_count++;
        }
    }
}

echo "Created {$inserted_count} new unique candidate accounts.\n";

// Seed attempts dynamically for these users to make the leaderboard look spectacular!
// We'll seed attempts for 90 of these candidates to maintain high density without cluttering
$attempts_to_seed = 90;
$attempts_seeded = 0;

$levels = ['Easy', 'Medium', 'Hard', 'Advanced', 'Expert'];

for ($k = 0; $k < $attempts_to_seed; $k++) {
    if (!isset($user_ids[$k])) {
        continue;
    }
    $uid = $user_ids[$k];
    
    // Pick subject (1 to 5)
    $sub_id = ($k % 5) + 1;
    $lvl = $levels[$k % 5];
    
    // Check if this attempt already exists
    $check_att = $conn->prepare("SELECT id FROM exam_attempts WHERE user_id = ? AND subject_id = ? AND level = ? AND exam_mode = 'official'");
    $check_att->bind_param("iis", $uid, $sub_id, $lvl);
    $check_att->execute();
    if ($check_att->get_result()->num_rows > 0) {
        continue;
    }
    
    // Generate scores: range from 15 to 25
    $score = 15 + ($k % 11);
    $total_q = 25;
    $perc = ($score / $total_q) * 100;
    
    // Time taken: from 90 seconds (1m 30s) to 540 seconds (9m)
    $time_offset_sec = 90 + ($k % 10) * 45;
    
    $started = date("Y-m-d H:i:s", time() - (86400 * ($k % 15)) - 3600); // spread across 15 days
    $submitted = date("Y-m-d H:i:s", strtotime($started) + $time_offset_sec);
    
    $stmt = $conn->prepare("INSERT INTO exam_attempts (user_id, subject_id, level, total_questions, score, percentage, started_at, submitted_at, exam_mode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'official')");
    $stmt->bind_param("iisiisss", $uid, $sub_id, $lvl, $total_q, $score, $perc, $started, $submitted);
    if ($stmt->execute()) {
        $attempts_seeded++;
    }
}

// Enable foreign key checks back
$conn->query("SET FOREIGN_KEY_CHECKS = 1;");

echo "Seeded {$attempts_seeded} unique competitive test attempts across all subjects!\n";
echo "Successfully completed seeding 100+ unique student records with zero repeats.\n";
?>
