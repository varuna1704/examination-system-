# Master Documentation Strategy & Specification Catalog
### Secure Enterprise Exam Portal Architecture & Implementation Manual

---

## 1. Technical Architecture & System Documentation

### A. Document Scope & Audience
*   **System Architecture Specification (SAS):**
    *   *Owner:* Principal Solutions Architect.
    *   *Maintainer:* Core Engineering Team.
    *   *Primary Audience:* Systems Architects, Backend Engineers, and Security Auditors.
*   **Database Schema & Data Dictionary (DSDD):**
    *   *Owner:* Lead Database Administrator (DBA).
    *   *Maintainer:* DBA & Backend Engineers.
    *   *Primary Audience:* Database Developers, Data Analysts, and System Engineers.
*   **API Documentation (APID):**
    *   *Owner:* Lead Backend Developer.
    *   *Maintainer:* Backend & Frontend Engineers.
    *   *Primary Audience:* Frontend Developers, Integration Engineers, and Third-Party QA Automation Engineers.

---

### B. Production-Ready Outlines

#### 1. System Architecture Specification (SAS)
*   **1. Executive Summary & Core Platform Design Principles**
    *   1.1. High-Availability & Intra-Network Resiliency.
    *   1.2. The Principle of Least Privilege in Role-Based Access.
    *   1.3. Real-Time Telemetry & Asynchronous Invigilation Metrics.
*   **2. Multi-Tiered System Layout**
    *   2.1. Presentation Layer (Vanilla CSS3, HSL Design Token System, HTML5, Vanilla JavaScript ES6+).
    *   2.2. Application Controller Layer (Stateful PHP 8.x Engine, Session Lifecycle Security, MVC Design).
    *   2.3. Data Persistence Layer (MariaDB 10.x/MySQL Relational Storage, InnoDB Engine).
*   **3. High-Concurrency Event Window Handling (University Finals Week Scale)**
    *   3.1. High-Volume Request Buffering & Reverse Proxy Routing (Nginx/Apache configurations).
    *   3.2. Micro-Caching and Static Asset Optimization.
    *   3.3. Database Connection Pooling & Memory Allocations.
*   **4. Real-Time Heartbeat Telemetry & State Polling Architecture**
    *   4.1. The 3-Second AJAX Heartbeat Loop Lifecycle.
    *   4.2. Session Hijacking Prevention & Active Token Audits.
*   **5. Dynamic Item Response Theory (IRT) Pedagogical Framework**
    *   5.1. Question pool dynamic tier shifting (Easy, Medium, Hard, Advanced, Expert).
    *   5.2. Final Competency Evaluation Algorithm (Last 4 Steady-State Questions Average).

#### 2. Database Schema & Data Dictionary (DSDD)
*   **1. Database Physical Engine Configuration & Storage Engine Design**
    *   1.1. Database Engine: InnoDB (Enforcing ACID transactions).
    *   1.2. Character Set & Collation: `utf8mb4_unicode_ci` for complete character encoding support.
*   **2. Normalized Entity Relationship Definitions (Schema Tables)**
    *   2.1. Core Directory Tables: `users`, `subjects`, `questions`, `cohorts`.
    *   2.2. Mapping & Enrollment Tables: `cohort_members`, `subject_cohorts`.
    *   2.3. Graded Transaction Tables: `exam_attempts`, `attempt_answers`.
    *   2.4. Gamification Ledger Tables: `badges`, `user_badges`.
*   **3. Granular Columns & Data Dictionary Metrics**
    *   3.1. Data types, Default constraints, Key declarations (PK, FK, UK).
    *   3.2. Indices for search speed: `uq_cohort_user`, `uq_subject_cohort`, `uq_user_badge`.
*   **4. Foreign Key Constraints & Cascading Delete Configurations**
    *   4.1. Standardizing `ON DELETE CASCADE` across map relations to automatically prune orphaned student response metrics.
*   **5. Concurrency & Transaction Isolation Mechanics**
    *   5.1. Transaction isolation settings (`READ COMMITTED`) to prevent dirty reads during parallel exam submissions.
    *   5.2. Row-level lock strategies to eliminate table deadlocks.

