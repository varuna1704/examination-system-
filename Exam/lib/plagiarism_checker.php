<?php
/**
 * Plagiarism and Subjective Answer Similarity Engine
 */

class PlagiarismChecker {
    
    /**
     * Calculates the similarity percentage between two strings.
     */
    public static function getSimilarityScore(string $str1, string $str2): float
    {
        $clean1 = self::sanitize($str1);
        $clean2 = self::sanitize($str2);
        
        if (empty($clean1) || empty($clean2)) {
            return 0.0;
        }
        
        similar_text($clean1, $clean2, $percent);
        return round($percent, 2);
    }
    
    /**
     * Compares a student's answer against all other student submissions for the same question
     * to detect cohort-level copying.
     */
    public static function checkCohortPlagiarism(mysqli $db, int $currentAttemptId, int $questionId, string $studentAnswer): array
    {
        $cleanStudent = self::sanitize($studentAnswer);
        if (empty($cleanStudent)) {
            return ['max_similarity' => 0.0, 'matched_user' => 'N/A', 'matched_attempt_id' => null];
        }
        
        // Fetch all other student answers for the same question
        $stmt = $db->prepare("
            SELECT aa.attempt_id, aa.selected_answer, u.u_name 
            FROM attempt_answers aa
            JOIN exam_attempts ea ON ea.id = aa.attempt_id
            JOIN users u ON u.id = ea.user_id
            WHERE aa.question_id = ? AND aa.attempt_id != ? AND aa.selected_answer IS NOT NULL
        ");
        
        if (!$stmt) {
            return ['max_similarity' => 0.0, 'matched_user' => 'N/A', 'matched_attempt_id' => null];
        }
        
        $stmt->bind_param("ii", $questionId, $currentAttemptId);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $maxSimilarity = 0.0;
        $matchedUser = 'N/A';
        $matchedAttemptId = null;
        
        while ($row = $res->fetch_assoc()) {
            $otherAnswer = $row['selected_answer'];
            $similarity = self::getSimilarityScore($cleanStudent, $otherAnswer);
            
            if ($similarity > $maxSimilarity) {
                $maxSimilarity = $similarity;
                $matchedUser = $row['u_name'];
                $matchedAttemptId = (int)$row['attempt_id'];
            }
        }
        
        return [
            'max_similarity' => $maxSimilarity,
            'matched_user' => $matchedUser,
            'matched_attempt_id' => $matchedAttemptId
        ];
    }
    
    /**
     * Sanitizes strings for accurate lexical comparison.
     */
    private static function sanitize(string $text): string
    {
        $text = strtolower($text);
        // Strip out HTML markup
        $text = strip_tags($text);
        // Normalize whitespaces and line endings
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
?>
