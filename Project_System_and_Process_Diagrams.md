# Project System & Process Diagrams Catalog
### Visualizing the Enterprise Online Examination & Learning Management Suite

This document consolidates **31 core diagrams** mapping out the **Project Management**, **Process & Workflows**, **Technical & System Designs**, and **Advanced Security, Runtime States & Logic Diagrams** of the Exam Portal suite using standard Mermaid.js notation.

---

## 1. Project Management Diagrams

### A. Project Network Diagram (Critical Path Method)
This diagram maps task dependencies, early start/finish times, and durations to establish the **Critical Path** (marked with double-lined nodes and bold borders) required to build the entire suite.

```mermaid
graph TD
    classDef critical fill:#f96,stroke:#333,stroke-width:4px;
    classDef normal fill:#bbf,stroke:#333,stroke-width:1px;

    T1["T1: DB Schema & Normalization (4d)<br/>ES: 0 | EF: 4"]:::critical --> T2["T2: Auth & RBAC Setup (3d)<br/>ES: 4 | EF: 7"]:::critical
    T2 --> T3["T3: Traditional Quiz Engine (5d)<br/>ES: 7 | EF: 12"]:::critical
    T2 --> T4["T4: Teacher CRUD Panels (4d)<br/>ES: 7 | EF: 11"]
    
    T3 --> T5["T5: Simulated Proctor Console (6d)<br/>ES: 12 | EF: 18"]
    T3 --> T6["T6: Offline State Caching (4d)<br/>ES: 12 | EF: 16"]
    T3 --> T7["T7: AI Adaptive Testing IRT (7d)<br/>ES: 12 | EF: 19"]:::critical
    T4 --> T8["T8: Subjective Plagiarism Engine (5d)<br/>ES: 11 | EF: 16"]

    T7 --> T9["T9: Skills Passport Gamification (4d)<br/>ES: 19 | EF: 23"]:::critical
    T9 --> T10["T10: Public Registry Signer (5d)<br/>ES: 23 | EF: 28"]:::critical
    
    T6 --> T11["T11: Performance Analytics (4d)<br/>ES: 16 | EF: 20"]
    T8 --> T11
    
    T10 --> T12["T12: E2E Security Audit (3d)<br/>ES: 28 | EF: 31"]:::critical
    T11 --> T12

    style T1 fill:#ff9999,stroke:#cc0000,stroke-width:4px
    style T2 fill:#ff9999,stroke:#cc0000,stroke-width:4px
    style T3 fill:#ff9999,stroke:#cc0000,stroke-width:4px
    style T7 fill:#ff9999,stroke:#cc0000,stroke-width:4px
    style T9 fill:#ff9999,stroke:#cc0000,stroke-width:4px
    style T10 fill:#ff9999,stroke:#cc0000,stroke-width:4px
    style T12 fill:#ff9999,stroke:#cc0000,stroke-width:4px
```

---

### B. High-Level Project Roadmap & Milestones
This Gantt roadmap outlines the timeline and critical milestones across the five primary development streams.

```mermaid
gantt
    title Exam Portal Development Roadmap & Milestones
    dateFormat  YYYY-MM-DD
    section Phase 1: Foundation
    Database Modeling & Constraints    :active, des1, 2026-05-01, 4d
    RBAC Gateways & Session Security   :active, des2, 2026-05-05, 3d
    section Phase 2: Assessment Core
    Linear Exam Core Engine            :crit, des3, 2026-05-08, 5d
    Question Bank Form Syntax Preview  :des4, after des2, 4d
    section Phase 3: Telemetry
    Live Proctor Telemetry Heartbeat   :des5, after des3, 6d
    Offline-Safe LocalStorage Sync     :des6, after des3, 4d
    section Phase 4: Pedagogy
    AI-Driven IRT Testing              :crit, des7, after des3, 7d
    Subjective Similarity Plagiarism   :des8, after des4, 5d
    section Phase 5: Verification
    Skills Passport Badge Engine       :crit, des9, after des7, 4d
    Public Verification Registry       :crit, des10, after des9, 5d
```

---

## 2. Process & Workflow Diagrams

### A. Standard Flowchart: Candidate Exam Session Loop
This flowchart maps the logical steps a student follows from the portal login down to certificate issuance, including network status forks and proctor warnings.

```mermaid
flowchart TD
    Start([Student Landing]) --> Login[Login index.php / authentication]
    Login --> RoleCheck{Role Check?}
    
    RoleCheck -->|Admin/Teacher| AdminDash[Admin Console Dashboard]
    RoleCheck -->|Student| Dashboard[Dashboard Catalog subject.php]
    
    Dashboard --> SelectExam[Select Course Category]
    SelectExam --> ModeCheck{Exam Mode?}
    
    ModeCheck -->|Practice / Mock| MockExam[Mock Exam - No Graded Attempts]
    ModeCheck -->|Official / Adaptive| OfficialExam[Launch Exam session]
    
    OfficialExam --> RenderQuestion[Display Active Question Block]
    RenderQuestion --> SelectOption[Select Answer Choice]
    
    SelectOption --> NetCheck{Connection Status?}
    NetCheck -->|Offline| SaveLocal[Cache Answer in Browser LocalStorage]
    SaveLocal --> OfflineWarning[Display Working Offline Banner]
    OfflineWarning --> NextQuestion{More Questions?}
    
    NetCheck -->|Online| SyncServer[Submit AJAX Sync response to DB]
    SyncServer --> NextQuestion
    
    NextQuestion -->|Yes| RenderQuestion
    NextQuestion -->|No / Time Limit| SubmitExam[Compile answers & POST payload]
    
    SubmitExam --> SyncCheck{Cached Offline Answers?}
    SyncCheck -->|Yes| PushCache[Flush LocalStorage & replay answers payload]
    PushCache --> Plagiarism[Trigger Plagiarism Check Levenshtein comparison]
    SyncCheck -->|No| Plagiarism
    
    Plagiarism --> ScoreCalc[Calculate percentage, correct count, and IRT ratings]
    ScoreCalc --> GradeReport[Render results details review.php]
    
    GradeReport --> PassCheck{Achieved Grade >= 50%?}
    PassCheck -->|No| LeadPodium[Show Results summary]
    PassCheck -->|Yes| IssueCert[Generate Crypto-Signed Key & render gold certificate]
    IssueCert --> EarnBadges[Assign Digital Badges to Skills Passport]
    
    EarnBadges --> End([Complete Session])
    LeadPodium --> End
```

---

### B. Cross-Functional Swimlane Diagram
This lane chart splits student actions, instructor commands, server-side controller processing, and database commitments.