> [!IMPORTANT]
> **Data Integrity Mandate DSDD-01 (Cascading Delete Controls):**
> All mapping, attempts, and session-answer tables (`cohort_members`, `subject_cohorts`, `user_badges`, `exam_attempts`, `attempt_answers`) MUST incorporate explicit foreign key constraints referencing primary tables (`users`, `subjects`, `questions`, `cohorts`, `badges`) configured with `ON DELETE CASCADE`. Manual row orphan purging is strictly forbidden.

#### 3. API Documentation (APID)
*   **1. API Protocol Protocols & Security Baseline**
    *   1.1. Base URL Routing and JSON payload standards.
    *   1.2. Session-state authentication validation using encrypted PHP Session Cookies.
    *   1.3. CSRF Protection Tokens inside HTTP Header structures.
*   **2. Proctor Telemetry Heartbeat API (`proctor_check.php`)**
    *   2.1. Request payload structure (Inputs: `attempt_id`, `status`).
    *   2.2. Response JSON schema (Outputs: `proctor_status`, `proctor_paused`, `time_remaining_sec`).
*   **3. Adaptive Question Assessment API (`adaptive_quiz.php`)**
    *   3.1. Dynamic level evaluation parameters (Inputs: `attempt_id`, `question_id`, `selected_answer`).
    *   3.2. Level-shift decision array payload returns (Outputs: `next_question_id`, `new_level`, `badges_awarded`).
*   **4. Offline State Synchronization API (`sync_answer.php`)**
    *   4.1. LocalStorage JSON payload format structures (Inputs: `attempt_id`, `answers[]`).
    *   4.2. Replay protection and duplicate submission check configurations.
*   **5. API Response Error Codes & Retries Engine**
    *   5.1. Error code ranges: `200 OK`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `429 Too Many Requests`.

---

### C. Functional Visual Blueprints for Diagrams

#### 1. System Architecture Diagram
*   **Elements & Nodes:**
    *   *Client Tier:* Client Browser (renders modern CSS/HTML, executes Vanilla JS client-side processes, and manages LocalStorage storage buffers).
    *   *Routing / Proxy Tier:* Nginx Reverse Proxy (intercepts incoming traffic, enforces Rate Limiting, performs SSL/TLS Termination, and splits static vs. dynamic traffic streams).
    *   *Application Server Tier:* Apache HTTP Server running a pool of PHP 8.x worker engines.
    *   *Caching Tier:* Memcached/Redis cluster storing session states (`$_SESSION`) to prevent high-frequency DB lookup fatigue.
    *   *Persistent Storage Tier:* MariaDB 10.x Database Cluster configured in a Primary-Replica high-availability topology.
*   **Vectors & Intersections:**
    *   Client Browsers dispatch HTTPS requests through the Nginx Gateway.
    *   Nginx routes dynamic execution calls to Apache/PHP, while serving cached CSS/JS files directly back to the client.
    *   Apache/PHP workers check active session hashes via high-speed memory queries to the Caching Tier.
    *   Apache/PHP executes transactional queries (using type-checked Prepared Statements) into the MariaDB Database Cluster.
    *   MariaDB Primary automatically mirrors transactional updates asynchronously to the Replica Database Node.
*   **Critical Visual Highlighting:**
    *   Draw an explicit red border boundary encapsulating the **SSL/TLS Encryption Boundary** at the Nginx Reverse Proxy.
    *   Render a thick dashed blue box showing the **Enterprise DMZ Firewall** isolating the Database and Caching layers from direct public access.

