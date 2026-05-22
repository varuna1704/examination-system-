# 🎓 ExamPortal Pro - Modern Online Examination System

![ExamPortal Banner](screenshots/banner.png)

[![PHP Version](https://img.shields.io/badge/PHP-8.x-777bb4?style=flat-square&logo=php)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=flat-square&logo=mysql)](https://www.mysql.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](https://opensource.org/licenses/MIT)

**ExamPortal Pro** is a premium, feature-rich Online Examination System designed to provide a seamless testing experience for students and a robust platform for educators. Built with a modern aesthetic and high-performance backend, it supports multi-level difficulty settings, real-time timer tracking, and detailed performance analytics.

---

## 🚀 Key Features

### 👤 User Experience
- **Secure Authentication**: Robust registration and login system using PHP sessions and prepared statements.
- **Dynamic Dashboard**: Personalized subject selection with a clean, card-based interface.
- **Multi-Level Difficulty**: Choose your challenge from **Easy, Medium, Hard, Advanced, or Expert**.

### 📝 Examination Engine
- **Randomized Questioning**: Questions are shuffled for every attempt to ensure integrity.
- **Timed Assessment**: Integrated real-time countdown timer tailored to difficulty levels.
- **Smart Auto-Submission**: Automatically submits and saves progress when time runs out.
- **Real-time Scoring**: Instant calculation of percentages and performance metrics.

### 📊 Results & Review
- **Detailed Analytics**: Breakdown of correct vs. wrong answers.
- **In-depth Review**: Post-exam review system showing correct answers and **conceptual explanations**.
- **Modern UI/UX**: Premium "Glassmorphism" design, responsive across all devices (Desktop, Tablet, Mobile).

---

## 🛠️ Tech Stack

- **Frontend**: 
  - HTML5 & CSS3 (Modern Flexbox/Grid layouts)
  - JavaScript (ES6+ for timer logic and UI interactions)
  - Custom animations and transitions for a premium feel.
- **Backend**: 
  - PHP (Server-side logic and session management)
- **Database**: 
  - MySQL / PostgreSQL (Structured data storage for users, questions, and responses)
- **Design Pattern**: 
  - MVC-inspired structure for clean code separation.

---

## 📸 Screenshots

> [!TIP]
> *Experience the premium UI/UX of ExamPortal Pro below.*

| **Login Page** | **Subject Selection** |
| :---: | :---: |
| ![Login UI](screenshots/login.png) | ![Dashboard UI](screenshots/dashboard.png) |

| **Active Quiz** | **Result Analysis** |
| :---: | :---: |
| ![Quiz UI](screenshots/quiz.png) | ![Result UI](screenshots/result.png) |

---

## ⚙️ Installation Guide

Follow these steps to set up the project locally using **XAMPP**:

1. **Clone the Repository**
   ```bash
   git clone https://github.com/varuna1704/examination-system-.git
   cd examination-system-
   ```

2. **Setup Local Environment**
   - Copy the `Exam` folder into your XAMPP `htdocs` directory.
   - Start **Apache** and **MySQL** services in the XAMPP Control Panel.

3. **Database Configuration**
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
   - Create a new database named `examination_system`.
   - Import the `Exam/Exam_DB.sql` or `Exam/insert_questions.sql` file.
   - *(Optional)* If using the automated script, run `Exam/setup_db.bat`.

4. **Update Configuration**
   - Edit `Exam/config.php` to match your database credentials:
     ```php
     $host = "localhost";
     $user = "root";
     $pass = "";
     $dbname = "examination_system";
     ```

5. **Run the App**
   - Navigate to `http://localhost/Exam/index.php` in your browser.

---

## 🗄️ Database Structure

The system relies on a clean, fully-normalized canonical database schema:

- **`users`**: Stores user credentials, roles, and profile information.
- **`subjects`**: Subject catalog (Java Programming Language, Python, PHP, C, Data Structures).
- **`questions`**: Core questions table containing:
  - `subject_id`: Foreign key referencing the subjects table.
  - `level`: Difficulty (Easy, Medium, Hard, Advanced, Expert)
  - `type`: Question format (e.g. MCQ)
  - `question`: The text of the question
  - `options (A-D)`: Multiple choice answers
  - `correct_answer`: The logic key ('A', 'B', 'C', or 'D')
  - `explanation`: Detailed explanation shown to students post-exam.
- **`exam_attempts`**: Logs user exam attempts, starting time, submission time, score, and overall percentage.
- **`attempt_answers`**: Stores the exact option chosen by the user for each question in an attempt, checking if it was correct.

---

## 📈 Future Roadmap

- [x] **Dynamic Question Generation**: Intelligently generate dynamic questions matching LeetCode and HackerRank styles.
- [x] **Hybrid Question Management System**: Prioritized question fetching combining local DB caching, internal programmatic generator templates, and external API requests (Open Trivia DB).
- [ ] **Anti-Cheating Module**: Browser lock and tab-switching detection.
- [ ] **Admin Command Center**: Complete dashboard for educators to manage questions and track student progress.
- [ ] **Leaderboards**: Competitive ranking system for global or subject-specific top performers.

---

## 👨‍💻 Author

**Varuna Nikam**
- GitHub: [@varuna1704](https://github.com/varuna1704)
- Portfolio: [(https://varuna-nikam.vercel.app/)]

---

## 📄 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

---

*Made with ❤️ for modern education.*
