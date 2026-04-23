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
                ],
                'Medium' => [
                    ['question' => 'Which of these is not an access modifier in Java?', 'a' => 'public', 'b' => 'private', 'c' => 'protected', 'd' => 'internal', 'correct' => 'D', 'explanation' => 'Java has public, private, protected, and default (no keyword). "internal" is used in languages like C# or Kotlin.'],
                    ['question' => 'How can you prevent a class from being inherited in Java?', 'a' => 'static', 'b' => 'final', 'c' => 'abstract', 'd' => 'private', 'correct' => 'B', 'explanation' => 'Applying the "final" keyword to a class declaration prevents any other class from extending it.'],
                    ['question' => 'What is the default value of a boolean variable in Java?', 'a' => 'true', 'b' => 'null', 'c' => 'false', 'd' => '0', 'correct' => 'C', 'explanation' => 'Primitive boolean variables are initialized to false by default when declared as class members.']
                ],
                'Hard' => [
                    ['question' => 'Which of the following is true about garbage collection in Java?', 'a' => 'You can force it', 'b' => 'It runs in the background', 'c' => 'It deletes active objects', 'd' => 'It only runs when JVM stops', 'correct' => 'B', 'explanation' => 'Garbage collection is an automatic background process. System.gc() only "suggests" the JVM to run it.'],
                    ['question' => 'What is the time complexity of searching an element in a HashMap (average case)?', 'a' => 'O(1)', 'b' => 'O(log n)', 'c' => 'O(n)', 'd' => 'O(n log n)', 'correct' => 'A', 'explanation' => 'HashMap provides constant time performance O(1) for basic operations like get and put, assuming a good hash function.'],
                    ['question' => 'Which method is used to start a thread execution in Java?', 'a' => 'run()', 'b' => 'execute()', 'c' => 'start()', 'd' => 'init()', 'correct' => 'C', 'explanation' => 'The start() method registers the thread with the scheduler and calls the run() method in a separate call stack.']
                ],
                'Advanced' => [
                    ['question' => 'What is a "Deadlock" in Java multithreading?', 'a' => 'A thread finishing execution', 'b' => 'Two threads waiting for each other', 'c' => 'A thread sleeping forever', 'd' => 'CPU running out of memory', 'correct' => 'B', 'explanation' => 'Deadlock occurs when two or more threads are blocked forever, each waiting for the other to release a lock.'],
                    ['question' => 'Which interface should be implemented to make an object "Comparable" in a custom way?', 'a' => 'Comparator', 'b' => 'Comparable', 'c' => 'Serializable', 'd' => 'Cloneable', 'correct' => 'A', 'explanation' => 'The Comparator interface is used for custom sorting logic external to the class, while Comparable is for natural ordering.'],
                    ['question' => 'What is the purpose of the "volatile" keyword?', 'a' => 'To make a variable immutable', 'b' => 'To ensure visibility across threads', 'c' => 'To speed up variable access', 'd' => 'To prevent variable from being cached', 'correct' => 'B', 'explanation' => 'Volatile ensures that the value is always read from and written to main memory, making it visible to all threads.']
                ],
                'Expert' => [
                    ['question' => 'Explain the "PhantomReference" in Java memory management.', 'a' => 'Used for soft caching', 'b' => 'Used for weak links', 'c' => 'Used for pre-mortem cleanup', 'd' => 'A reference that is never null', 'correct' => 'C', 'explanation' => 'Phantom references are used to track when an object has been removed from memory, allowing for cleanup actions.'],
                    ['question' => 'What is the "Happens-Before" relationship in Java Memory Model?', 'a' => 'Time travel in code', 'b' => 'Guarantee of memory visibility', 'c' => 'Execution order of methods', 'd' => 'Priority of threads', 'correct' => 'B', 'explanation' => 'Happens-before defines a guarantee that memory writes by one specific statement are visible to another specific statement.'],
                    ['question' => 'Which GC algorithm is default in Java 17?', 'a' => 'G1 GC', 'b' => 'Parallel GC', 'c' => 'ZGC', 'd' => 'Serial GC', 'correct' => 'A', 'explanation' => 'Garbage First (G1) is the default garbage collector in modern LTS versions of Java like 17.']
                ]
            ],
            'python' => [
                'Easy' => [
                    ['question' => 'How do you create a list in Python?', 'a' => '[]', 'b' => '{}', 'c' => '()', 'd' => '<>', 'correct' => 'A', 'explanation' => 'Square brackets are used to define a list in Python.'],
                    ['question' => 'Which function is used to get the length of a list?', 'a' => 'size()', 'b' => 'length()', 'c' => 'len()', 'd' => 'count()', 'correct' => 'C', 'explanation' => 'The built-in len() function returns the number of items in an object.'],
                    ['question' => 'What is the output of print(2**3)?', 'a' => '6', 'b' => '8', 'c' => '9', 'd' => '5', 'correct' => 'B', 'explanation' => '** is the exponentiation operator in Python. 2 raised to the power of 3 is 8.']
                ],
                'Medium' => [
                    ['question' => 'What is a "List Comprehension"?', 'a' => 'A way to summarize a list', 'b' => 'A concise way to create lists', 'c' => 'A list sorting method', 'd' => 'A list compression tool', 'correct' => 'B', 'explanation' => 'List comprehensions provide a concise way to create lists based on existing iterables.'],
                    ['question' => 'How do you handle exceptions in Python?', 'a' => 'try/catch', 'b' => 'try/except', 'c' => 'do/catch', 'd' => 'handle/error', 'correct' => 'B', 'explanation' => 'Python uses the try and except keywords for exception handling.'],
                    ['question' => 'Which of these is an immutable data type?', 'a' => 'List', 'b' => 'Dictionary', 'c' => 'Set', 'd' => 'Tuple', 'correct' => 'D', 'explanation' => 'Tuples are immutable, meaning their elements cannot be changed after creation.']
                ],
                'Hard' => [
                    ['question' => 'What does the "yield" keyword do?', 'a' => 'Returns a list', 'b' => 'Pauses a function and returns a value', 'c' => 'Stops the execution', 'd' => 'Speeds up loops', 'correct' => 'B', 'explanation' => 'The yield keyword is used in generators to return a value and pause execution so it can be resumed later.'],
                    ['question' => 'What is the "GIL" in Python?', 'a' => 'Global Interpreter Lock', 'b' => 'General Internal Link', 'c' => 'Global Interface Level', 'd' => 'Great Instance Logic', 'correct' => 'A', 'explanation' => 'The Global Interpreter Lock prevents multiple native threads from executing Python bytecodes at once.'],
                    ['question' => 'Which method is used to sort a list in-place?', 'a' => 'sorted()', 'b' => 'arrange()', 'c' => 'sort()', 'd' => 'order()', 'correct' => 'C', 'explanation' => 'list.sort() sorts the list in-place, while sorted() returns a new sorted list.']
                ],
                'Advanced' => [
                    ['question' => 'What is a "Decorator" in Python?', 'a' => 'A tool for UI design', 'b' => 'A function that modifies another function', 'c' => 'A way to comment code', 'd' => 'A class that adds variables', 'correct' => 'B', 'explanation' => 'Decorators allow you to wrap another function in order to extend its behavior without permanently modifying it.'],
                    ['question' => 'What is the purpose of "__init__.py" file?', 'a' => 'To initialize variables', 'b' => 'To mark a directory as a package', 'c' => 'To store program entry point', 'd' => 'To speed up imports', 'correct' => 'B', 'explanation' => 'A directory containing an __init__.py file is treated as a Python package.'],
                    ['question' => 'What is a "Lambda" function?', 'a' => 'A large function', 'b' => 'An anonymous one-line function', 'c' => 'A recursive function', 'd' => 'A function used in maps', 'correct' => 'B', 'explanation' => 'Lambda functions are small, anonymous functions defined with the lambda keyword.']
                ],
                'Expert' => [
                    ['question' => 'Explain "Metaclasses" in Python.', 'a' => 'Classes for UI', 'b' => 'Classes that create classes', 'c' => 'Classes with many parents', 'd' => 'Classes used for metadata', 'correct' => 'B', 'explanation' => 'Metaclasses are the "stuff" that creates classes. They allow for deep customization of class creation.'],
                    ['question' => 'How does "MRO" (Method Resolution Order) work in Python?', 'a' => 'Alphabetical order', 'b' => 'C3 Linearization', 'c' => 'Top to bottom', 'd' => 'Left to right', 'correct' => 'B', 'explanation' => 'Python uses the C3 Linearization algorithm to determine the order in which classes are searched during method lookup.'],
                    ['question' => 'What is the "Walrus Operator" (:=) used for?', 'a' => 'Comparing values', 'b' => 'Assignment within an expression', 'c' => 'Checking for null', 'd' => 'Dividing large numbers', 'correct' => 'B', 'explanation' => 'Introduced in Python 3.8, the walrus operator allows you to assign a value to a variable as part of an expression.']
                ]
            ],
            'php' => [
                'Easy' => [
                    ['question' => 'How do you start a PHP block?', 'a' => '<?php', 'b' => '<php>', 'c' => '<script>', 'd' => '<?', 'correct' => 'A', 'explanation' => 'The standard way to start a PHP script is with <?php.'],
                    ['question' => 'Which function is used to output text?', 'a' => 'print()', 'b' => 'echo', 'c' => 'display()', 'd' => 'Both A and B', 'correct' => 'D', 'explanation' => 'Both echo and print are used to output data to the screen in PHP.'],
                    ['question' => 'How do you define a variable in PHP?', 'a' => 'var name', 'b' => '$name', 'c' => '@name', 'd' => '#name', 'correct' => 'B', 'explanation' => 'All variables in PHP must start with a dollar sign ($).']
                ],
                'Medium' => [
                    ['question' => 'What is the difference between include and require?', 'a' => 'None', 'b' => 'require stops script on failure', 'c' => 'include stops script on failure', 'd' => 'require is faster', 'correct' => 'B', 'explanation' => 'require will produce a fatal error (E_COMPILE_ERROR) and stop the script, while include only produces a warning.'],
                    ['question' => 'Which superglobal holds form data sent via POST?', 'a' => '$_GET', 'b' => '$_REQUEST', 'c' => '$_POST', 'd' => '$_SESSION', 'correct' => 'C', 'explanation' => '$_POST is an associative array of variables passed to the current script via the HTTP POST method.'],
                    ['question' => 'How do you start a session in PHP?', 'a' => 'session_begin()', 'b' => 'start_session()', 'c' => 'session_start()', 'd' => 'session_init()', 'correct' => 'C', 'explanation' => 'session_start() creates a session or resumes the current one based on a session identifier.']
                ],
                'Hard' => [
                    ['question' => 'What are "Traits" in PHP?', 'a' => 'A way to sort arrays', 'b' => 'A mechanism for code reuse', 'c' => 'A type of variable', 'd' => 'A way to handle errors', 'correct' => 'B', 'explanation' => 'Traits are a mechanism for code reuse in single inheritance languages like PHP.'],
                    ['question' => 'What is the purpose of "Composer"?', 'a' => 'To write music', 'b' => 'Dependency management for PHP', 'c' => 'To compile PHP to C', 'd' => 'A PHP IDE', 'correct' => 'B', 'explanation' => 'Composer is the standard tool for managing dependencies and libraries in PHP projects.'],
                    ['question' => 'Which magic method is called when an object is used as a string?', 'a' => '__init()', 'b' => '__string()', 'c' => '__toString()', 'd' => '__convert()', 'correct' => 'C', 'explanation' => 'The __toString() method allows a class to decide how it will react when it is treated like a string.']
                ],
                'Advanced' => [
                    ['question' => 'What is "Dependency Injection"?', 'a' => 'A virus type', 'b' => 'Providing objects to a class', 'c' => 'A way to speed up DB', 'd' => 'None of above', 'correct' => 'B', 'explanation' => 'Dependency Injection is a design pattern where an object receives other objects that it depends on.'],
                    ['question' => 'What does "PSR" stand for in PHP community?', 'a' => 'PHP Standard Recommendation', 'b' => 'PHP Script Runner', 'c' => 'Public System Root', 'd' => 'Primary Source Registry', 'correct' => 'A', 'explanation' => 'PSR stands for PHP Standard Recommendation, developed by the PHP Framework Interop Group.'],
                    ['question' => 'What is the purpose of OpCache?', 'a' => 'To cache database queries', 'b' => 'To cache compiled PHP bytecode', 'c' => 'To cache user sessions', 'd' => 'To cache HTML files', 'correct' => 'B', 'explanation' => 'OpCache improves PHP performance by storing precompiled script bytecode in shared memory.']
                ],
                'Expert' => [
                    ['question' => 'What is "Swoole" in PHP context?', 'a' => 'A new CMS', 'b' => 'An async network framework', 'c' => 'A templating engine', 'd' => 'A security tool', 'correct' => 'B', 'explanation' => 'Swoole is a high-performance networking framework that enables PHP to run as an asynchronous, non-blocking server.'],
                    ['question' => 'Explain "Attributes" introduced in PHP 8.', 'a' => 'Replacement for classes', 'b' => 'Structured metadata for code', 'c' => 'A new way to define variables', 'd' => 'A way to speed up arrays', 'correct' => 'B', 'explanation' => 'Attributes (also known as annotations) offer the ability to add structured, machine-readable metadata information to declarations in code.'],
                    ['question' => 'What is a "Fiber" in PHP 8.1?', 'a' => 'A way to increase memory', 'b' => 'Full-stack coroutines', 'c' => 'A new data type', 'd' => 'A security layer', 'correct' => 'B', 'explanation' => 'Fibers represent full-stack, interruptible functions. They can be used to implement cooperative multitasking.']
                ]
            ],
            'c' => [
                'Easy' => [
                    ['question' => 'Which header file is needed for printf()?', 'a' => 'stdio.h', 'b' => 'conio.h', 'c' => 'stdlib.h', 'd' => 'math.h', 'correct' => 'A', 'explanation' => 'stdio.h (Standard Input Output) contains the declaration for printf().'],
                    ['question' => 'What is the size of an int in C (typically)?', 'a' => '2 bytes', 'b' => '4 bytes', 'c' => '8 bytes', 'd' => '1 byte', 'correct' => 'B', 'explanation' => 'On most modern 32-bit and 64-bit systems, an integer is 4 bytes.'],
                    ['question' => 'How do you comment a single line in C?', 'a' => '#', 'b' => '//', 'c' => '/*', 'd' => '--', 'correct' => 'B', 'explanation' => '// is used for single-line comments in C99 and later standards.']
                ],
                'Medium' => [
                    ['question' => 'What is a pointer in C?', 'a' => 'A variable that stores address', 'b' => 'A way to point at screen', 'c' => 'A recursive function', 'd' => 'A type of array', 'correct' => 'A', 'explanation' => 'A pointer is a variable whose value is the address of another variable.'],
                    ['question' => 'Which operator is used to get the address of a variable?', 'a' => '*', 'b' => '&', 'c' => '->', 'd' => '.', 'correct' => 'B', 'explanation' => 'The ampersand (&) is the address-of operator in C.'],
                    ['question' => 'What is a "struct" in C?', 'a' => 'A way to structure code', 'b' => 'A user-defined data type', 'c' => 'A loop type', 'd' => 'A memory block', 'correct' => 'B', 'explanation' => 'A struct is a user-defined data type that allows grouping variables of different types.']
                ],
                'Hard' => [
                    ['question' => 'What is the purpose of malloc()?', 'a' => 'To clear memory', 'b' => 'Dynamic memory allocation', 'c' => 'To free memory', 'd' => 'To print memory address', 'correct' => 'B', 'explanation' => 'malloc() allocates a block of uninitialized memory dynamically on the heap.'],
                    ['question' => 'What is a "dangling pointer"?', 'a' => 'A pointer pointing to NULL', 'b' => 'A pointer pointing to freed memory', 'c' => 'A pointer with no name', 'd' => 'A pointer inside a loop', 'correct' => 'B', 'explanation' => 'A dangling pointer arises when an object is deleted or deallocated, without modifying the value of the pointer.'],
                    ['question' => 'What is the output of sizeof(void)?', 'a' => '0', 'b' => '1', 'c' => 'Error', 'd' => 'Depends on compiler', 'correct' => 'C', 'explanation' => 'The void type has no size, and applying sizeof to it results in a compilation error.']
                ],
                'Advanced' => [
                    ['question' => 'What is a "Function Pointer"?', 'a' => 'A pointer to a variable', 'b' => 'A pointer that stores method address', 'c' => 'A function that returns pointer', 'd' => 'None of above', 'correct' => 'B', 'explanation' => 'A function pointer is a pointer that points to the entry point of a function.'],
                    ['question' => 'What is "Memory Leak"?', 'a' => 'Fast memory access', 'b' => 'Unused memory not released', 'c' => 'CPU overheating', 'd' => 'Disk space running out', 'correct' => 'B', 'explanation' => 'A memory leak occurs when a program fails to release memory that it no longer needs.'],
                    ['question' => 'What is the "Volatile" qualifier in C?', 'a' => 'Variable can change unexpectedly', 'b' => 'Variable is constant', 'c' => 'Variable is fast', 'd' => 'Variable is local', 'correct' => 'A', 'explanation' => 'Volatile tells the compiler that the value of the variable may change at any time without any action being taken by the code.']
                ],
                'Expert' => [
                    ['question' => 'What is "Buffer Overflow"?', 'a' => 'Too much data in disk', 'b' => 'Writing past memory boundaries', 'c' => 'Network speed issue', 'd' => 'Database slow down', 'correct' => 'B', 'explanation' => 'Buffer overflow occurs when more data is written to a block of memory, or buffer, than it is configured to hold.'],
                    ['question' => 'Explain "Bit-fields" in C structs.', 'a' => 'Fields for large numbers', 'b' => 'Using specific number of bits', 'c' => 'Fields for binary files', 'd' => 'None of above', 'correct' => 'B', 'explanation' => 'Bit-fields allow the programmer to specify the exact number of bits each member should occupy.'],
                    ['question' => 'What is "Alignment Padding" in structs?', 'a' => 'Extra space for UI', 'b' => 'Memory alignment for performance', 'c' => 'Adding zeros to numbers', 'd' => 'None of above', 'correct' => 'B', 'explanation' => 'Alignment padding is added by compilers to ensure that struct members are aligned at address boundaries suitable for the CPU.']
                ]
            ],
            'ds' => [
                'Easy' => [
                    ['question' => 'What is the time complexity of accessing an element in an array by index?', 'a' => 'O(1)', 'b' => 'O(n)', 'c' => 'O(log n)', 'd' => 'O(n^2)', 'correct' => 'A', 'explanation' => 'Arrays allow direct access via index, which is a constant time operation.'],
                    ['question' => 'Which data structure follows LIFO?', 'a' => 'Queue', 'b' => 'Stack', 'c' => 'Linked List', 'd' => 'Tree', 'correct' => 'B', 'explanation' => 'Stack follows Last-In-First-Out (LIFO) principle.'],
                    ['question' => 'Which data structure follows FIFO?', 'a' => 'Stack', 'b' => 'Queue', 'c' => 'Array', 'd' => 'Graph', 'correct' => 'B', 'explanation' => 'Queue follows First-In-First-Out (FIFO) principle.']
                ],
                'Medium' => [
                    ['question' => 'What is the main advantage of a Linked List over an Array?', 'a' => 'Faster access', 'b' => 'Dynamic size', 'c' => 'Less memory usage', 'd' => 'Easier to sort', 'correct' => 'B', 'explanation' => 'Linked lists can grow or shrink in size dynamically without requiring a contiguous block of memory.'],
                    ['question' => 'What is a "Binary Search Tree"?', 'a' => 'A tree with 2 nodes', 'b' => 'Left < Root < Right', 'c' => 'Root < Left < Right', 'd' => 'A sorted list', 'correct' => 'B', 'explanation' => 'In a BST, the left subtree contains values less than the root, and the right subtree contains values greater.'],
                    ['question' => 'Which sorting algorithm has the best average time complexity?', 'a' => 'Bubble Sort', 'b' => 'Selection Sort', 'c' => 'Merge Sort', 'd' => 'Insertion Sort', 'correct' => 'C', 'explanation' => 'Merge Sort has a consistent average and worst-case complexity of O(n log n).']
                ],
                'Hard' => [
                    ['question' => 'What is "Hashing"?', 'a' => 'Encrypting data', 'b' => 'Mapping data to a fixed size', 'c' => 'Sorting data', 'd' => 'Searching data', 'correct' => 'B', 'explanation' => 'Hashing is the process of mapping data of arbitrary size to fixed-size values using a hash function.'],
                    ['question' => 'What is a "Max Heap"?', 'a' => 'Root is the smallest', 'b' => 'Root is the largest', 'c' => 'A balanced tree', 'd' => 'A complete graph', 'correct' => 'B', 'explanation' => 'In a Max Heap, for any given node, its value is greater than or equal to the values of its children.'],
                    ['question' => 'What is the time complexity of Binary Search?', 'a' => 'O(n)', 'b' => 'O(log n)', 'c' => 'O(1)', 'd' => 'O(n log n)', 'correct' => 'B', 'explanation' => 'Binary search repeatedly divides the search interval in half, leading to logarithmic complexity.']
                ],
                'Advanced' => [
                    ['question' => 'What is "Dynamic Programming"?', 'a' => 'Writing code fast', 'b' => 'Solving subproblems and storing results', 'c' => 'Programming with animations', 'd' => 'None of above', 'correct' => 'B', 'explanation' => 'DP is an optimization technique that solves complex problems by breaking them into overlapping subproblems.'],
                    ['question' => 'What is an "AVL Tree"?', 'a' => 'A tree with many levels', 'b' => 'A self-balancing BST', 'c' => 'A tree used for storage', 'd' => 'None of above', 'correct' => 'B', 'explanation' => 'An AVL tree is a self-balancing binary search tree where the height difference between subtrees is at most 1.'],
                    ['question' => 'What is "Dijkstra Algorithm" used for?', 'a' => 'Sorting list', 'b' => 'Finding shortest path in graph', 'c' => 'Searching in tree', 'd' => 'Compressing files', 'correct' => 'B', 'explanation' => 'Dijkstra algorithm is used to find the shortest path from a source node to all other nodes in a weighted graph.']
                ],
                'Expert' => [
                    ['question' => 'What is a "B-Tree"?', 'a' => 'Binary Tree', 'b' => 'Self-balancing search tree for databases', 'c' => 'Balanced Tree', 'd' => 'Basic Tree', 'correct' => 'B', 'explanation' => 'B-Trees are optimized for systems that read and write large blocks of data, commonly used in databases and file systems.'],
                    ['question' => 'What is "Time-Space Tradeoff"?', 'a' => 'Buying more RAM', 'b' => 'Decreasing time at cost of memory', 'c' => 'Making code shorter', 'd' => 'None of above', 'correct' => 'B', 'explanation' => 'A tradeoff where we use more memory (e.g., caching) to achieve faster execution time.'],
                    ['question' => 'What is "NP-Complete"?', 'a' => 'Non-Printable code', 'b' => 'Problems that can be verified in polynomial time', 'c' => 'Easy problems', 'd' => 'None of above', 'correct' => 'B', 'explanation' => 'NP-Complete problems are the hardest problems in NP, and their solutions can be verified quickly but no efficient way to find them is known.']
                ]
            ]
        ];

        return $banks[$subject][$level] ?? [];
    }
}