#### 2. Entity-Relationship Diagram (ERD)
*   **Elements & Nodes:**
    *   `users`: ID, First/Middle/Last Name, Username, Hashed Password, Email, Role (`student`, `teacher`, `admin`).
    *   `subjects`: ID, Unique Name.
    *   `cohorts`: ID, Unique Class Name.
    *   `questions`: ID, Subject ID, Complexity Level, Question Format, Question Text, Choices (A–D), Correct Answer, Text Explanation.
    *   `cohort_members`: ID, Cohort ID, User ID (Bridging table).
    *   `subject_cohorts`: ID, Subject ID, Cohort ID, Open DateTime, Close DateTime.
    *   `badges`: ID, Name, Description, Emoji Symbol, Unlock Condition Type, Unlock Condition Value.
    *   `user_badges`: ID, User ID, Badge ID.
    *   `exam_attempts`: ID, Verification Key, User ID, Subject ID, Attempt Score, Percentage, Started At, Submitted At, Proctor Status, Proctor Paused State, Time Remaining.
    *   `attempt_answers`: ID, Attempt ID, Question ID, Chosen Option, Correct Verification Status, Plagiarism Similarity Score.
*   **Vectors & Intersections:**
    *   `users` to `cohort_members` has a **1-to-Many** connection; `cohorts` to `cohort_members` has a **1-to-Many** connection (together implementing a Many-to-Many student enrollment catalog).
    *   `subjects` to `subject_cohorts` and `cohorts` to `subject_cohorts` form a **1-to-Many** connection (implementing scheduled release windows).
    *   `users` to `exam_attempts` has a **1-to-Many** database vector.
    *   `exam_attempts` to `attempt_answers` has a **1-to-Many** vector configured with cascade actions.
*   **Critical Visual Highlighting:**
    *   Apply a distinct **gold color fill** to key relational indexes and unique composite keys (`uq_cohort_user`, `uq_subject_cohort`, `uq_user_badge`).
    *   Represent relationships containing `ON DELETE CASCADE` constraints with **solid green routing lines**, while non-cascading relationships use dashed gray lines.

---
---

## 2. Security, Compliance, & Access Control Documentation

### A. Document Scope & Audience
*   **Role-Based Access Control (RBAC) Framework:**
    *   *Owner:* Director of Information Security.
    *   *Maintainer:* Cybersecurity Specialist & System Administrator.
    *   *Primary Audience:* System Auditors, DevOps Engineers, and Security Administrators.
*   **Data Retention & Privacy Policy:**
    *   *Owner:* Chief Compliance Officer.
    *   *Maintainer:* Legal Counsel & Data Privacy Officer.
    *   *Primary Audience:* Legal Compliance Inspectors, System Evaluators, and General Platform Users.
*   **Proctoring & Anti-Cheat Protocols:**
    *   *Owner:* Head of Academic Integrity.
    *   *Maintainer:* Lead Frontend Security Engineer.
    *   *Primary Audience:* Invigilators, Teachers, and Frontend Developers.

---

### B. Production-Ready Outlines

#### 1. Role-Based Access Control (RBAC) Framework
*   **1. The Principles of Least Privilege & Architectural Boundaries**
    *   1.1. Absolute Separation of Student, Teacher, and Administrator Workspaces.
    *   1.2. Session Hijacking and Privilege Escalation Protections.
*   **2. Authorization Matrices & Access Rules**
    *   2.1. System-wide role configurations (`student`, `teacher`, `admin`).
    *   2.2. Page-level gateway guards (`require_login()`, `require_admin()` execution checks).
*   **3. Cryptographic Password Management Policy**
    *   3.1. Hashing Standards: `PASSWORD_DEFAULT` (Bcrypt) at the application layer.
    *   3.2. Verification mechanisms during credential login validations.
*   **4. Cohort Class Management Controls**
    *   4.1. Cohort-level exam visibility restrictions (preventing access outside enrollment spaces).
*   **5. Security Auditing & Incident Mitigation**
    *   5.1. Session timeout schedules (auto-logout rules).
    *   5.2. Unauthorized URL modification tracking and automated logging.

#### 2. Data Retention & Privacy Policy
*   **1. Legal Compliance Framework & Regulatory Baselines**
    *   1.1. Compliance standards matching GDPR, CCPA, and Family Educational Rights and Privacy Act (FERPA) regulations.