```mermaid
flowchart TB
    subgraph Student Client (Browser Domain)
        S1[Select Exam in Dashboard] --> S2[Enter quiz.php Workspace]
        S2 --> S3[Answer MCQ Selection]
        S3 --> S4[3s Asynchronous Heartbeat Polls]
        S5[Display Proctor Blurred Pause Overlay]
        S6[Submit Completed Attempt JSON]
    end

    subgraph Teacher / Proctor Command GUI
        P1[Set Cohort Access Calendars]
        P2[Monitor Candidate Live Telemetry Grid]
        P3[Trigger Warning Alert OR Pause command]
    end

    subgraph Application Middleware Controller (PHP 8.x)
        A1[Enforce Session Guard & Role validation]
        A2[Process Proctor Telemetry Checks]
        A3[Adaptive Engine Level Shifter]
        A4[Levenshtein Plagiarism String Scanner]
        A5[Cryptographic Signature Certification generator]
    end

    subgraph Database Persistence Layer (MySQL/MariaDB)
        D1[(Validate Cohort Schedules)]
        D2[(Update Attempt proctor_paused Status)]
        D3[(Commit Answer to attempt_answers)]
        D4[(Log Plagiarism Similarity Score)]
        D5[(Store Signatures & Passwords)]
    end

    P1 --> D1
    D1 --> S1
    S2 --> A1
    A1 --> S3
    S3 --> D3
    S4 --> A2
    A2 <--> P2
    P3 --> D2
    D2 --> S4
    S4 --> S5
    S6 --> A4
    A4 --> D4
    A3 --> S3
    A5 --> D5
```

---

### C. BPMN: End-to-End Examination Lifecycle
This BPMN model maps the business flow between the Candidate Pool and the Proctor Pool across various gateways.

```mermaid
flowchart TD
    subgraph Invigilator & Teacher Business Pool
        TStart((Start Event)) --> TCreate[Define Question Database Catalog]
        TCreate --> TSchedule[Set Classroom Release Calendars]
        TSchedule --> TActive((Exam Goes Live))
        TActive --> TMonitor[Open Live Proctoring Dashboard]
        TMonitor --> TTelemetry{Monitor Telemetry}
        TTelemetry -->|Cheating Alert| TWarn[Issue Screen Warning to Browser]
        TTelemetry -->|Session Complete| TGrade[Validate Subjective Answers]
        TTelemetry -->|Connection Loss| TPause[Pause Timer Remote Command]
        TWarn --> TTelemetry
        TPause --> TTelemetry
        TGrade --> TEnd((End Event))
    end

    subgraph Student Candidate Business Pool
        CStart((Start Event)) --> CLogin[User authentication index.php]
        CLogin --> CEnroll{Cohort Open?}
        CEnroll -->|No| CWait[Wait for Release Schedule]
        CEnroll -->|Yes| CLaunch[Initialize Graded Attempt]
        CLaunch --> CSolve[Complete MCQ Sets]
        CSolve --> CNetCheck{Connection?}
        CNetCheck -->|Offline| CLocal[Buffer in LocalStorage]
        CNetCheck -->|Online| CHeartbeat[Transmit AJAX answer telemetry]
        CLocal --> CHeartbeat
        CHeartbeat --> CProctorState{Proctor Paused?}
        CProctorState -->|Yes| CWaitScreen[Lock Screen Display]
        CProctorState -->|No| CSubmit[Send exam submission]
        CWaitScreen --> CSolve
        CSubmit --> CResults[View Performance Grades]
        CResults --> CVerify[Access Signed Certification Registry]
        CVerify --> CEnd((End Event))
    end

    CLaunch <--> TMonitor
    CHeartbeat <--> TTelemetry
    TWarn <--> CSolve
    TPause <--> CProctorState
```

---

### D. Value Stream Map: Information and Asset Flows
This maps the lead time and cycle times for academic assets (questions, answers, certificates) moving from preparation to evaluation.

```mermaid
flowchart TD
    subgraph Information Flow
        Admin[Admin / Teacher Coordinator] -->|1. Setup Schedules & Syllabus| CoreDB[(Core Database)]
        Proctor[Live Proctor Console] -->|2. Asynchronous Controls| SessionCtrl[Attempt Heartbeat Control]
    end

    subgraph Production Flow (Value-Add Path)
        RawQ[Unsolved Questions Repository] -->|1. Pull Pool| Quiz[Active quiz.php Session]
        Quiz -->|2. Buffer Answers| LocalState[Client LocalStorage]
        LocalState -->|3. Sync AJAX| GradedState[Evaluated Attempt Answers]
        GradedState -->|4. Algorithmic Grading| CertGen[Signed Certificates Registry]
    end

    subgraph Timelines (Cycle Time vs. Delay)
        TimeLine1["Question Pull Delay: 50ms"] -.-> Quiz
        TimeLine2["Student Solving Time: 45 min"] -.-> LocalState
        TimeLine3["Network Sync Time: 300ms"] -.-> GradedState
        TimeLine4["Levenshtein Check Delay: 2s"] -.-> CertGen
    end
```

---

### E. Student Exam Dashboard Visibility & Scheduling Resolution Flowchart
This maps the access verification pathway executed dynamically in `subject.php` to resolve classroom calendar availability for candidates.

```mermaid
flowchart TD
    Start([Access subject.php Dashboard]) --> FetchUser[Get $_SESSION user_id & cohort_memberships]
    FetchUser --> QueryCohorts[Query active cohort_id's enrolled]
    QueryCohorts --> GetSubjects[Fetch subjects linked to Cohorts via subject_cohorts]
    GetSubjects --> LoopSubjects{For each Subject record}
    
    LoopSubjects -->|Next Subject| GetTimes[Retrieve subject_cohorts opens_at & closes_at]
    GetTimes --> CheckTime{Current Time within opens_at and closes_at?}
    
    CheckTime -->|No: Before opens_at| CardLocked[Render Locked card: 'Release Pending' with countdown]
    CheckTime -->|No: After closes_at| CardClosed[Render Closed card: 'Deadline Passed']
    
    CheckTime -->|Yes| QueryAttempts[Query exam_attempts count for user & subject]
    QueryAttempts --> CheckAttempts{Any completed official attempt?}
    
    CheckAttempts -->|Yes| CardCompleted[Render Review card: 'View Results' & passport link]
    CheckAttempts -->|No| CheckActive{Active incomplete attempt exists?}
    
    CheckActive -->|Yes| CardResume[Render Resume card: 'Resume Exam' with active attempt_id]
    CheckActive -->|No| CardActive[Render Active card: 'Start Exam' with validation key]
    
    CardLocked & CardClosed & CardCompleted & CardResume & CardActive --> LoopSubjects
    LoopSubjects -->|Done| End([Dashboard Catalog Rendered])
```

---

### F. Skills Passport Gamification Rule & Achievement Engine Decision Matrix
This visualizes the rule pipelines executed on attempt submission to reward and commit digital credentials into `user_badges`.

