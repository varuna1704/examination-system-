<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/security.php';
require_login();

// Retrieve all attempts for this user
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT ea.*, s.name AS subject_name 
    FROM exam_attempts ea
    JOIN subjects s ON ea.subject_id = s.id
    WHERE ea.user_id = ?
    ORDER BY ea.submitted_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$attempts = $stmt->get_result();

// Aggregate stats for the currently logged-in user
// 1. Total Attempts
$stmt_total = $conn->prepare("SELECT COUNT(*) AS total FROM exam_attempts WHERE user_id = ?");
$stmt_total->bind_param("i", $userId);
$stmt_total->execute();
$total_attempts = $stmt_total->get_result()->fetch_assoc()['total'] ?? 0;

// 2. Certifications Earned (Passed official exams)
$stmt_certs = $conn->prepare("SELECT COUNT(*) AS certs FROM exam_attempts WHERE user_id = ? AND exam_mode = 'official' AND percentage >= 50.00");
$stmt_certs->bind_param("i", $userId);
$stmt_certs->execute();
$certs_earned = $stmt_certs->get_result()->fetch_assoc()['certs'] ?? 0;

// 3. Maximum Percentage (High Score)
$stmt_max = $conn->prepare("SELECT MAX(percentage) AS max_perc FROM exam_attempts WHERE user_id = ?");
$stmt_max->bind_param("i", $userId);
$stmt_max->execute();
$max_percentage = $stmt_max->get_result()->fetch_assoc()['max_perc'] ?? 0.00;