*   **2. Data Classification Matrix & Storage Standards**
    *   2.1. Personally Identifiable Information (PII) category listings (`users` details).
    *   2.2. Graded Metrics and Candidate Performance logs (`exam_attempts`).
    *   2.3. System Metadata logs (IP addresses, Browser telemetry logs).
*   **3. Retention Windows & Automated Purging Lifecycles**
    *   3.1. Maximum storage intervals for student credentials and active attempts.
    *   3.2. Automated database pruning scripts for expired academic records.
*   **4. Cryptographic Registry & Data Protection Mandates**
    *   4.1. Public verification registry access limits (`verify.php` exposes only the certificate meta, completely concealing candidate email/password identifiers).
*   **5. Candidate Data Portability & "Right to Be Forgotten" Protocols**
    *   5.1. Secure database record scrubbing protocols.

> [!WARNING]
> **Data Integrity Mandate DRCAP-02 (Candidate Identity Protection):**
> Under no circumstances shall public verification APIs (`verify.php`) expose PII such as user email addresses, mobile numbers, age records, or physical addresses. Public registry searches MUST validate using the unique hash key and return only the student's legal name, exam score, subject category, and verification status.

#### 3. Proctoring & Anti-Cheat Protocols
*   **1. Threat Model Analysis & Integrity Threats**
    *   1.1. Threat Category: Browser Tab Shifting, Multi-window setups, Copied responses.
*   **2. Real-Time Telemetry & Asynchronous Invigilation Infrastructure**
    *   2.1. Asynchronous Client-Server Heartbeat (3-second connection loop via AJAX).
    *   2.2. Interactive warning state transitions (`monitoring` &rarr; `warning_1` &rarr; `warning_2` &rarr; `suspended`).
*   **3. Proctor Command Console Mechanics**
    *   3.1. Remote screen override actions (Warn, Pause Exam, Force Submit).
    *   3.2. Pausing functionality: immediate suspension of local countdown timers and application of a glassmorphic overlay.
*   **4. Subjective Answer Plagiarism Engine**
    *   4.1. Similarity evaluation metrics using Levenshtein distance computations.
    *   4.2. Comparative checks: Candidate-to-Candidate peer submissions and Candidate-to-AnswerKey metrics.
*   **5. Browser Lock and Focus Tracking Integration**
    *   5.1. Browser focus tracking (`window.onblur`/`window.onfocus`) to flag student departures.

---

### C. Functional Visual Blueprints for Diagrams

#### 1. Role-Permission Matrix Diagram
*   **Elements & Nodes:**
    *   *Subject Entities (Roles):* `Student`, `Teacher`, `Master Admin`.
    *   *Action Operations (CRUD):* Create, Read, Update, Delete.
    *   *Object Resources:* Question Pool database, User Directory database, Attempt/Grade ledger, Cohort/Classroom calendars.
*   **Vectors & Intersections:**
    *   Draw vectors linking `Student` directly to `Read` operations on enrolled Exam/Subject lists, and `Read/Write` operations on their own `exam_attempts` rows.
    *   Map `Teacher` to `Create, Read, Update, Delete` vectors targeting the Question Pool database, and `Read` vectors targeting cohort performance reports.
    *   Draw unrestricted execution routes from the `Master Admin` role to all actions (CRUD) across every database resource including user profiles and system settings.
*   **Critical Visual Highlighting:**
    *   Apply a bright **red warning color** to any line terminating at "Delete" operations for users or attempt ledgers.
    *   Use a **bold lock symbol** at intersections where `Student` attempt access meets `Write` restrictions (ensuring students cannot self-grade or access peer results).

#### 2. Data Flow Diagram (DFD)
*   **Elements & Nodes:**
    *   *External Entities:* Student Candidate, Proctor Invigilator, Public Employer.
    *   *Processes:* Login Verification (P1), Quiz Answer Processor (P2), Proctor Telemetry Sync (P3), Plagiarism Comparison Engine (P4), Public Certificate Search (P5).
    *   *Data Stores:* User Directory (D1), Attempt Database Ledger (D2), Plagiarism Reference Table (D3).
