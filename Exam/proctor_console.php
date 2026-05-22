<?php
require_once 'lib/security.php';
require_login();

// Restrict to admins and teachers
if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header('Location: subject.php');
    exit;
}

require 'config.php';

// Handle active AJAX commands
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];
    $attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;

    if ($attempt_id > 0) {
        if ($action === 'warn') {
            // Fetch current status
            $stmt = $conn->prepare("SELECT proctor_status FROM exam_attempts WHERE id = ?");
            $stmt->bind_param("i", $attempt_id);
            $stmt->execute();
            $status = $stmt->get_result()->fetch_assoc()['proctor_status'] ?? 'monitoring';

            $new_status = 'monitoring';
            if ($status === 'monitoring') $new_status = 'warning_1';
            elseif ($status === 'warning_1') $new_status = 'warning_2';
            elseif ($status === 'warning_2') $new_status = 'suspended';
            
            $up = $conn->prepare("UPDATE exam_attempts SET proctor_status = ? WHERE id = ?");
            $up->bind_param("si", $new_status, $attempt_id);
            $up->execute();
            echo json_encode(['status' => 'success', 'new_status' => $new_status]);
            exit;
        } elseif ($action === 'pause') {
            $up = $conn->prepare("UPDATE exam_attempts SET proctor_paused = 1 WHERE id = ?");
            $up->bind_param("i", $attempt_id);
            $up->execute();
            echo json_encode(['status' => 'success']);
            exit;
        } elseif ($action === 'resume') {
            $up = $conn->prepare("UPDATE exam_attempts SET proctor_paused = 0 WHERE id = ?");
            $up->bind_param("i", $attempt_id);
            $up->execute();
            echo json_encode(['status' => 'success']);
            exit;
        } elseif ($action === 'terminate') {
            // Force submit/complete
            $up = $conn->prepare("UPDATE exam_attempts SET proctor_status = 'completed', proctor_paused = 0 WHERE id = ?");
            $up->bind_param("i", $attempt_id);
            $up->execute();
            echo json_encode(['status' => 'success']);
            exit;
        }
    }
    echo json_encode(['status' => 'error']);
    exit;
}