```mermaid
flowchart TD
    Start[Exam Submission Hook Triggered] --> GetAttempt[Fetch exam_attempts score, percentage & duration]
    GetAttempt --> GetSubject[Identify subject_name & cohort_id]
    
    subgraph Java Badge Path
        CheckJava{Subject == 'Java'?} -->|Yes| JavaPass{Percentage >= 50%?}
        JavaPass -->|Yes| AwardJava[Unlock Badge: 'Java Artisan']
        JavaPass -->|No| JavaDone[No Action]
        CheckJava -->|No| JavaDone
    end
    
    subgraph PHP Badge Path
        CheckPHP{Subject == 'PHP'?} -->|Yes| PHPHard{Difficulty IN 'Hard', 'Advanced'?}
        PHPHard -->|Yes| PHPPass{Percentage >= 50%?}
        PHPPass -->|Yes| AwardPHP[Unlock Badge: 'PHP Master']
        PHPPass -->|No| PHPDone[No Action]
        PHPHard -->|No| PHPDone
        CheckPHP -->|No| PHPDone
    end
    
    subgraph Speedster Badge Path
        CalcTime[Calculate Duration: submitted_at - started_at] --> SpeedCheck{Duration < 300 seconds?}
        SpeedCheck -->|Yes| SpeedPass{Percentage >= 50%?}
        SpeedPass -->|Yes| AwardSpeed[Unlock Badge: 'Turbo Speedster']
        SpeedPass -->|No| SpeedDone[No Action]
        SpeedCheck -->|No| SpeedDone
    end
    
    subgraph Perfection Badge Path
        ScoreCheck{Score == total_questions?} -->|Yes| AwardPerfect[Unlock Badge: 'Absolute Perfection']
        ScoreCheck -->|No| PerfectDone[No Action]
    end

    AwardJava & AwardPHP & AwardSpeed & AwardPerfect --> SaveDB[Insert into user_badges values]
    JavaDone & PHPDone & SpeedDone & PerfectDone --> CheckComplete{All evaluated?}
    
    SaveDB --> CheckComplete
    CheckComplete --> End([Passport Sync Complete])
```

---

## 3. Technical & System Design Diagrams

### A. UML Class Diagram: Portal Software Blueprints
This class diagram specifies database properties, logic functions, and associations between core system classes.

```mermaid
classDiagram
    class User {
        +int id
        +string username
        +string email
        +string password_hash
        +string role
        +register() bool
        +login() bool
    }

    class Subject {
        +int id
        +string name
        +getOpenExams(int cohort_id) array
    }

    class Question {
        +int id
        +int subject_id
        +string level
        +string type
        +string question_text
        +string correct_answer
        +string explanation
        +saveToDatabase() bool
    }

    class Cohort {
        +int id
        +string name
        +addMember(int user_id) bool
        +bindSubjectSchedule(int subject_id, string opens, string closes) bool
    }

    class ExamAttempt {
        +int id
        +string verification_key
        +int user_id
        +int subject_id
        +int score
        +float percentage
        +string proctor_status
        +int proctor_paused
        +int time_remaining_sec
        +calculateFinalPercentage() float
        +pauseAttempt() void
        +terminateAttempt() void
    }

    class AttemptAnswer {
        +int id
        +int attempt_id
        +int question_id
        +string selected_answer
        +int is_correct
        +float similarity_score
        +saveChoice() bool
    }

    class PlagiarismChecker {
        +calculateLevenshtein(string target, string source) float
        +calculateJaroWinkler(string target, string source) float
        +evaluateSimilarity(int attempt_id) array
    }

    class AdaptiveEngine {
        +int attempt_id
        +array history_levels
        +evaluateNextQuestion(int current_answer_status) int
        +calculateIRTFinalScore() float
    }

    User "1" --> "many" ExamAttempt : Starts
    Subject "1" --> "many" Question : Contains
    Cohort "many" --> "many" User : Enrolls
    Cohort "many" --> "many" Subject : Schedules
    ExamAttempt "1" --> "many" AttemptAnswer : Records
    Question "1" --> "many" AttemptAnswer : References
    ExamAttempt --> AdaptiveEngine : Adapts
    AttemptAnswer --> PlagiarismChecker : Audits
```

---

### B. UML Sequence Diagram: Telemetry Polling & Proctor Control
This sequence details the interactions between the Student's browser, the heartbeat handler, the proctor's dashboard, and the database.

```mermaid
sequenceDiagram
    autonumber
    actor Student as Student Browser (quiz.php)
    participant Server as Heartbeat Handler (proctor_check.php)
    actor Proctor as Proctor Console (proctor_console.php)
    participant DB as MySQL Database Store

    Note over Student, DB: Asynchronous Invigilation Heartbeat Loop
    loop Every 3 Seconds
        Student->>Server: HTTP POST / GET payload (attempt_id, status)
        Server->>DB: Query attempt status (SELECT proctor_status, proctor_paused, time_remaining)
        DB-->>Server: Return active status properties
        Server-->>Student: Return JSON payload (proctor_status, proctor_paused, time_remaining)
    end

    Note over Proctor, DB: Proctor Intervention Execution
    Proctor->>DB: Click "Pause Exam" (UPDATE exam_attempts SET proctor_paused = 1 WHERE id = ?)
    DB-->>Proctor: Confirm database lock

    Note over Student, Server: Next Heartbeat Interaction
    Student->>Server: HTTP POST payload (attempt_id, status)
    Server->>DB: Query attempt status
    DB-->>Server: Return updated metrics (proctor_paused = 1)
    Server-->>Student: Return JSON payload (proctor_paused: 1, timer: frozen)
    
    Note over Student: Browser triggers window overlay blur and halts local countdown timers
```

---

### C. Entity-Relationship Diagram (ERD): Logical Database Schemas
This designs the database tables, indices, data types, and primary-foreign key linkages.

```mermaid
erDiagram
    users {
        int id PK
        string f_name
        string m_name
        string l_name
        string u_name UK
        string u_email UK
        string u_pass
        int u_age
        string u_mob
        text u_adr
        enum role
        timestamp created_at
    }

    subjects {
        int id PK
        string name UK
    }

    questions {
        int id PK
        int subject_id FK
        enum level
        enum type
        text question
        text option_a
        text option_b
        text option_c
        text option_d
        string correct_answer
        text explanation
    }

    cohorts {
        int id PK
        string name UK
        timestamp created_at
    }

    cohort_members {
        int id PK
        int cohort_id FK
        int user_id FK
        timestamp created_at
    }

    subject_cohorts {
        int id PK
        int subject_id FK
        int cohort_id FK
        datetime opens_at
        datetime closes_at
    }

    badges {
        int id PK
        string name UK
        text description
        string icon
        string condition_type
        string condition_value
    }

    user_badges {
        int id PK
        int user_id FK
        int badge_id FK
        timestamp unlocked_at
    }

    exam_attempts {
        int id PK
        string verification_key UK
        int user_id FK
        int subject_id FK
        enum level
        int total_questions
        int score
        decimal percentage
        datetime started_at
        datetime submitted_at
        enum exam_mode
        enum exam_type
        enum proctor_status
        tinyint proctor_paused
        int time_remaining_sec
    }

    attempt_answers {
        int id PK
        int attempt_id FK
        int question_id FK
        string selected_answer
        tinyint is_correct
        decimal similarity_score
    }

    users ||--o{ cohort_members : enlists
    cohorts ||--o{ cohort_members : holds
    subjects ||--o{ subject_cohorts : bounds
    cohorts ||--o{ subject_cohorts : schedules
    subjects ||--o{ questions : categorizes
    users ||--o{ exam_badges : collects
    badges ||--o{ exam_badges : unlocks
    users ||--o{ exam_attempts : performs
    subjects ||--o{ exam_attempts : tests
    exam_attempts ||--o{ attempt_answers : lists
    questions ||--o{ attempt_answers : matches
```