*   **Vectors & Intersections:**
    *   Student sends Username/Password credentials to P1; P1 queries D1 and returns an active session token to the Student.
    *   Student posts quiz answers to P2; P2 stores choices into D2 and simultaneously dispatches answers to P4 for plagiarism analysis.
    *   Proctor sends screen status signals (e.g. Pause, Warn) to P3; P3 updates D2 and synchronizes metrics back to the Student's screen.
    *   Public Employer posts certificate verification keys to P5; P5 queries D2 and displays passing credentials.
*   **Critical Visual Highlighting:**
    *   Surround P4 (Plagiarism Engine) and D3 (Plagiarism Store) with a **green boundary** indicating automated cryptographic evaluation spaces.
    *   Render the data pathways between the Student's browser and P2/P3 with a **thick padlock icon** to represent secure HTTPS traffic encapsulation.

---
---

## 3. Functional & Operational Documentation

### A. Document Scope & Audience
*   **Functional Requirements Document (FRD):**
    *   *Owner:* Lead Product Owner.
    *   *Maintainer:* Business Analyst & Technical Product Manager.
    *   *Primary Audience:* Frontend Developers, Backend Engineers, QA Testers, and University Admins.
*   **User Lifecycle & Enrollment Guide:**
    *   *Owner:* Head of Academic Operations.
    *   *Maintainer:* Student Registrar & System Operator.
    *   *Primary Audience:* Registrars, Cohort Managers, Instructors, and System Support Teams.

---

### B. Production-Ready Outlines

#### 1. Functional Requirements Document (FRD)
*   **1. Project Scope & Functional System Map**
    *   1.1. Student Examination Workspace requirements.
    *   1.2. Teacher Command Dashboard requirements.
    *   1.3. Master Admin Cohort Management tools.
*   **2. Live Proctoring Control Panel Requirements**
    *   2.1. Concurrency updates: 3-second maximum heartbeat telemetry sync lag.
    *   2.2. Multi-status invigilation controls (Warn student, Pause exam, Terminate/Force Submit).
*   **3. HTML5 Offline Resiliency Engine Requirements**
    *   3.1. Detection: Instant network connection loss detection.
    *   3.2. Caching: Seamless LocalStorage answer buffering.
    *   3.3. Syncing: Asynchronous batch sync on network connection recovery.
*   **4. Dynamic AI-Driven Adaptive (Placement) Mode Requirements**
    *   4.1. Response-driven difficulty adjustments (Easy, Medium, Hard, Advanced, Expert).
    *   4.2. Interactive skill mapping visualizer.
*   **5. Verification & Security Engine Requirements**
    *   5.1. Cryptographic hash key generation upon scoring a passing grade.
    *   5.2. Public-facing gold-foil certificate lookup directory.

#### 2. User Lifecycle & Enrollment Guide
*   **1. User Access & Credential Registration**
    *   1.1. Registration forms validation rules (Age checks, valid emails, password requirements).
    *   1.2. Role allocation: Automated initialization as `student`.
*   **2. Cohort Enrollment & Classroom Assignment**
    *   2.1. Admin-driven cohort generation (`admin_cohorts.php`).
    *   2.2. Student enrollment mapping constraints (preventing duplicate classroom seats).
*   **3. Cohort Scheduled Release Windows Calendar**
    *   3.1. Defining exact opening and closing boundaries (`opens_at`, `closes_at`).
    *   3.2. Automated visibility shifts inside the subject navigation dashboard (`subject.php`).
*   **4. Achievement Progression & Digital Skills Passport**
    *   4.1. Criteria parameters for digital badge rewards.
    *   4.2. Access boundaries for sharing the public Skills Passport portfolio URL.
*   **5. System Offboarding & Lifecycle Expiry**
    *   5.1. Terminating user sessions upon course completion.
    *   5.2. User account deactivation and archival steps.

> [!IMPORTANT]
> **Data Integrity Mandate ULEG-03 (Enrollment Uniqueness):**
> The database structure MUST enforce index uniqueness on user-to-cohort mapping via composite index keys (`uq_cohort_user`). A candidate cannot be mapped to the same classroom cohort multiple times, preventing duplicate session initialization parameters.

