-- Update Schema for Multi-level Difficulty and Explanations

-- Add difficulty level and explanation to question table
ALTER TABLE public.question ADD COLUMN difficulty_level VARCHAR(20) DEFAULT 'Easy';
ALTER TABLE public.question ADD COLUMN explanation TEXT DEFAULT 'No explanation provided.';

-- Add duration to test table
ALTER TABLE public.test ADD COLUMN duration_minutes INTEGER DEFAULT 30;

-- Update subject icons if needed (already handled in code but good to have)
-- Add some sample questions for different levels in Java (Subject ID 1)

-- Medium Level Questions for Java
INSERT INTO public.question (test_id, que_desc, ans1, ans2, ans3, ans4, true_ans, difficulty_level, explanation) VALUES
(1, 'Which of these is not a feature of Java?', 'Object Oriented', 'Use of pointers', 'Portable', 'Dynamic', 2, 'Medium', 'Java does not support pointers to ensure memory safety and security.'),
(1, 'What is the size of float and double in Java?', '32 and 64', '64 and 64', '32 and 32', '64 and 32', 1, 'Medium', 'Float is 32 bits (4 bytes) and Double is 64 bits (8 bytes) in Java.'),
(1, 'Which package contains the Random class?', 'java.util', 'java.lang', 'java.io', 'java.net', 1, 'Medium', 'The Random class is part of the java.util package.');

-- Hard Level Questions for Java
INSERT INTO public.question (test_id, que_desc, ans1, ans2, ans3, ans4, true_ans, difficulty_level, explanation) VALUES
(1, 'What is the output of 0.1 + 0.2 == 0.3 in Java?', 'true', 'false', 'Compilation Error', 'Runtime Error', 2, 'Hard', 'Floating point arithmetic in Java (and most languages) follows IEEE 754, where 0.1 + 0.2 is actually 0.30000000000000004.'),
(1, 'Which method of the Class.class is used to find a method by its name?', 'getMethod()', 'findMethod()', 'searchMethod()', 'queryMethod()', 1, 'Hard', 'getMethod() returns a Method object that reflects the specified public member method of the class.');

-- Add Explanation column to useranswer for review
ALTER TABLE public.useranswer ADD COLUMN explanation TEXT;