---

### D. Data Flow Diagram (DFD): Information Flow Dynamics
This Level-1 DFD tracks data transformations as information passes through the various verification pipelines.

```mermaid
flowchart TD
    subgraph Data Sources & Dest (External Entities)
        Student([Student Candidate])
        Proctor([Proctor Invigilator])
        Employer([Public Employer])
    end

    subgraph Process Workflows
        P1[1.0 Authenticate Session]
        P2[2.0 Proctor Telemetry Controller]
        P3[3.0 Question Shifter IRT Engine]
        P4[4.0 Plagiarism Evaluator Engine]
        P5[5.0 Certificate registry validation]
    end

    subgraph Data Stores
        D1[(User Directory)]
        D2[(Attempts Ledger)]
        D3[(Question Inventory)]
    end

    Student -->|1. Credentials| P1
    P1 -->|Query Credentials| D1
    D1 -->|Active Session Token| P1
    P1 -->|Redirect Session Status| Student

    Student -->|2. Hearts & Telemetry state| P2
    Proctor -->|3. Pause/Warn Commands| P2
    P2 -->|Log states| D2
    D2 -->|Sync session values| P2
    P2 -->|Screen Lock Indicators| Student

    Student -->|4. Answer selections| P3
    P3 -->|Evaluate Performance level| D3
    D3 -->|Higher/Lower difficulty MCQs| P3
    P3 -->|Update current grades| D2

    Student -->|5. Submit Exam attempt| P4
    P4 -->|Run Levenshtein match checks| D2
    D2 -->|Similarity Flags & grading| P4
    P4 -->|Write plagiarism score| D2

    Employer -->|6. Search Certification Key| P5
    P5 -->|Query Key validations| D2
    D2 -->|Grading Verification data| P5
    P5 -->|Gold Certificate Visual render| Employer
```

---

### E. System Architecture Diagram: Deployment Topologies
This details the production and intranet deployment infrastructure for high-concurrency event windows.

```mermaid
flowchart TD
    subgraph Public Internet Tier
        Client[Student Candidate Client Browser]
        VerifyViewer[Public Credential Registry Viewer]
    end

    subgraph DMZ Routing & Security Tier
        NginxProxy[Nginx Load Balancer & Reverse Proxy<br/>- Rate Limiting<br/>- SSL/TLS Termination<br/>- Static CSS/JS Asset Caching]
    end

    subgraph Internal Application Tier
        ApacheWS1[Apache Web Server - Worker 1<br/>PHP 8.x Processing Engine]
        ApacheWS2[Apache Web Server - Worker 2<br/>PHP 8.x Processing Engine]
        SessionStore[(Redis / Memcached<br/>Session Store & Dynamic States)]
    end

    subgraph High-Availability Secure Database Tier
        DBPrimary[(MariaDB 10.x Primary DB<br/>ACID InnoDB Transaction Logs)]
        DBReplica[(MariaDB 10.x Replica DB<br/>Read-Only Analytics Queries)]
    end

    Client -->|HTTPS Port 443| NginxProxy
    VerifyViewer -->|HTTPS Port 443| NginxProxy

    NginxProxy -->|HTTP Port 80 Round-Robin| ApacheWS1
    NginxProxy -->|HTTP Port 80 Round-Robin| ApacheWS2

    ApacheWS1 <-->|Active Session Handshake| SessionStore
    ApacheWS2 <-->|Active Session Handshake| SessionStore

    ApacheWS1 -->|Prepared SQL Write Statements| DBPrimary
    ApacheWS2 -->|Prepared SQL Write Statements| DBPrimary

    DBPrimary -->|Asynchronous Master-Slave Mirroring| DBReplica
    ApacheWS1 -.->|Read-Only Performance Reports| DBReplica
    ApacheWS2 -.->|Read-Only Performance Reports| DBReplica
```

---

### F. Cryptographic Certificate Signing & Public Verification Ceremony Sequence Diagram
This maps the generation of HMAC SHA-256 signatures for passing students and the public search and verification checks on `verify.php`.

```mermaid
sequenceDiagram
    autonumber
    actor Student as Student Client (quiz.php)
    participant PHP as Certificate Signer (review.php)
    actor Public as Employer Viewer (verify.php)
    participant DB as MariaDB database

    Note over Student, PHP: Exam Completion Phase
    Student->>PHP: Post final responses payload
    PHP->>PHP: Grade attempt (score >= 50%)
    
    Note over PHP: Cryptographic Signature Sealing
    PHP->>PHP: Extract: user_id, subject_id, score, percentage, timestamp
    PHP->>PHP: Concat values into raw text block
    PHP->>PHP: Generate SHA-256 HMAC (data, server_secret_pepper)
    PHP->>PHP: Convert hash to 32-character hexadecimal key (verification_key)
    
    PHP->>DB: UPDATE exam_attempts SET verification_key = ?, percentage = ?, score = ?
    DB-->>PHP: DB Commit success
    PHP-->>Student: Render Gold-Foil Certificate with verification_key printed
    
    Note over Public, DB: Public Verification Ceremony
    Public->>Public: Input printed verification_key into verify.php search
    Public->>PHP: Post search inquiry
    PHP->>DB: SELECT * FROM exam_attempts JOIN users JOIN subjects WHERE verification_key = ?
    DB-->>PHP: Return single attempt & user record
    
    alt Verification Match Found
        PHP->>PHP: Regenerate SHA-256 HMAC using DB record values & secret_pepper
        PHP->>PHP: Compare generated hash with verification_key
        alt Hash Signature Valid
            PHP-->>Public: Render Gold Seal Verified status, Student name, Subject, Grade & Date
        else Hash Mismatch / Tampered
            PHP-->>Public: Render Red Warning: 'Tampered Certificate Signature!'
        end
    else Record Not Found
        PHP-->>Public: Render 'Invalid Certificate Hash: No record exists'
    end
```

---

### G. Multi-Tenant Cohort Database Compartmentalization Object Diagram
This details structural relationships illustrating how databases block cross-tenant visibility leaks between cohort boundaries.