// Fetch active attempts for real-time dashboard render
if (isset($_GET['poll'])) {
    header('Content-Type: application/json');
    $active = [];
    $res = $conn->query("
        SELECT ea.id, ea.level, ea.exam_mode, ea.proctor_status, ea.proctor_paused, ea.time_remaining_sec,
               u.f_name, u.l_name, u.u_name, s.name as subject_name,
               (SELECT COUNT(*) FROM attempt_answers WHERE attempt_id = ea.id AND selected_answer IS NOT NULL) as answered,
               ea.total_questions
        FROM exam_attempts ea
        JOIN users u ON u.id = ea.user_id
        JOIN subjects s ON s.id = ea.subject_id
        WHERE ea.proctor_status != 'completed' AND ea.submitted_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
        ORDER BY ea.id DESC
    ");
    
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $fullName = trim($row['f_name'] . ' ' . $row['l_name']);
            if (empty($fullName)) $fullName = $row['u_name'];
            $row['student_name'] = $fullName;
            $active[] = $row;
        }
    }
    echo json_encode($active);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Proctor Invigilation Console | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        .proctor-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        .proctor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
        }
        .proctor-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            border: 2px solid var(--gray-200);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.3s ease;
        }
        .proctor-card.warning-1 { border-color: #f59e0b; background: #fffbeb; }
        .proctor-card.warning-2 { border-color: #ef4444; background: #fef2f2; }
        .proctor-card.suspended { border-color: #7f1d1d; background: #fca5a5; }
        .proctor-card.paused { border-color: #3b82f6; background: #eff6ff; }
        
        .student-meta {
            font-size: 0.8rem;
            color: var(--gray-600);
            margin-top: 0.25rem;
        }
        .progress-bar-container {
            background: var(--gray-200);
            border-radius: 10px;
            height: 6px;
            width: 100%;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        .progress-bar-fill {
            background: var(--primary);
            height: 100%;
            transition: width 0.4s ease;
        }
        .proctor-actions {
            display: flex;
            gap: 0.4rem;
            margin-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
            padding-top: 1rem;
        }
        .btn-action {
            flex-grow: 1;
            padding: 0.45rem;
            font-size: 0.78rem;
            font-weight: 700;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }
        .btn-warn { background: #f59e0b; color: white; }
        .btn-warn:hover { background: #d97706; }
        .btn-pause { background: #3b82f6; color: white; }
        .btn-pause:hover { background: #2563eb; }
        .btn-resume { background: #10b981; color: white; }
        .btn-resume:hover { background: #059669; }
        .btn-term { background: #ef4444; color: white; }
        .btn-term:hover { background: #dc2626; }
    </style>
</head>
<body>
    <?php include("modern_header.php"); ?>

    <div class="container">
        <!-- Page Header -->
        <div style="margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="color: var(--white); margin-bottom: 0.25rem;">Live Active Proctor Console</h1>
                <p style="color: rgba(255,255,255,0.85); font-size: 0.95rem;">Real-time invigilation, dynamic session pausing, warnings telemetry, and force submissions.</p>
            </div>
            <a href="admin_dashboard.php" class="btn btn-inline" style="background: rgba(255,255,255,0.2); color: var(--white); border: 1px solid rgba(255,255,255,0.4); font-size: 0.9rem;">
                &larr; Admin Portal
            </a>
        </div>

        <!-- Main Workspace -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>Active Candidate Sessions</h2>
                <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(99,102,241,0.1); padding: 0.3rem 0.8rem; border-radius: 20px; color: var(--primary); font-size: 0.8rem; font-weight: 600;">
                    <span style="animation: pulse 1.5s infinite; color: #10b981;">●</span> Live Telemetry Connected
                </div>
            </div>

            <div id="no-active-sessions" class="text-center text-muted" style="padding: 4rem; display: none;">
                📭 No active test sessions currently in progress. 
            </div>

            <div class="proctor-grid" id="proctor-grid">
                <!-- Programmatically populated -->
            </div>
        </div>
    </div>

    <script>
        const grid = document.getElementById('proctor-grid');
        const noSessions = document.getElementById('no-active-sessions');

        function pollActiveSessions() {
            fetch('proctor_console.php?poll=1')
                .then(res => res.json())
                .then(data => {
                    grid.innerHTML = '';
                    if (data.length === 0) {
                        noSessions.style.display = 'block';
                        return;
                    }
                    noSessions.style.display = 'none';

                    data.forEach(session => {
                        const card = document.createElement('div');
                        let statusClass = session.proctor_status;
                        if (session.proctor_paused == 1) statusClass = 'paused';

                        card.className = `proctor-card ${statusClass}`;
                        
                        const progPercent = session.total_questions > 0 
                            ? Math.round((session.answered / session.total_questions) * 100) 
                            : 0;

                        card.innerHTML = `
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <strong style="font-size: 1.1rem; color: var(--gray-900);">${session.student_name}</strong>
                                    <span class="badge ${session.exam_mode === 'official' ? 'badge-admin' : 'badge-student'}">
                                        ${session.exam_mode}
                                    </span>
                                </div>
                                <div class="student-meta">Username: <strong>${session.u_name}</strong></div>
                                
                                <div style="margin-top: 1rem; border-top: 1px dashed var(--gray-200); padding-top: 0.8rem;">
                                    <div style="font-size: 0.82rem; color: var(--gray-700);">Subject: <strong>${session.subject_name}</strong></div>
                                    <div style="font-size: 0.8rem; color: var(--gray-600); margin-top: 0.25rem;">Level: <strong>${session.level}</strong></div>
                                </div>

                                <div style="margin-top: 1rem;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.72rem; color: var(--gray-600);">
                                        <span>Progress:</span>
                                        <strong>${session.answered} / ${session.total_questions} questions (${progPercent}%)</strong>
                                    </div>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar-fill" style="width: ${progPercent}%;"></div>
                                    </div>
                                </div>

                                <div style="margin-top: 1rem; font-size: 0.8rem; font-weight: 600;">
                                    Status: <span style="text-transform: uppercase;">${session.proctor_paused == 1 ? '⏸ PAUSED' : '👁 ' + session.proctor_status}</span>
                                </div>
                            </div>

                            <div class="proctor-actions">
                                <button class="btn-action btn-warn" onclick="sendProctorCmd('warn', ${session.id})">⚠️ Warn</button>
                                ${session.proctor_paused == 1 
                                    ? `<button class="btn-action btn-resume" onclick="sendProctorCmd('resume', ${session.id})">▶ Resume</button>`
                                    : `<button class="btn-action btn-pause" onclick="sendProctorCmd('pause', ${session.id})">⏸ Pause</button>`
                                }
                                <button class="btn-action btn-term" onclick="sendProctorCmd('terminate', ${session.id})">🛑 Submit</button>
                            </div>
                        `;
                        grid.appendChild(card);
                    });
                })
                .catch(err => console.error("Polling error:", err));
        }

        function sendProctorCmd(action, attemptId) {
            if (action === 'terminate' && !confirm("Are you sure you want to force-submit this candidate's exam?")) {
                return;
            }
            fetch(`proctor_console.php?action=${action}&attempt_id=${attemptId}`)
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        pollActiveSessions();
                    } else {
                        alert("Failed to execute proctor command.");
                    }
                });
        }

        // Poll every 3 seconds for fast synchronization
        pollActiveSessions();
        setInterval(pollActiveSessions, 3000);
    </script>
</body>
</html>
