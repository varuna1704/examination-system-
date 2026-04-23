<?php
/**
 * Dynamic Question Generation System Demo
 * This script demonstrates the automatic population of questions across all levels and subjects.
 */
include 'config.php';
include 'QuestionGenerator.php';

$generator = new QuestionGenerator($conn);

$subjects = ['Java', 'Python', 'PHP', 'C', 'DS'];
$levels = ['Easy', 'Medium', 'Hard', 'Advanced', 'Expert'];

echo "<h1>Dynamic Question Generation System - Initialization</h1>";
echo "<p>Mimicking LeetCode/HackerRank scaling logic...</p>";

foreach ($subjects as $sub) {
    echo "<h2>Subject: $sub</h2>";
    foreach ($levels as $lvl) {
        echo "Processing $lvl level... ";
        $generator->ensurePool($sub, $lvl, 3); // Ensure at least 3 questions for each combo
        
        // Fetch to verify
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM questions WHERE subject = ? AND level = ?");
        $stmt->bind_param("ss", $sub, $lvl);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        echo "<span style='color: green;'>Pool Size: $count questions.</span><br>";
    }
}

echo "<br><hr><h3>Sample SQL Insert Logic (for reference)</h3>";
echo "<pre>
INSERT INTO questions (subject, level, question, option_a, option_b, option_c, option_d, correct_answer, explanation)
VALUES ('Java', 'Easy', 'What is JVM?', 'Java Virtual Machine', 'Java Visual Model', 'Just Variable Mode', 'None', 'A', 'JVM is the engine that provides a runtime environment to drive the Java Code.');
</pre>";

echo "<br><a href='subject.php' style='padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px;'>Go to Examination Portal</a>";
?>
