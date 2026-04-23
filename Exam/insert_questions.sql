-- Insert questions mapped to existing schema
-- Table: public.question (test_id, que_desc, ans1, ans2, ans3, ans4, true_ans, difficulty_level, explanation)

DELETE FROM public.question;

INSERT INTO public.question (test_id, que_desc, ans1, ans2, ans3, ans4, true_ans, difficulty_level, explanation) VALUES

-- ================= JAVA EASY =================
(1,'What is JVM?','Java Virtual Machine','Java Variable Method','Just Virtual Machine','None',1,'Easy','JVM executes Java bytecode and enables platform independence'),
(1,'Which keyword defines a class?','function','class','define','object',2,'Easy','class keyword is used to define class'),
(1,'Which data type stores integers?','float','char','int','double',3,'Easy','int stores whole numbers'),

-- ================= JAVA MEDIUM =================
(1,'Which concept is used in inheritance?','Encapsulation','Polymorphism','Extends keyword','Abstraction',3,'Medium','Inheritance uses extends keyword'),
(1,'Which method is entry point?','start()','main()','run()','init()',2,'Medium','main() is entry point'),

-- ================= JAVA HARD =================
(1,'Which exception is unchecked?','IOException','SQLException','NullPointerException','FileNotFoundException',3,'Hard','Runtime exceptions are unchecked'),
(1,'Which collection does not allow duplicates?','List','Set','ArrayList','Vector',2,'Hard','Set does not allow duplicates'),

-- ================= JAVA ADVANCED =================
(1,'Which keyword prevents inheritance?','static','final','const','private',2,'Advanced','final prevents inheritance'),
(1,'Which is thread-safe?','ArrayList','HashMap','Vector','LinkedList',3,'Advanced','Vector is synchronized'),

-- ================= JAVA EXPERT =================
(1,'Which GC algorithm is used in JVM?','Mark and Sweep','Bubble Sort','Quick Sort','DFS',1,'Expert','JVM uses Mark and Sweep'),

-- ================= PYTHON EASY =================
(3,'Python is?','Compiled','Interpreted','Assembly','None',2,'Easy','Python is interpreted language'),
(3,'Which is correct variable?','1name','name1','@name','name#',2,'Easy','Variables cannot start with number'),

-- ================= PYTHON MEDIUM =================
(3,'Which keyword defines function?','func','define','def','function',3,'Medium','def defines function'),
(3,'Which data type is immutable?','List','Dictionary','Tuple','Set',3,'Medium','Tuple is immutable'),

-- ================= PYTHON HARD =================
(3,'Which module handles regex?','math','re','sys','os',2,'Hard','re module handles regex'),
(3,'What is lambda?','Loop','Function','Anonymous function','Variable',3,'Hard','Lambda is anonymous function'),

-- ================= PYTHON ADVANCED =================
(3,'Which is used for OOP?','class','def','lambda','import',1,'Advanced','class defines object'),
(3,'Which supports multiple inheritance?','C','Java','Python','None',3,'Advanced','Python supports multiple inheritance'),

-- ================= PYTHON EXPERT =================
(3,'Which decorator is used?','@staticmethod','@loop','@var','@const',1,'Expert','Decorator modifies function behavior'),

-- ================= C EASY =================
(4,'Entry point of program?','start()','main()','run()','init()',2,'Easy','main is entry point'),
(4,'Header file for IO?','stdio.h','math.h','string.h','conio.h',1,'Easy','stdio.h handles IO'),

-- ================= C MEDIUM =================
(4,'Which loop is entry controlled?','for','do-while','switch','goto',1,'Medium','for loop is entry controlled'),
(4,'Pointer stores?','Value','Address','Index','Loop',2,'Medium','Pointer stores memory address'),

-- ================= C HARD =================
(4,'Which is dynamic memory function?','malloc','printf','scanf','return',1,'Hard','malloc allocates memory'),
(4,'Which operator is dereference?','&','*','#','@',2,'Hard','* is dereference operator'),

-- ================= PHP EASY =================
(2,'PHP stands for?','Personal Home Page','Private Home Page','Public Home Page','None',1,'Easy','PHP originally meant Personal Home Page'),
(2,'Variables start with?','#','$','@','%',2,'Easy','$ is used'),

-- ================= PHP MEDIUM =================
(2,'Which function outputs data?','echo','input','scan','print_r',1,'Medium','echo outputs data'),
(2,'Which symbol is used for concatenation?','+','.','&','%',2,'Medium','. is used'),

-- ================= DATA STRUCTURE EASY =================
(5,'Stack follows?','FIFO','LIFO','Random','None',2,'Easy','Stack is LIFO'),
(5,'Queue follows?','LIFO','FIFO','Random','None',2,'Easy','Queue is FIFO'),

-- ================= DATA STRUCTURE MEDIUM =================
(5,'Which structure uses pointers?','Array','Linked List','Stack','Queue',2,'Medium','Linked list uses pointers'),
(5,'Binary tree max children?','1','2','3','4',2,'Medium','Binary tree has max 2 children'),

-- ================= DATA STRUCTURE HARD =================
(5,'Which traversal is DFS?','Level Order','Preorder','Breadth','None',2,'Hard','Preorder is DFS'),
(5,'Which is non-linear?','Array','Stack','Tree','Queue',3,'Hard','Tree is non-linear');
