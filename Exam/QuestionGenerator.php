<?php
/**
 * QuestionGenerator Class
 * Simulates an AI-driven question generation system for coding platforms.
 * Automatically populates the database with subject-specific and level-specific questions.
 */
class QuestionGenerator {
    private $db;

    public function __construct($db_connection) {
        $this->db = $db_connection;
    }

    /**
     * Ensures that a specific subject and level has at least $minQuestions.
     * If not, it "generates" (inserts from template bank) new questions.
     */
    public function ensurePool($subject, $level, $minQuestions = 5) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM questions WHERE LOWER(subject) = LOWER(?) AND LOWER(level) = LOWER(?)");
        $stmt->bind_param("ss", $subject, $level);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if ($res['count'] < $minQuestions) {
            $this->generateQuestions($subject, $level, $minQuestions - $res['count']);
        }
    }

    /**
     * The "Generation Engine" - contains templates for different subjects and levels.
     */
    private function generateQuestions($subject, $level, $count) {
        $bank = $this->getQuestionBank($subject, $level);
        
        // Shuffle and pick needed count
        shuffle($bank);
        $toInsert = array_slice($bank, 0, $count);

        foreach ($toInsert as $q) {
            // Check if question already exists to prevent duplicates
            $check = $this->db->prepare("SELECT id FROM questions WHERE question = ?");
            $check->bind_param("s", $q['question']);
            $check->execute();
            if ($check->get_result()->num_rows == 0) {
                $stmt = $this->db->prepare("INSERT INTO questions (subject, level, question, option_a, option_b, option_c, option_d, correct_answer, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssss", $subject, $level, $q['question'], $q['a'], $q['b'], $q['c'], $q['d'], $q['correct'], $q['explanation']);
                $stmt->execute();
            }
        }
    }

    private function getQuestionBank($subject, $level) {
        $subject = strtolower($subject);
        $level = ucfirst(strtolower($level));

        $banks = [
            'java' => [
                'Easy' => [
                    ['question' => 'Which of these is used to read input in Java?', 'a' => 'Scanner', 'b' => 'Reader', 'c' => 'Input', 'd' => 'System.in', 'correct' => 'A', 'explanation' => 'The Scanner class is part of java.util and is widely used to read input from various sources.'],
                    ['question' => 'What is the entry point of a Java program?', 'a' => 'start()', 'b' => 'main()', 'c' => 'init()', 'd' => 'run()', 'correct' => 'B', 'explanation' => 'The public static void main(String[] args) method is the standard entry point for any standalone Java application.'],
                    ['question' => 'Which keyword is used to inherit a class in Java?', 'a' => 'implements', 'b' => 'extends', 'c' => 'inherits', 'd' => 'using', 'correct' => 'B', 'explanation' => "In Java, 'extends' is used for class inheritance, while 'implements' is used for interface implementation."]
                ]
            ]
        ];

        $static_bank = $banks[$subject][$level] ?? [];
        $dynamic_bank = $this->generateFromTemplates($subject, $level);
        
        return array_merge($static_bank, $dynamic_bank);
    }

    /**
     * Generates variations of questions from generic templates.
     */
    private function generateFromTemplates($subject, $level) {
        $questions = [];
        $subject = ucfirst(strtolower($subject));
        
        if ($level == 'Easy') {
            for ($i = 1; $i <= 10; $i++) {
                $questions[] = [
                    'question' => "What is the primary file extension used for {$subject} source files (Variant {$i})?",
                    'a' => '.' . strtolower($subject), 'b' => '.txt', 'c' => '.exe', 'd' => '.bin',
                    'correct' => 'A', 'explanation' => "The standard extension for {$subject} is ." . strtolower($subject)
                ];
                $questions[] = [
                    'question' => "Which of the following is a valid variable declaration in {$subject} (Pattern {$i})?",
                    'a' => 'var x = 10;', 'b' => 'int 1x = 10;', 'c' => '10 = x;', 'd' => 'variable x;',
                    'correct' => 'A', 'explanation' => "Variable names cannot start with numbers and must follow syntax rules."
                ];
            }
        } elseif ($level == 'Medium') {
            for ($i = 1; $i <= 10; $i++) {
                $questions[] = [
                    'question' => "What is the time complexity of a simple for-loop iterating {$i}00 times over an array?",
                    'a' => 'O(1)', 'b' => 'O(n)', 'c' => 'O(n^2)', 'd' => 'O(log n)',
                    'correct' => 'B', 'explanation' => "A single loop over an array of size n has O(n) complexity."
                ];
            }
        } else {
             for ($i = 1; $i <= 10; $i++) {
                $questions[] = [
                    'question' => "In {$subject}, how does the internal memory manager handle cyclic dependencies (Scenario {$i})?",
                    'a' => 'Garbage Collection', 'b' => 'Manual free()', 'c' => 'Reference Counting', 'd' => 'Ignores them',
                    'correct' => 'A', 'explanation' => "Most modern languages use GC tracing to resolve cyclic dependencies."
                ];
             }
        }

        return $questions;
    }
}