```mermaid
graph TD
    subgraph Multi-Tenant Database Structures
        direction TB
        C1["Cohort A Object: CSCI-101 (id: 1)"]
        C2["Cohort B Object: CSCI-102 (id: 2)"]
        
        S1["Subject Object: Database Systems (id: 10)"]
        S2["Subject Object: Software Engineering (id: 11)"]
        
        SC1["subject_cohorts A-10:<br/>opens_at: 09:00 | closes_at: 11:00"]
        SC2["subject_cohorts B-10:<br/>opens_at: 14:00 | closes_at: 16:00"]
        
        U1["User Student-1 (id: 101)<br/>Role: Student"]
        U2["User Student-2 (id: 102)<br/>Role: Student"]
        U3["User Student-3 (id: 103)<br/>Role: Student"]
        
        CM1["cohort_members: User 101 -> Cohort A"]
        CM2["cohort_members: User 102 -> Cohort A"]
        CM3["cohort_members: User 103 -> Cohort B"]
        
        EA1["exam_attempts: Attempt #1 (User 101)<br/>Cohort Context: CSCI-101"]
        EA2["exam_attempts: Attempt #2 (User 102)<br/>Cohort Context: CSCI-101"]
        EA3["exam_attempts: Attempt #3 (User 103)<br/>Cohort Context: CSCI-102"]
    end
    
    C1 -->|bounds| SC1
    C2 -->|bounds| SC2
    S1 -->|scheduled via| SC1
    S1 -->|scheduled via| SC2
    
    U1 --> CM1
    U2 --> CM2
    U3 --> CM3
    
    CM1 --> C1
    CM2 --> C1
    CM3 --> C2
    
    EA1 --> U1
    EA1 --> S1
    EA2 --> U2
    EA2 --> S1
    EA3 --> U3
    EA3 --> S1

    classDef cohort fill:#e1f5fe,stroke:#0288d1,stroke-width:2px;
    classDef subject fill:#e8f5e9,stroke:#388e3c,stroke-width:2px;
    classDef user fill:#fff3e0,stroke:#f57c00,stroke-width:2px;
    classDef attempt fill:#fce4ec,stroke:#c2185b,stroke-width:2px;
    
    C1:::cohort
    C2:::cohort
    S1:::subject
    S2:::subject
    U1:::user
    U2:::user
    U3:::user
    EA1:::attempt
    EA2:::attempt
    EA3:::attempt
```

---

### H. Curriculum Performance Analytics & Statistical Insight Level-2 DFD
This traces specific sub-process data transformations yielding Bell curves, hardest questions, and low-average support warning triggers.

```mermaid
flowchart TD
    subgraph Data Stores
        D_Attempts[(exam_attempts Table)]
        D_Answers[(attempt_answers Table)]
        D_Questions[(questions Table)]
        D_Users[(users Table)]
    end

    subgraph Process 6.0: Performance Analytics Engine (analytics.php)
        P6_1[6.1 Aggregate Cohort Grades]
        P6_2[6.2 Compute Bell-Curve Data Points]
        P6_3[6.3 Calculate Question Accuracy Ratios]
        P6_4[6.4 Scan Student Averages for Support Warnings]
    end

    subgraph Outputs & Displays
        V_Bell[Bell-Curve Grade Distribution Charts]
        V_Gaps[Top 5 Hardest Questions Report]
        V_Warns[Academic Support Warning Registry]
    end

    %% Pipeline 1: Bell Curve
    D_Attempts -->|Raw Score & Percentages| P6_1
    P6_1 -->|Score distributions| P6_2
    P6_2 -->|Standard Deviations & Bell Plot Coordinates| V_Bell

    %% Pipeline 2: Hardest Questions (Syllabus Gaps)
    D_Questions -->|Question IDs & Text| P6_3
    D_Answers -->|is_correct metrics| P6_3
    P6_3 -->|Select lowest accuracy questions ratio < 40%| V_Gaps

    %% Pipeline 3: Low Performance Student warnings
    D_Users -->|User IDs & Names| P6_4
    D_Attempts -->|Running averages| P6_4
    P6_4 -->|Identify averages < 50.00%| V_Warns
```

---

### I. Database Automated Migration & Administrative Seeding Sequence Diagram
This maps the schema script transaction structures, connection checks, rollbacks, and admin seeder security logic.

```mermaid
sequenceDiagram
    autonumber
    actor Admin as DevOps Engineer / System Script
    participant Runner as Migration Runner (run_enterprise_migrations.php)
    participant MariaDB as MariaDB Engine
    participant Seeder as Admin Seeder (seed_admin.php)

    Admin->>Runner: Execute PHP CLI script / HTTP Access
    Runner->>MariaDB: Connect (mysqli) & CHECK DATABASE exists
    MariaDB-->>Runner: Connection established
    
    Runner->>MariaDB: CREATE TABLE IF NOT EXISTS migration_history
    MariaDB-->>Runner: Table Ready
    
    Runner->>MariaDB: SELECT migration_name FROM migration_history
    MariaDB-->>Runner: Return list of applied files
    
    Note over Runner, MariaDB: Transaction-Safe DDL Execution
    Runner->>Runner: Scan directory for new SQL files (003_enterprise_lms_extensions.sql)
    
    loop For each unapplied SQL file
        Runner->>MariaDB: START TRANSACTION
        Runner->>MariaDB: Parse & execute DDL statements (ALTER TABLE, CREATE TABLE)
        alt Statement Execution Success
            Runner->>MariaDB: INSERT INTO migration_history VALUES (filename)
            Runner->>MariaDB: COMMIT
            MariaDB-->>Runner: Changes persisted
        else SQL Error / Constraint Failure
            Runner->>MariaDB: ROLLBACK
            MariaDB-->>Runner: Rollback completed cleanly
            Runner-->>Admin: Halt & output migration error stack
        end
    end
    
    Note over Runner, Seeder: Seeding Phase
    Runner->>Seeder: Invoke seed_admin.php processes
    Seeder->>Seeder: Generate secure bcrypt hash for 'admin' password
    Seeder->>MariaDB: INSERT INTO users (role, u_name, u_pass) ON DUPLICATE KEY UPDATE u_pass = ?
    MariaDB-->>Seeder: Seeding updated
    Seeder-->>Runner: Seeding complete
    
    Runner-->>Admin: Output success report (Database migrations completed safely)
```

---

## 4. Advanced Security, Runtime States & Logic Diagrams

### A. UML State Machine Diagram: Exam Attempt Lifecycle
This tracks database record state transitions during live attempts, ensuring security thresholds are respected.

```mermaid
stateDiagram-v2
    [*] --> Created : Start Assessment (click launch)
    Created --> InProgress : First question loaded
    
    state InProgress {
        [*] --> ActiveTimer
        ActiveTimer --> Suspended : Proctor click "Pause"
        Suspended --> ActiveTimer : Proctor click "Resume"
        ActiveTimer --> Warning1 : Proctor click "Warn"
        Warning1 --> Warning2 : Second proctor warning
        Warning2 --> Terminated : Proctor force-submits / exceeds limits
    }
    
    InProgress --> OfflineBuffering : Browser 'offline' event
    OfflineBuffering --> InProgress : Browser 'online' event & batch sync
    
    InProgress --> Completed : User manual submit / Time expires
    OfflineBuffering --> Completed : Time expires offline
    
    Completed --> EvaluatingSimilarity : Run Levenshtein plagiarism check
    EvaluatingSimilarity --> FlaggedForReview : Similarity score > 75%
    EvaluatingSimilarity --> Graded : Similarity score <= 75%
    
    FlaggedForReview --> Graded : Instructor manual release
    FlaggedForReview --> Rejected : Instructor invalidates
    
    Graded --> Certified : Grade >= 50% (Issue Crypto Certification Key)
    Graded --> Failed : Grade < 50%
    
    Certified --> [*]
    Failed --> [*]
    Rejected --> [*]
```