---

### C. Functional Visual Blueprints for Diagrams

#### 1. User Journey / Flow Diagram
*   **Elements & Nodes:**
    *   *Entry Step:* System login screen.
    *   *Decision Step 1:* Credentials correct check?
    *   *Landing Step:* User Dashboard (Subject directory).
    *   *Decision Step 2:* Enrolled Cohort Schedule open check?
    *   *Assessment Steps:* Choice of Mock Exam (Practice) OR Graded Official Exam OR Adaptive Placement Exam.
    *   *Completion Steps:* Result calculation screen, Certificate display, Badge awards ledger.
*   **Vectors & Intersections:**
    *   Connect the login screen to the credentials decision step; invalid routes return the user to the entry screen with error notifications.
    *   Direct the student dashboard node to check the cohort schedule database records; if the current date is outside the release window, access is blocked and the subject card is hidden.
    *   Route the student through either the linear exam flow or adaptive difficulty adjustment states based on the exam type selected.
    *   Map the final scores to the certificate registry and digital badges repository.
*   **Critical Visual Highlighting:**
    *   Render the decision step for **Adaptive Level Shifting** using a gold diamond shape to highlight real-time difficulty calculations.
    *   Highlight the **Official Exam Launch** path using a bright blue line to indicate a graded assessment session.

#### 2. UML Sequence Diagram
*   **Elements & Nodes:**
    *   *Lifelines:* Student Browser, `quiz.php` Page Controller, `proctor_check.php` Heartbeat Script, `proctor_console.php` Proctor GUI, Database Store.
*   **Vectors & Intersections:**
    *   Student clicks answer radio; Student Browser issues background AJAX fetch request to `quiz.php`.
    *   `quiz.php` executes a prepared database statement to store the selected choice, returning an HTTP `200 OK` response.
    *   Student Browser starts the 3-second heartbeat loop, posting attempt state to `proctor_check.php`.
    *   In parallel, Proctor clicks "Pause Exam" on the `proctor_console.php` GUI, which immediately updates the proctor paused state in the Database Store.
    *   During the next telemetry check request, `proctor_check.php` queries the database, detects the paused flag, and dispatches a JSON response containing `proctor_paused = 1`.
    *   Student Browser intercepts the response, blocks the UI with a blurred overlay, and freezes the countdown timer.
*   **Critical Visual Highlighting:**
    *   Surround the 3-second heartbeat loop using a **solid blue UML Loop box** labeled `alt Asynchronous heartbeats`.
    *   Use a **red execution block** to show the proctor intervention stage (updating DB status and freezing student screens).

---
---

## 4. Business Continuity & DevOps Documentation

### A. Document Scope & Audience
*   **Deployment & CI/CD Pipeline Guide:**
    *   *Owner:* Lead DevOps Engineer.
    *   *Maintainer:* DevOps Team.
    *   *Primary Audience:* Deploy Engineers, Developers, and Release Managers.
*   **Disaster Recovery & High-Availability Plan:**
    *   *Owner:* Principal Infrastructure Architect.
    *   *Maintainer:* DevOps & Infrastructure Operations Team.
    *   *Primary Audience:* Systems Administrators, Incident Response Teams, and Infrastructure Auditors.

---

### B. Production-Ready Outlines

#### 1. Deployment & CI/CD Pipeline Guide
*   **1. System Environments Setup & Architecture**
    *   1.1. Local Environment: XAMPP / PHP dev server configurations.
    *   1.2. Staging Environment: Testing space for QA automation and manual validation.
    *   1.3. Production Environment: Secure cloud instance configurations (e.g. AWS EC2, DigitalOcean).
*   **2. Automated Pipeline Orchestration (CI/CD)**
    *   2.1. Version control configurations (Git branching rules, release tags).
    *   2.2. Build pipeline stages: Linting checks, security audits, and code analysis.
*   **3. Database Migrations Management**
    *   3.1. Roll-forward migration scripts (`seeds/seed_admin.php`, database tables expansion).
    *   3.2. Safe rollback procedures during migration incidents.
