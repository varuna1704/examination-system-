<?php
/**
 * Hybrid Question Management System
 * Combines Local Database, Internal Question Generator, and External API (Open Trivia DB).
 */
class HybridQuestionManager {
    private $db;
    private $generator;

    public function __construct($db_connection, $generator_instance) {
        $this->db = $db_connection;
        $this->generator = $generator_instance;
    }

    /**
     * Ensures the database has the required number of questions for a given subject and level.
     * Uses priority: Database -> Internal Generator -> External API.
     */
    public function ensureQuestions($subject, $level) {
        $required_count = (strtolower($level) === 'expert') ? 10 : 25;
        $subjectId = $this->generator->getSubjectId($subject);

        // Step 1: Check Database
        $current_count = $this->getDbCount($subjectId, $level);
        if ($current_count >= $required_count) {
            return; // Smart Caching: Sufficient questions exist
        }

        // Step 2: Use Internal Question Generator
        $this->generator->ensurePool($subject, $level, $required_count);
        $current_count = $this->getDbCount($subjectId, $level);
        if ($current_count >= $required_count) {
            return;
        }

        // Step 3: Fetch from External API (Open Trivia DB)
        $deficit = $required_count - $current_count;
        if ($deficit > 0) {
            $this->fetchFromApi($subjectId, $subject, $level, $deficit);
        }
    }

    private function getDbCount($subjectId, $level) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM questions WHERE subject_id = ? AND level = ?");
        $stmt->bind_param("is", $subjectId, $level);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['count'];
    }

    /**
     * Fetches questions from Open Trivia DB and inserts them into the local database.
     */
    private function fetchFromApi($subjectId, $subjectName, $level, $amount) {
        // Open Trivia DB only allows max 50 per request
        $amount = min($amount, 50);

        // Map system levels to API difficulty
        $difficulty_map = [
            'easy' => 'easy',
            'medium' => 'medium',
            'hard' => 'hard',
            'advanced' => 'hard',
            'expert' => 'hard'
        ];
        $api_diff = $difficulty_map[strtolower($level)] ?? 'medium';

        // Map subjects to Open Trivia DB Categories (18 is Science: Computers)
        $category = 18; 

        $url = "https://opentdb.com/api.php?amount={$amount}&category={$category}&difficulty={$api_diff}&type=multiple";

        // Handle API request with cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 seconds timeout
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            // Error Handling: API failed, fallback to DB/Generator
            error_log("Open Trivia DB API Error: " . curl_error($ch));
            curl_close($ch);
            return;
        }
        curl_close($ch);

        $data = json_decode($response, true);
        if (!$data || !isset($data['results'])) {
            return;
        }

        foreach ($data['results'] as $item) {
            $question_text = html_entity_decode($item['question'], ENT_QUOTES);
            
            // Avoid duplicates
            $check = $this->db->prepare("SELECT id FROM questions WHERE subject_id = ? AND question = ?");
            $check->bind_param("is", $subjectId, $question_text);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                continue;
            }

            // Convert to MCQ format
            $options = $item['incorrect_answers'];
            $options[] = $item['correct_answer'];
            
            // Decode HTML entities in options
            $options = array_map(function($opt) {
                return html_entity_decode($opt, ENT_QUOTES);
            }, $options);

            // Shuffling options
            shuffle($options);

            // Find correct answer index
            $correct_index = array_search(html_entity_decode($item['correct_answer'], ENT_QUOTES), $options);
            $correct_letter = chr(65 + $correct_index); // 0 -> 'A', 1 -> 'B', etc.

            $explanation = "Source: Open Trivia DB. Correct answer is " . html_entity_decode($item['correct_answer'], ENT_QUOTES) . ".";

            $stmt = $this->db->prepare("INSERT INTO questions (subject_id, level, type, question, option_a, option_b, option_c, option_d, correct_answer, explanation) VALUES (?, ?, 'MCQ', ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssss", 
                $subjectId, 
                $level, 
                $question_text, 
                $options[0], 
                $options[1], 
                $options[2], 
                $options[3], 
                $correct_letter, 
                $explanation
            );
            $stmt->execute();
        }
    }
}
?>