---

### B. UML Use Case Diagram: Actors & Bound Functions
This maps user access credentials and roles directly to secure execution cases in the portal boundary.

```mermaid
flowchart TD
    subgraph Users (Actors)
        Student([Student Candidate])
        Proctor([Proctor Invigilator])
        Admin([Master Administrator])
        Employer([Public Employer])
    end

    subgraph Exam Portal Suite (Use Cases)
        UC1(Login & Register Profile)
        UC2(Browse Subject Directories)
        UC3(Solve MCQ Assessment)
        UC4(Trigger Offline Answer Buffering)
        UC5(View Digital Skills passport)
        
        UC6(Monitor Active Telemetry Grid)
        UC7(Warn Candidate / Pause Timer)
        UC8(Add Questions with Syntax Preview)
        
        UC9(Create Classroom Cohorts)
        UC10(Bind Schedule Release Calendars)
        UC11(Extract Curriculum Performance Analytics)
        
        UC12(Search Public Certification Key)
    end

    Student --> UC1
    Student --> UC2
    Student --> UC3
    Student --> UC4
    Student --> UC5

    Proctor --> UC1
    Proctor --> UC6
    Proctor --> UC7
    Proctor --> UC8

    Admin --> UC1
    Admin --> UC6
    Admin --> UC7
    Admin --> UC8
    Admin --> UC9
    Admin --> UC10
    Admin --> UC11

    Employer --> UC12
```

---

### C. UML Component Diagram: Software Modular Dependencies
This blueprints codebase modular structures, execution libraries, and communication interfaces.

```mermaid
flowchart TD
    subgraph Client Presentation Layer
        UI[CSS Glassmorphic Engine] <--> DOM[JS DOM Controller]
        DOM <--> HeartbeatWorker[Asynchronous Telemetry Poller]
        DOM <--> OfflineCache[HTML5 LocalStorage Buffer]
    end

    subgraph Server Middleware Layer
        Router[PHP Route Controller] --> Auth[RBAC Guard security.php]
        Router --> TelemetryHandler[proctor_check.php]
        Router --> SyncHandler[sync_answer.php]
        Router --> ExamHandler[quiz.php / adaptive_quiz.php]
        
        Auth --> SessionManager[PHP Session Controller]
    end

    subgraph Core Logic Systems
        IRT[Adaptive IRT Engine]
        Plag[Levenshtein Similarity Checker]
        Cert[Cryptographic Signature Key Generator]
    end

    subgraph Storage Adapter Layer
        DBA[MySQLi Database Adapter]
    end

    HeartbeatWorker <-->|HTTP POST JSON| TelemetryHandler
    OfflineCache -->|AJAX Fetch Batch| SyncHandler
    DOM <-->|HTTP Page Routing| ExamHandler

    ExamHandler <--> IRT
    ExamHandler <--> Cert
    SyncHandler <--> Plag
    
    SyncHandler --> DBA
    TelemetryHandler --> DBA
    ExamHandler --> DBA
    
    DBA <--> DB[(MariaDB Database Store)]
```

---

### D. UML Activity Diagram: IRT Adaptive Question Shifting Logic
This tracks the dynamic question selection logic trees executed inside `adaptive_quiz.php`.

```mermaid
flowchart TD
    Start([Initialize Adaptive Attempt]) --> SetMedium[Set Current Level = Medium]
    SetMedium --> LoadQuestion[Fetch Unsolved Question at Current Level]
    LoadQuestion --> GetAnswer[Collect Student Option Selection]
    GetAnswer --> EvalCorrect{Is Answer Correct?}
    
    EvalCorrect -->|Yes| LogSuccess[Record Success in History array]
    LogSuccess --> LevelUp{Current Level == Expert?}
    LevelUp -->|Yes| MaintainExpert[Keep Level = Expert]
    LevelUp -->|No| IncLevel[Shift Level Up Easy -> Med -> Hard -> Adv -> Expert]
    
    EvalCorrect -->|No| LogFail[Record Failure in History array]
    LogFail --> LevelDown{Current Level == Easy?}
    LevelDown -->|Yes| MaintainEasy[Keep Level = Easy]
    LevelDown -->|No| DecLevel[Shift Level Down Expert -> Adv -> Hard -> Med -> Easy]
    
    MaintainExpert --> CheckCount
    IncLevel --> CheckCount
    MaintainEasy --> CheckCount
    DecLevel --> CheckCount

    CheckCount{Total Questions Handled == 10?}
    CheckCount -->|No| LoadQuestion
    CheckCount -->|Yes| EndAssessment[Submit Attempt answers]
    
    EndAssessment --> AverageIRT[Extract Difficulty Values of the LAST 4 questions]
    AverageIRT --> CalculateScore[Compute average steady-state rating score]
    CalculateScore --> MapBadge[Map Rating to Skill Passport Badge]
    MapBadge --> IssueSignedCert[Issue Crypto Certification Key]
    IssueSignedCert --> End([Complete and Store Graded Attempt])
```

---

### E. Threat Model / Attack Tree Diagram: Portal Vulnerability Analysis
This maps threat vectors to system-engineered security controls to enforce database and credential integrity.

```mermaid
graph TD
    Attack([Compromise Exam Portal Integrity]) --> V1[Vertical Privilege Escalation]
    Attack --> V2[Candidate Cheating Infiltration]
    Attack --> V3[Certificate Forgery & Impersonation]
    Attack --> V4[Database Denial of Service DB crash]

    V1 --> V1_Sub[Direct URL manipulation targeting admin controllers]
    V1_Sub -.-> M1[Enforce require_admin Session checks at compilation start]

    V2 --> V2_Sub1[Timer Manipulation via local browser dev tools client scripts]
    V2_Sub1 -.-> M2[Verify countdown values on server-side HTTP attempt check-in]
    V2 --> V2_Sub2[Peer text copying in short answers subjective fields]
    V2_Sub2 -.-> M3[Run automated Levenshtein and Jaro-Winkler string similarity cross-checks]
    V2 --> V2_Sub3[Session switching/tab loss to search answers online]
    V2_Sub3 -.-> M4[Implement window focus/blur event monitoring telemetry warnings]

    V3 --> V3_Sub[Self-producing custom verification registry certification files]
    V3_Sub -.-> M5[Validate certificate lookups using cryptographically hashed SHA-256 signature verification keys]

    V4 --> V4_Sub[High-concurrency traffic floods during finals week]
    V4_Sub -.-> M6[Deploy Nginx Rate Limiting and execute MariaDB connection pools]

    classDef mitig fill:#dfd,stroke:#080,stroke-width:2px;
    classDef attack fill:#fdd,stroke:#800,stroke-width:2px;
    
    M1:::mitig
    M2:::mitig
    M3:::mitig
    M4:::mitig
    M5:::mitig
    M6:::mitig
    V1_Sub:::attack
    V2_Sub1:::attack
    V2_Sub2:::attack
    V2_Sub3:::attack
    V3_Sub:::attack
    V4_Sub:::attack
```