*   **4. Automated Visual Testing & Validation Stages**
    *   4.1. End-to-end testing of student login, exam submission, and proctor control flows.
*   **5. Deployment Strategies & Pipeline Controls**
    *   5.1. Zero-Downtime Deployment methodologies (Blue-Green or rolling updates during high-traffic exam weeks).

#### 2. Disaster Recovery & High-Availability Plan
*   **1. Service Level Agreements (SLAs) & Recovery Targets**
    *   1.1. Recovery Point Objective (RPO): Maximum allowable data loss (Goal: < 3 seconds of local test states).
    *   1.2. Recovery Time Objective (RTO): Target time to restore normal service operations (Goal: < 5 minutes).
*   **2. Automated Failover Architecture & Multi-Node Setup**
    *   2.1. Load Balancer setups distributing concurrent student request pools.
    *   2.2. Active-Passive Database clustering setups with automated health checking.
*   **3. Automated Backups & System Recovery**
    *   3.1. Automated hourly incremental backups of database states.
    *   3.2. Secure off-site encrypted snapshot replication.
*   **4. Incident Response & Manual Failover Runbook**
    *   4.1. Step-by-step instructions for manual database replica escalation.
    *   4.2. Client-side crash notification handling (providing instructions for students to resume exams safely).
*   **5. Drills & Business Continuity Verification Exercises**
    *   5.1. Schedule for periodic failover simulation drills.

> [!CAUTION]
> **Data Integrity Mandate DRHA-04 (State Protection Recovery):**
> High-availability configurations MUST ensure that if an application server node crashes mid-exam, the load balancer will route the student's browser to an active node without terminating their session. The backup node MUST restore their progress using cached database states and local browser cache replays.

---

### C. Functional Visual Blueprints for Diagrams

#### 1. CI/CD Pipeline Diagram
*   **Elements & Nodes:**
    *   *Source Stage:* Developer Code Commit (GitHub Repository).
    *   *Trigger:* Webhook trigger.
    *   *Build/Test Container:* CI Server (GitHub Actions/Jenkins).
    *   *Jobs:* PHP Linting check, PHPUnit security checks, MariaDB structure validation.
    *   *Release Registry:* Production-Ready Artifact Archive.
    *   *Deployment Target:* AWS EC2 Production Autoscaling group.
*   **Vectors & Intersections:**
    *   Developer pushes updates to the `main` branch, which triggers the CI webhook.
    *   CI Server checks out code, installs dependencies, and runs parallel validation tests.
    *   If all checks pass, the code is packaged and stored in the Release Registry.
    *   The deployment job executes a zero-downtime rolling update, deploying packages to production nodes.
*   **Critical Visual Highlighting:**
    *   Render a **solid red blocker gate icon** showing an automated stop if any PHPUnit security checks fail.
    *   Color-code validation checks with **bright green circles** when successful.

#### 2. Failover Lifecycle Diagram
*   **Elements & Nodes:**
    *   *Normal State:* Load Balancer distributing traffic to Server Node A (Active) and Server Node B (Active); Database Primary replicating to Database Replica.
    *   *Failure Incident:* Server Node A crashes.
    *   *Monitoring System:* Health Check Daemon.
    *   *Mitigation Action:* DNS/Load Balancer Routing changes.
    *   *Recovery State:* All traffic routed to Server Node B; Database Replica elevated to primary database state.
*   **Vectors & Intersections:**
    *   Health Check Daemon constantly polls Server Node A's `/health` endpoint.
    *   Node A crashes; Health Check Daemon registers connection timeouts and updates the Load Balancer within 5 seconds.
    *   Load Balancer stops routing requests to Node A and shifts traffic exclusively to Node B.
    *   Student Browsers automatically retry failed requests, which are routed to Node B, resuming the exam using DB-cached states.
*   **Critical Visual Highlighting:**
    *   Highlight the **Crash Event** using a large yellow lightning bolt.
    *   Draw the failover redirection vector using a **thick dashed orange line** to illustrate the recovery path.
