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
     * Resolves the canonical subject ID from the subject name.
     * Inserts the subject if it does not exist.
     */
    public function getSubjectId($subjectName) {
        $mapping = [
            'java' => 'Java Programming Language',
            'python' => 'Python Programming Language',
            'php' => 'PHP Programming Language',
            'c' => 'C Language',
            'ds' => 'Data Structure'
        ];
        
        $s = strtolower($subjectName);
        $canonName = $subjectName;
        foreach ($mapping as $short => $full) {
            if (strpos($s, $short) !== false) {
                $canonName = $full;
                break;
            }
        }

        $stmt = $this->db->prepare("SELECT id FROM subjects WHERE LOWER(name) = LOWER(?) LIMIT 1");
        $stmt->bind_param("s", $canonName);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        if ($res) {
            return (int)$res['id'];
        }

        $stmt = $this->db->prepare("INSERT INTO subjects (name) VALUES (?)");
        $stmt->bind_param("s", $canonName);
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    /**
     * Ensures that a specific subject and level has at least $minQuestions.
     * If not, it "generates" (inserts from template bank) new questions.
     */
    public function ensurePool($subject, $level, $minQuestions = 25) {
        $subjectId = $this->getSubjectId($subject);
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM questions WHERE subject_id = ? AND level = ?");
        $stmt->bind_param("is", $subjectId, $level);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if ($res['count'] < $minQuestions) {
            $this->generateQuestions($subjectId, $subject, $level, $minQuestions - $res['count']);
        }
    }

    /**
     * The "Generation Engine" - generates and inserts templates for different subjects and levels.
     */
    private function generateQuestions($subjectId, $subjectName, $level, $count) {
        $added = 0;
        $seed = 0;
        $guard = 0;

        $dupStmt = $this->db->prepare("SELECT id FROM questions WHERE subject_id = ? AND level = ? AND question = ? LIMIT 1");
        $insertStmt = $this->db->prepare(
            "INSERT INTO questions (subject_id, level, type, question, option_a, option_b, option_c, option_d, correct_answer, explanation)
             VALUES (?, ?, 'MCQ', ?, ?, ?, ?, ?, ?, ?)"
        );

        while ($added < $count && $guard < 2000) {
            $guard++;
            $candidate = $this->buildQuestion($subjectName, $level, $seed++);
            
            if (!$this->isValidQuestion($candidate)) {
                continue;
            }

            // Prevent duplicates
            $dupStmt->bind_param("iss", $subjectId, $level, $candidate['question']);
            $dupStmt->execute();
            $dupRes = $dupStmt->get_result();
            if ($dupRes && $dupRes->num_rows > 0) {
                continue;
            }

            $insertStmt->bind_param(
                "issssssss",
                $subjectId,
                $level,
                $candidate['question'],
                $candidate['option_a'],
                $candidate['option_b'],
                $candidate['option_c'],
                $candidate['option_d'],
                $candidate['correct_answer'],
                $candidate['explanation']
            );

            if ($insertStmt->execute()) {
                $added++;
            }
        }
    }

    private function canonicalKey(string $subject): string {
        $s = strtolower($subject);
        if (strpos($s, 'java') !== false) return 'java';
        if (strpos($s, 'python') !== false) return 'python';
        if (strpos($s, 'php') !== false) return 'php';
        if (strpos($s, 'data structure') !== false || strpos($s, 'ds') !== false) return 'ds';
        if ($s === 'c language' || $s === 'c') return 'c';
        return 'generic';
    }

    private function getExplanation(string $key, string $topic): string {
        return "This explanation is correct because in {$key} development, the behavior of {$topic} is defined by standard language specifications and optimization guidelines.";
    }

    private function getTemplates(string $level): array {
        return match ($level) {
            'Easy' => [
                "What is true about %s in %s?",
                "Which option correctly defines %s for %s?",
                "For %s basics, what is correct about %s?",
                "Choose the correct beginner statement about %s in %s.",
                "In introductory %s, which fact about %s is correct?"
            ],
            'Medium' => [
                "In %s code reading, what is correct about %s?",
                "Which practical usage statement about %s in %s is valid?",
                "For common %s patterns, what is true about %s?",
                "In a moderate %s example, which %s statement is correct?",
                "Select the correct %s behavior for %s usage."
            ],
            'Hard' => [
                "During %s debugging, which statement about %s is accurate?",
                "In a tricky %s edge case, what is true about %s?",
                "Which hard-level %s behavior about %s is correct?",
                "When %s code fails, which %s statement is valid?",
                "Choose the correct hard scenario fact about %s in %s."
            ],
            'Advanced' => [
                "In advanced %s architecture, what is true about %s?",
                "For %s performance design, which %s statement is correct?",
                "Which advanced design decision in %s aligns with %s?",
                "In large-scale %s systems, what is correct about %s?",
                "Choose the accurate advanced rule about %s in %s."
            ],
            'Expert' => [
                "At expert depth in %s internals, what is true about %s?",
                "Which low-level %s behavior related to %s is correct?",
                "For expert %s optimization, which %s statement is valid?",
                "In %s implementation internals, what is correct about %s?",
                "Choose the expert-level fact about %s in %s."
            ],
            default => ["What is correct about %s in %s?"]
        };
    }

    private function getConceptBank(string $key): array {
        return match ($key) {
            'java' => [
                ['topic' => 'class declaration', 'correct' => 'The class keyword declares a class.', 'wrong' => ['The define keyword declares a class.', 'The object keyword declares a class.', 'The create keyword declares a class.']],
                ['topic' => 'program entry point', 'correct' => 'main(String[] args) is the entry point in standard Java applications.', 'wrong' => ['start() is always the entry point.', 'run() is always the entry point.', 'init() is always the entry point.']],
                ['topic' => 'inheritance', 'correct' => 'extends is used for class inheritance.', 'wrong' => ['inherits is used for class inheritance.', 'include is used for class inheritance.', 'using is used for class inheritance.']],
                ['topic' => 'interface implementation', 'correct' => 'implements is used to implement interfaces.', 'wrong' => ['extends is required to implement every interface.', 'interface automatically implements itself.', 'override implements interfaces.']],
                ['topic' => 'unchecked exceptions', 'correct' => 'NullPointerException is unchecked.', 'wrong' => ['IOException is unchecked.', 'SQLException is unchecked.', 'FileNotFoundException is unchecked.']],
                ['topic' => 'collections uniqueness', 'correct' => 'Set implementations disallow duplicate elements.', 'wrong' => ['ArrayList disallows duplicate elements.', 'LinkedList disallows duplicate elements.', 'Vector disallows duplicate elements.']],
                ['topic' => 'string immutability', 'correct' => 'String objects are immutable.', 'wrong' => ['String objects are mutable by default.', 'Strings are mutable only in methods.', 'Strings cannot be compared by value.']],
                ['topic' => 'memory management', 'correct' => 'The JVM reclaims unreachable objects using garbage collection.', 'wrong' => ['Java requires manual free() for all heap objects.', 'Java never reclaims unreachable objects.', 'Garbage collection runs only at compile time.']],
                ['topic' => 'thread safety', 'correct' => 'Vector is synchronized by default.', 'wrong' => ['ArrayList is synchronized by default.', 'HashMap is synchronized by default.', 'LinkedList is synchronized by default.']],
                ['topic' => 'final classes', 'correct' => 'A final class cannot be subclassed.', 'wrong' => ['A final class cannot have methods.', 'A final class must be abstract.', 'A final class can be inherited once.']]
            ],
            'python' => [
                ['topic' => 'execution model', 'correct' => 'Python is generally interpreted.', 'wrong' => ['Python is only assembled.', 'Python requires manual linking for all scripts.', 'Python source cannot be interpreted.']],
                ['topic' => 'function declaration', 'correct' => 'def defines a function.', 'wrong' => ['func defines a function.', 'define defines a function.', 'function defines a function.']],
                ['topic' => 'immutable types', 'correct' => 'tuple is immutable.', 'wrong' => ['list is immutable.', 'dict is immutable.', 'set is immutable.']],
                ['topic' => 'anonymous functions', 'correct' => 'lambda creates an anonymous function.', 'wrong' => ['yield creates an anonymous function.', 'pass creates an anonymous function.', 'global creates an anonymous function.']],
                ['topic' => 'regular expressions', 'correct' => 'The re module provides regex support.', 'wrong' => ['The os module provides regex support.', 'The sys module provides regex support.', 'The math module provides regex support.']],
                ['topic' => 'inheritance model', 'correct' => 'Python supports multiple inheritance.', 'wrong' => ['Python supports only single inheritance.', 'Python does not support inheritance.', 'Python supports inheritance only for built-ins.']],
                ['topic' => 'list comprehension', 'correct' => 'List comprehensions construct lists from iterable expressions.', 'wrong' => ['List comprehensions only sort lists.', 'List comprehensions only remove duplicates.', 'List comprehensions are only for file reading.']],
                ['topic' => 'dictionary keys', 'correct' => 'Dictionary keys must be hashable.', 'wrong' => ['Dictionary keys must always be lists.', 'Dictionary keys must always be dictionaries.', 'Dictionary keys can never be strings.']],
                ['topic' => 'virtual environments', 'correct' => 'Virtual environments isolate project dependencies.', 'wrong' => ['Virtual environments compile Python to C.', 'Virtual environments replace pip.', 'Virtual environments prevent module imports.']],
                ['topic' => 'decorators', 'correct' => 'A decorator wraps a callable to extend behavior.', 'wrong' => ['A decorator always changes variable type.', 'A decorator can only be used on loops.', 'A decorator always creates a class.']]
            ],
            'php' => [
                ['topic' => 'variable syntax', 'correct' => 'PHP variables are prefixed with $.', 'wrong' => ['PHP variables are prefixed with #.', 'PHP variables are prefixed with %.', 'PHP variables are prefixed with @ only.']],
                ['topic' => 'output', 'correct' => 'echo outputs one or more strings.', 'wrong' => ['scan outputs one or more strings.', 'input outputs one or more strings.', 'write outputs one or more strings.']],
                ['topic' => 'string concatenation', 'correct' => 'The dot operator concatenates strings in PHP.', 'wrong' => ['The plus operator concatenates strings in PHP.', 'The ampersand operator concatenates strings in PHP.', 'The slash operator concatenates strings in PHP.']],
                ['topic' => 'request data', 'correct' => '$_POST contains HTTP POST data.', 'wrong' => ['$_POST contains only cookies.', '$_POST contains only session values.', '$_POST contains only server variables.']],
                ['topic' => 'sessions', 'correct' => 'session_start() starts or resumes a session.', 'wrong' => ['session_start() destroys all sessions.', 'session_start() closes the current session.', 'session_start() can only run after output.']],
                ['topic' => 'prepared statements', 'correct' => 'Prepared statements reduce SQL injection risk.', 'wrong' => ['Prepared statements only improve formatting.', 'Prepared statements cannot bind parameters.', 'Prepared statements are unsupported by mysqli.']],
                ['topic' => 'include vs require', 'correct' => 'require triggers a fatal error if the target file is missing.', 'wrong' => ['require only emits a notice if missing.', 'include and require both always silently continue.', 'require can only load CSS files.']],
                ['topic' => 'array model', 'correct' => 'PHP arrays are ordered maps.', 'wrong' => ['PHP arrays are fixed-length only.', 'PHP arrays cannot have string keys.', 'PHP arrays cannot nest.']],
                ['topic' => 'password security', 'correct' => 'password_hash() should be used for secure password hashing.', 'wrong' => ['base64_encode() should be used for secure password hashing.', 'md5() is recommended for secure password hashing.', 'sha1() without salt is recommended for secure password hashing.']],
                ['topic' => 'strict comparison', 'correct' => '=== compares both value and type.', 'wrong' => ['=== compares value only.', '=== compares type only.', '=== cannot compare strings.']]
            ],
            'c' => [
                ['topic' => 'entry point', 'correct' => 'main() is the standard program entry point.', 'wrong' => ['start() is the standard program entry point.', 'run() is the standard program entry point.', 'init() is the standard program entry point.']],
                ['topic' => 'standard I/O', 'correct' => 'stdio.h declares standard input/output functions.', 'wrong' => ['stdio.h declares only threading functions.', 'stdio.h declares only socket APIs.', 'stdio.h declares only memory allocation APIs.']],
                ['topic' => 'pointers', 'correct' => 'A pointer stores a memory address.', 'wrong' => ['A pointer stores only floating-point values.', 'A pointer stores only loop counters.', 'A pointer stores CPU frequency values.']],
                ['topic' => 'dynamic memory', 'correct' => 'malloc allocates memory dynamically.', 'wrong' => ['printf allocates memory dynamically.', 'scanf allocates memory dynamically.', 'return allocates memory dynamically.']],
                ['topic' => 'dereference', 'correct' => 'The * operator dereferences a pointer.', 'wrong' => ['The & operator dereferences a pointer.', 'The # operator dereferences a pointer.', 'The @ operator dereferences a pointer.']],
                ['topic' => 'array indexing', 'correct' => 'C arrays are zero-indexed.', 'wrong' => ['C arrays are one-indexed.', 'C arrays are always negative-indexed.', 'C arrays must start at index 2.']],
                ['topic' => 'const usage', 'correct' => 'const prevents modification through the declared identifier.', 'wrong' => ['const forces heap allocation.', 'const disables compiler diagnostics.', 'const makes variables global.']],
                ['topic' => 'header guards', 'correct' => 'Header guards prevent multiple-inclusion problems.', 'wrong' => ['Header guards optimize recursion depth.', 'Header guards force inlining.', 'Header guards replace function prototypes.']],
                ['topic' => 'undefined behavior', 'correct' => 'Out-of-bounds array access can cause undefined behavior.', 'wrong' => ['Out-of-bounds array access is always safe.', 'Out-of-bounds array access always throws exceptions in standard C.', 'Out-of-bounds array access is auto-corrected by the compiler.']],
                ['topic' => 'memory regions', 'correct' => 'Heap memory usually requires explicit release by the programmer.', 'wrong' => ['Heap memory is always auto-freed in C.', 'Stack memory always requires free().', 'Heap and stack are the same storage.']]
            ],
            'ds' => [
                ['topic' => 'stack order', 'correct' => 'A stack follows LIFO order.', 'wrong' => ['A stack follows FIFO order.', 'A stack always sorts inserted values.', 'A stack removes middle elements first.']],
                ['topic' => 'queue order', 'correct' => 'A queue follows FIFO order.', 'wrong' => ['A queue follows LIFO order.', 'A queue always removes highest priority first.', 'A queue can only remove from the middle.']],
                ['topic' => 'linked lists', 'correct' => 'Linked list nodes store data and link references.', 'wrong' => ['Linked list nodes are always contiguous in memory.', 'Linked list nodes cannot be inserted in the middle.', 'Linked list nodes cannot store addresses.']],
                ['topic' => 'binary trees', 'correct' => 'A binary tree node has at most two children.', 'wrong' => ['A binary tree node has exactly three children.', 'A binary tree node has at most four children.', 'A binary tree node cannot have children.']],
                ['topic' => 'linear search', 'correct' => 'Linear search is O(n) in the worst case.', 'wrong' => ['Linear search is O(1) in the worst case.', 'Linear search is O(log n) in the worst case.', 'Linear search is O(n^2) in the worst case.']],
                ['topic' => 'hash tables', 'correct' => 'Average-case hash-table lookup is often O(1).', 'wrong' => ['Average-case hash-table lookup is always O(n^2).', 'Hash tables cannot perform key lookups.', 'Average-case hash-table lookup is always O(n log n).']],
                ['topic' => 'DFS behavior', 'correct' => 'Depth-first search explores along a path before backtracking.', 'wrong' => ['Depth-first search always visits nodes level by level.', 'Depth-first search is identical to insertion sort.', 'Depth-first search works only on linked lists.']],
                ['topic' => 'priority queues', 'correct' => 'A priority queue removes elements by priority key.', 'wrong' => ['A priority queue always removes oldest element first.', 'A priority queue cannot store numeric keys.', 'A priority queue stores elements without order rules.']],
                ['topic' => 'balanced trees', 'correct' => 'Balanced trees help keep many operations near O(log n).', 'wrong' => ['Balanced trees force all operations to O(1).', 'Balanced trees cannot be searched.', 'Balanced trees always use linear memory scans.']],
                ['topic' => 'heap property', 'correct' => 'In a max-heap, each parent key is greater than or equal to child keys.', 'wrong' => ['In a max-heap, each parent key is always smaller than child keys.', 'A max-heap is always fully sorted.', 'A max-heap root is always the smallest key.']]
            ],
            default => [
                ['topic' => 'core concept', 'correct' => 'This option is the correct technical statement.', 'wrong' => ['This option is technically incorrect.', 'This option does not fit the concept.', 'This option contradicts the concept.']]
            ]
        };
    }

    private function buildQuestion(string $subjectName, string $level, int $seed): array {
        $key = $this->canonicalKey($subjectName);
        $concepts = $this->getConceptBank($key);
        $templates = $this->getTemplates($level);

        $concept = $concepts[$seed % count($concepts)];
        $template = $templates[intdiv($seed, count($concepts)) % count($templates)];
        
        $question = sprintf($template, $concept['topic'], $subjectName) . " (Variant " . ($seed + 1) . ")";
        $options = array_merge([$concept['correct']], $concept['wrong']);

        $shift = $seed % 4;
        $rotated = [];
        for ($i = 0; $i < 4; $i++) {
            $rotated[$i] = $options[($i + $shift) % 4];
        }
        $correctIndex = array_search($concept['correct'], $rotated, true);

        return [
            'question' => $question,
            'option_a' => $rotated[0],
            'option_b' => $rotated[1],
            'option_c' => $rotated[2],
            'option_d' => $rotated[3],
            'correct_answer' => chr(65 + (int)$correctIndex),
            'explanation' => $this->getExplanation($subjectName, $concept['topic'])
        ];
    }

    private function isValidQuestion(array $q): bool {
        foreach (['question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'explanation'] as $k) {
            if (!isset($q[$k]) || trim((string)$q[$k]) === '') {
                return false;
            }
        }
        $opts = [$q['option_a'], $q['option_b'], $q['option_c'], $q['option_d']];
        return count(array_unique($opts)) === 4 && in_array($q['correct_answer'], ['A', 'B', 'C', 'D'], true);
    }
}
?>
