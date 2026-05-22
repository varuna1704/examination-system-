<?php
require_once __DIR__ . '/../../config.php';

echo "Seeding default master administrator account...\n";

$f_name = 'System';
$m_name = 'Master';
$l_name = 'Admin';
$u_name = 'admin';
$u_email = 'admin@examportal.pro';
$raw_pass = 'admin123';
$u_age = 30;
$u_mob = '9999999999';
$u_adr = 'ExamPortal HQ';
$role = 'admin';

// Verify the users table exists or create it
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    f_name VARCHAR(100),
    m_name VARCHAR(100),
    l_name VARCHAR(100),
    u_name VARCHAR(100) UNIQUE,
    u_email VARCHAR(100) UNIQUE,
    u_pass VARCHAR(255),
    u_age INT,
    u_mob VARCHAR(20),
    u_adr TEXT,
    role ENUM('admin', 'teacher', 'student') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Check if user 'admin' already exists
$stmt = $conn->prepare("SELECT id, role FROM users WHERE u_name = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("s", $u_name);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if ($row['role'] !== 'admin') {
            // Update to admin role if already exists but not admin
            $update = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
            $update->bind_param("i", $row['id']);
            $update->execute();
            echo "User 'admin' already exists. Role updated to 'admin'.\n";
        } else {
            echo "Master administrator 'admin' is already seeded and active.\n";
        }
    } else {
        // Insert new admin
        $hashed_pass = password_hash($raw_pass, PASSWORD_DEFAULT);
        $insert = $conn->prepare("INSERT INTO users (f_name, m_name, l_name, u_name, u_email, u_pass, u_age, u_mob, u_adr, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($insert) {
            $insert->bind_param("ssssssisss", $f_name, $m_name, $l_name, $u_name, $u_email, $hashed_pass, $u_age, $u_mob, $u_adr, $role);
            if ($insert->execute()) {
                echo "Successfully seeded default master administrator account!\n";
                echo "Username: admin\nPassword: admin123\n";
            } else {
                echo "Error: Failed to insert administrator account.\n";
            }
        } else {
            echo "Error preparing insert statement.\n";
        }
    }
} else {
    echo "Error preparing select statement.\n";
}
?>
