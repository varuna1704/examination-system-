<?php
$envFile = __DIR__ . '/.env';
$env = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        $parts = explode('=', $line, 2);
        $key = trim($parts[0]);
        $value = trim($parts[1] ?? '');
        $env[$key] = $value;
    }
}

$host = $env['DB_HOST'] ?? '127.0.0.1';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';
$dbname = $env['DB_NAME'] ?? 'examination_system';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