---

### F. UML Deployment Diagram: Infrastructure Environment Mappings
This blueprints physical hardware runtimes and staging architectures.

```mermaid
graph TD
    subgraph Developer Local Station Windows XAMPP
        DevClient[Browser Chrome/Edge] -- Localhost HTTP Port 80 --> DevApache[Apache 2.4 Server]
        DevApache -- PHP 8.x Engine --> DevDB[(MySQL Database 3306)]
    end

    subgraph Integration & CI Environment GitHub Runners
        CI_Container[Ubuntu Runner Container]
        CI_Container --> Lint[PHP CodeSniffer]
        CI_Container --> Test[PHPUnit & Security Checker]
    end

    subgraph Production Cloud Environment AWS EC2 / DigitalOcean
        Browser[Student Client Browser] -- HTTPS Port 443 --> WebProxy[Nginx Load Balancer]
        WebProxy -- Reverse Proxy round-robin --> WebServer1[Apache Node A -- PHP 8.x]
        WebProxy -- Reverse Proxy round-robin --> WebServer2[Apache Node B -- PHP 8.x]
        
        WebServer1 <--> CacheServer[(Redis Cache Cluster -- Session States)]
        WebServer2 <--> CacheServer
        
        WebServer1 --> MasterDB[(MariaDB 10.x Primary Database Node)]
        WebServer2 --> MasterDB
        
        MasterDB -- Asynchronous Replication --> SlaveDB[(MariaDB 10.x Read-Only Analytics Replica)]
    end
```

---

### G. Attack Surface / VPC Architecture Diagram (Physical Network Security)
This charts network isolation boundaries, firewalls, public NAT portals, and database VPC controls.

```mermaid
flowchart TD
    subgraph AWS Cloud Virtual Private Cloud VPC
        subgraph Public Subnet Internet Facing
            ALB[Application Load Balancer]
            NAT[NAT Gateway]
        end

        subgraph Private Application Subnet
            WebServerA[Apache PHP Worker Node A]
            WebServerB[Apache PHP Worker Node B]
        end

        subgraph Private Database & Caching Subnet
            Redis[(Redis Session Cache Cluster)]
            DBPrimary[(MariaDB Primary Database Server)]
            DBReplica[(MariaDB Replica Database Server)]
        end
    end

    User[External Candidate Client] -->|HTTPS Port 443 via Internet Gateway| ALB
    ALB -->|Forward dynamic traffic Port 80| WebServerA
    ALB -->|Forward dynamic traffic Port 80| WebServerB
    
    WebServerA -->|Private connection Port 6379| Redis
    WebServerB -->|Private connection Port 6379| Redis
    
    WebServerA -->|Prepared SQL statements Port 3306| DBPrimary
    WebServerB -->|Prepared SQL statements Port 3306| DBPrimary
    
    DBPrimary -->|Encrypted database mirror Port 3306| DBReplica
    
    WebServerA -->|Outbound updates| NAT
    WebServerB -->|Outbound updates| NAT
    NAT -->|Outbound queries via Internet Gateway| ExternalAPI[Third-party package managers]
```

---

### H. User Auth & Session Token Lifecycle Diagram
This sequence maps database hash fetches, session token distribution, authentication verification loops, and automated inactivity logouts.

```mermaid
sequenceDiagram
    autonumber
    actor Client as Candidate Browser
    participant App as PHP Login Handler (Auth.php)
    participant Redis as Redis Session Cache
    participant DB as MariaDB User Table

    Client->>App: Submits username & password via POST
    App->>DB: Fetch password_hash WHERE u_name = ?
    DB-->>App: Return user records
    App->>App: Verify password (password_verify)
    
    alt Credentials Invalid
        App-->>Client: Redirect to login with error banner (?err=1)
    else Credentials Valid
        App->>App: Initialize session properties (user_id, role, username)
        App->>Redis: Save session key payload (expires in 1800s)
        App->>Client: Inject HTTPOnly PHPSESSID cookie header
        App-->>Client: Redirect to dashboard (subject.php)
    end

    loop Active Session Telemetry
        Client->>App: Telebeat Poll (PHPSESSID Cookie included)
        App->>Redis: Check session key existence & touch expiration
        Redis-->>App: Return active status (Session Authorized)
        App-->>Client: Process active telemetric response
    end

    Note over Client, Redis: Automated Session Expiry Scenario (30 Min Inactivity)
    Redis->>Redis: Session key expires (Key TTL reaches 0)
    Client->>App: Heartbeat request with expired cookie
    App->>Redis: Check session key existence
    Redis-->>App: Return null (Session Expired)
    App->>App: Destroy local session arrays
    App-->>Client: Force Redirect to index.php with expired alert
```

---

### I. Plagiarism Engine Data Pipeline Diagram
This maps Jaro-Winkler, Levenshtein distance string evaluation algorithms, and student subjective response warning registries.

```mermaid
flowchart TD
    Start[Student Submits Subjective Text response] --> Extract[Extract text string answer]
    Extract --> Normalize[Text Normalization: strip punctuation, extra spaces, lower-case]
    Normalize --> QueryRef[Query DB for pre-defined Teacher Answer Key]
    Normalize --> QueryPeers[Query DB for all other student answers submitted in Cohort]
    
    QueryRef --> JW_Ref[Execute Jaro-Winkler evaluation against key]
    QueryRef --> Lev_Ref[Execute Levenshtein distance evaluation against key]
    
    QueryPeers --> JW_Peer[Execute Jaro-Winkler evaluation against peers]
    QueryPeers --> Lev_Peer[Execute Levenshtein distance evaluation against peers]

    JW_Ref & Lev_Ref --> CalcRefScore[Compute average reference similarity percentage]
    JW_Peer & Lev_Peer --> CalcPeerScore[Compute average peer-to-peer similarity percentage]

    CalcRefScore & CalcPeerScore --> ConsolidateScore[Calculate composite Plagiarism Index percentage]
    ConsolidateScore --> CheckThreshold{Similarity Score > 75%?}
    
    CheckThreshold -->|Yes| FlagDB[Insert record into attempt_answers with Flagged status]
    FlagDB --> AlertTeacher[Display High-Similarity warnings on Teacher Grade desk]
    
    CheckThreshold -->|No| SaveDB[Store standard similarity metrics in attempt_answers]
    
    SaveDB --> End([Plagiarism Pipeline execution complete])
    AlertTeacher --> End
```

---

### J. Client-Side Offline Resiliency Flowchart
This maps browser online-offline state listener event forks, LocalStorage buffering, and queue replay operations.