// 4. Average Percentage
$stmt_avg = $conn->prepare("SELECT AVG(percentage) AS avg_perc FROM exam_attempts WHERE user_id = ?");
$stmt_avg->bind_param("i", $userId);
$stmt_avg->execute();
$avg_percentage = $stmt_avg->get_result()->fetch_assoc()['avg_perc'] ?? 0.00;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        .history-table-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow-x: auto;
            margin-top: 1rem;
            border: 1px solid var(--gray-200);
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.95rem;
        }
        .history-table th {
            background: var(--gray-50);
            padding: 1.2rem 1.5rem;
            font-weight: 700;
            color: var(--gray-700);
            border-bottom: 2px solid var(--gray-200);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
        }
        .history-table td {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid var(--gray-100);
            color: var(--gray-900);
            vertical-align: middle;
        }
        .history-table tr:last-child td {
            border-bottom: none;
        }
        .history-table tr:hover td {
            background: var(--gray-50);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-official {
            background: #e0f2fe;
            color: #0369a1;
        }
        .badge-mock {
            background: #f3f4f6;
            color: #4b5563;
        }
        .badge-pass {
            background: #dcfce7;
            color: #166534;
        }
        .badge-fail {
            background: #fee2e2;
            color: #991b1b;
        }
        .action-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            margin-right: 1.25rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        .action-link:hover {
            text-decoration: underline;
        }
        .action-link.cert {
            color: #d97706;
        }

        /* Performance Analytics Dashboard Grid */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        .analytics-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .analytics-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        .analytics-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .analytics-info {
            display: flex;
            flex-direction: column;
        }
        .analytics-label {
            font-size: 0.72rem;
            color: var(--gray-600);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .analytics-val {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1.2;
            margin-top: 0.2rem;
        }
        .accent-blue { border-left: 5px solid #3b82f6; }
        .accent-blue .analytics-icon-wrapper { background: #eff6ff; color: #3b82f6; }

        .accent-gold { border-left: 5px solid #eab308; }
        .accent-gold .analytics-icon-wrapper { background: #fef9c3; color: #ca8a04; }

        .accent-rose { border-left: 5px solid #f43f5e; }
        .accent-rose .analytics-icon-wrapper { background: #fff1f2; color: #f43f5e; }

        .accent-emerald { border-left: 5px solid #10b981; }
        .accent-emerald .analytics-icon-wrapper { background: #ecfdf5; color: #10b981; }
    </style>
</head>
<body>
    <?php include("modern_header.php"); ?>
    
    <div class="container" style="max-width: 1200px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 2rem;">
            <div>
                <h1 style="color: white; margin-bottom: 0.25rem;">Exam History & Certificates</h1>
                <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem;">Review all your previous attempts, performance stats, and print earned certifications.</p>
            </div>
            <a href="subject.php" class="btn" style="width: auto;">&larr; Back to Dashboard</a>
        </div>

        <!-- Performance Analytics & Visual Summary -->
        <div class="analytics-grid">
            <div class="analytics-card accent-blue">
                <div class="analytics-icon-wrapper">📝</div>
                <div class="analytics-info">
                    <span class="analytics-label">Total Attempts</span>
                    <span class="analytics-val"><?php echo $total_attempts; ?></span>
                </div>
            </div>
            
            <div class="analytics-card accent-gold">
                <div class="analytics-icon-wrapper">🏆</div>
                <div class="analytics-info">
                    <span class="analytics-label">Certifications</span>
                    <span class="analytics-val"><?php echo $certs_earned; ?></span>
                </div>
            </div>
            
            <div class="analytics-card accent-rose">
                <div class="analytics-icon-wrapper">🔥</div>
                <div class="analytics-info">
                    <span class="analytics-label">Personal Best</span>
                    <span class="analytics-val"><?php echo round($max_percentage, 1); ?>%</span>
                </div>
            </div>
            
            <div class="analytics-card accent-emerald">
                <div class="analytics-icon-wrapper">📊</div>
                <div class="analytics-info">
                    <span class="analytics-label">Avg. Accuracy</span>
                    <span class="analytics-val"><?php echo round($avg_percentage, 1); ?>%</span>
                </div>
            </div>
        </div>

        <?php if ($attempts->num_rows == 0): ?>

            <div class="card text-center" style="padding: 4rem 2rem;">
                <span style="font-size: 4rem; display: block; margin-bottom: 1rem;">📝</span>
                <h2>No Attempts Found</h2>
                <p class="text-muted" style="margin-top: 0.5rem; margin-bottom: 1.5rem;">You haven't completed any examinations yet.</p>
                <a href="subject.php" class="btn">Take Your First Exam</a>
            </div>
        <?php else: ?>
            <div class="history-table-container">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Attempt Details</th>
                            <th>Exam Mode</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $attempts->fetch_assoc()): 
                            $passed = ($row['percentage'] >= 50.00);
                            $isOfficial = ($row['exam_mode'] === 'official');
                            $formattedDate = date("M j, Y, g:i a", strtotime($row['submitted_at']));
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--gray-900); font-size: 1.05rem;">
                                        <?php echo htmlspecialchars($row['subject_name']); ?>
                                    </div>
                                    <div style="color: var(--gray-500); font-size: 0.8rem; margin-top: 0.25rem;">
                                        Level: <strong><?php echo htmlspecialchars($row['level']); ?></strong> | <?php echo $formattedDate; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isOfficial): ?>
                                        <span class="badge badge-official">Official Exam</span>
                                    <?php else: ?>
                                        <span class="badge badge-mock">Mock Practice</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="color: var(--gray-900);"><?php echo (int)$row['score']; ?></strong>
                                    <span style="color: var(--gray-400);">/ <?php echo (int)$row['total_questions']; ?></span>
                                </td>
                                <td>
                                    <strong style="font-size: 1.05rem;"><?php echo round($row['percentage'], 1); ?>%</strong>
                                </td>
                                <td>
                                    <?php if ($passed): ?>
                                        <span class="badge badge-pass">Passed</span>
                                    <?php else: ?>
                                        <span class="badge badge-fail">Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="review.php?attempt_id=<?php echo $row['id']; ?>" class="action-link">
                                        🔍 Review Answers
                                    </a>
                                    <?php if ($isOfficial && $passed): ?>
                                        <a href="certificate.php?attempt_id=<?php echo $row['id']; ?>" target="_blank" class="action-link cert">
                                            🎓 View Certificate
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