```mermaid
flowchart TD
    Start[Active quiz.php Session] --> WatchNet[Listen for browser offline event listener]
    WatchNet --> NetTrigger{Network Event Triggered?}
    
    NetTrigger -->|offline| SwapUI[Display Floating Glass offline banner]
    SwapUI --> FreezeSync[Intercept standard form POST submissions]
    FreezeSync --> SaveLocal[Stringify and write option selections to LocalStorage]
    SaveLocal --> QueueStates[Mark states: Pending Server Synchronization]
    QueueStates --> LoopOffline{Wait for online event listener}
    
    LoopOffline -->|No| SaveLocal
    LoopOffline -->|Yes| ReadCache[Read serialized JSON array from LocalStorage]
    
    ReadCache --> FetchBatch[Launch background AJAX fetch sync_answer.php]
    FetchBatch --> ServerConfirm{Server confirms persistence with 200 OK?}
    
    ServerConfirm -->|Yes| FlushCache[Clear answers entries from browser LocalStorage]
    FlushCache --> DismissUI[Hide floating offline banner]
    DismissUI --> ResResume[Restore normal interactive quiz operations]
    
    ServerConfirm -->|No| RetryTimer[Trigger backing delay timer 5s]
    RetryTimer --> FetchBatch
    
    NetTrigger -->|online| ResResume
    ResResume --> End([Resiliency Sync Active])
```

---

### K. Admin Privilege Guardrails & Self-Demotion Prevention Decision Flowchart
This details the safety checks implemented inside `admin_dashboard.php` to completely neutralize self-inflicted lockouts or accidental administrator privilege loss.

```mermaid
flowchart TD
    Start[Admin Clicks 'Change Role' / POST update_role.php] --> GetSession[Get $_SESSION user_id & role]
    GetSession --> GetTarget[Identify target_user_id & target_new_role]
    
    GetTarget --> CheckSelf{Is target_user_id == session_user_id?}
    
    CheckSelf -->|No: Modifying other user| ExecuteDB[Commit UPDATE users SET role = target_new_role WHERE id = target_user_id]
    
    CheckSelf -->|Yes: Modifying self| CheckAdmin{Is target_new_role != 'admin'?}
    
    CheckAdmin -->|No: Changing from admin to admin| ExecuteDB
    
    CheckAdmin -->|Yes: Self-demotion attempt| BlockAction[Intercept execution & lock database write]
    BlockAction --> WriteAudit[Log Security Warning to system logs: Self-Demotion Blocked]
    BlockAction --> AlertUser[Return JSON response: 'Self-Demotion Prevented: Admin must retain master control']
    
    ExecuteDB --> Success[Return JSON: 'User role updated successfully']
    Success & AlertUser --> End([Logic Handled])
```

---

### L. Question Editor Live Syntax Preview Rendering Lifecycle Activity Diagram
This defines the XSS-safe live client rendering loop mapping text area captures to monospaced formatted question editor boxes inside `admin_questions.php`.

```mermaid
flowchart TD
    Start[Teacher Types Question Text in Editor] --> WatchInput[keyup Event Listener active in JS]
    WatchInput --> GetContent[Retrieve textarea raw string value]
    
    GetContent --> SanitizeInput[Run secure HTML Entities translation: escape <script> and tags]
    SanitizeInput --> CheckMarkdown{Detect Markdown/Syntax backticks?}
    
    CheckMarkdown -->|Yes| ParseCode[Format inline and block coding text using monospaced styling CSS]
    CheckMarkdown -->|No| ParseNormal[Parse standard text paragraph formats]
    
    ParseCode & ParseNormal --> UpdateCanvas[Inject clean HTML string into glassmorphic preview canvas]
    UpdateCanvas --> RenderDOM[Render visually updated syntax box inside teacher editor workspace]
    
    RenderDOM --> End([State Synchronized])
```

---

### M. Invigilator Actions & Auditing Logging Pipeline Sequence Diagram
This maps sequence loops capturing proctor commands (Warn, Pause, Resume, Force-Submit) within secure databases, establishing compliance auditing trails.

```mermaid
sequenceDiagram
    autonumber
    actor Proctor as Invigilator Browser (proctor_console.php)
    participant API as Telemetry API Controller (proctor_check.php)
    participant DB as MariaDB database
    actor Student as Student Browser (quiz.php)

    Note over Proctor, DB: Proctor Intervention Execution
    Proctor->>API: Click action Warn/Pause/Terminate (POST attempt_id, command)
    API->>API: Enforce require_admin / require_teacher role guard
    
    alt Unauthorized session
        API-->>Proctor: Return 403 Forbidden Response
    else Authorized session
        API->>DB: START TRANSACTION
        API->>DB: UPDATE exam_attempts SET proctor_status = ?, proctor_paused = ? WHERE id = ?
        API->>DB: INSERT INTO proctor_logs (proctor_id, target_attempt_id, action, ip_address) VALUES (?, ?, ?, ?)
        API->>DB: COMMIT
        DB-->>API: Data written safely
        API-->>Proctor: Return 200 OK Action Status
    end

    Note over Student, DB: Student Telemetry Heartbeat Pickup
    loop Every 3 seconds
        Student->>API: Poll telemetry payload (attempt_id)
        API->>DB: SELECT proctor_status, proctor_paused FROM exam_attempts WHERE id = ?
        DB-->>API: Return active parameters
        API-->>Student: Return JSON (proctor_status: 'warning_1', proctor_paused: 1)
        
        alt Warning Status Triggered
            Student->>Student: Inject floating red alert notice toast
        else Pause Status Triggered
            Student->>Student: Freeze student local countdown timer
            Student->>Student: Inject blurred full-screen overlay locking inputs
        end
    end
```

---

### N. Connection Degradation & Server Exception Recovery State Diagram
This models real-time system performance adaptation strategies, detailing state changes under heavy traffic spikes or server failure occurrences.

```mermaid
stateDiagram-v2
    [*] --> HealthyState : Platform initialization
    
    state HealthyState {
        [*] --> IdleListening
        IdleListening --> QueryProcessing : Incoming HTTP Request
        QueryProcessing --> IdleListening : Transaction complete 200 OK
    }
    
    HealthyState --> DegradedState : MariaDB Connection Pool Exhausted (3306 timeout)
    HealthyState --> DegradedState : Server-side unhandled exception (500 Error)
    
    state DegradedState {
        [*] --> InterceptRequest
        InterceptRequest --> RenderFallback : Route to custom local cache recovery page
        RenderFallback --> QueryRetry : Trigger background auto-retry connection check
        QueryRetry --> InterceptRequest : Check fails
    }
    
    DegradedState --> HealthyState : Connection restored / Resource pool cleared
    
    HealthyState --> RateLimitedState : HTTP requests count > 100/min per IP
    
    state RateLimitedState {
        [*] --> BlockIP
        BlockIP --> RenderRateAlert : Return 429 Too Many Requests
        RenderRateAlert --> CoolDownTimer : Wait 60s
    }
    
    RateLimitedState --> HealthyState : CoolDownTimer expires
```
