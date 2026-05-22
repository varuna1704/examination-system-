<?php
require_once 'lib/security.php';
require_login();

// Allow admin and teacher roles
if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header('Location: subject.php');
    exit;
}

require 'config.php';

// 1. General Metrics
$metrics = [
    'total_attempts' => 0,
    'average_score' => 0.0,
    'pass_rate' => 0.0,
];

$res = $conn->query("SELECT COUNT(*) as count, AVG(percentage) as avg_p FROM exam_attempts");
if ($res) {
    $row = $res->fetch_assoc();
    $metrics['total_attempts'] = (int)$row['count'];
    $metrics['average_score'] = round((float)$row['avg_p'], 2);
}

$pass_res = $conn->query("SELECT COUNT(*) as count FROM exam_attempts WHERE percentage >= 50.00");
if ($pass_res && $metrics['total_attempts'] > 0) {
    $pass_count = (int)$pass_res->fetch_assoc()['count'];
    $metrics['pass_rate'] = round(($pass_count / $metrics['total_attempts']) * 100, 2);
}

// 2. Score Distribution (Bell-Curve bucket aggregations)
$distribution = [
    '0-20' => 0,
    '21-40' => 0,
    '41-60' => 0,
    '61-80' => 0,
    '81-100' => 0
];
$dist_res = $conn->query("
    SELECT 
        SUM(CASE WHEN percentage <= 20 THEN 1 ELSE 0 END) as b1,
        SUM(CASE WHEN percentage > 20 AND percentage <= 40 THEN 1 ELSE 0 END) as b2,
        SUM(CASE WHEN percentage > 40 AND percentage <= 60 THEN 1 ELSE 0 END) as b3,
        SUM(CASE WHEN percentage > 60 AND percentage <= 80 THEN 1 ELSE 0 END) as b4,
        SUM(CASE WHEN percentage > 80 THEN 1 ELSE 0 END) as b5
    FROM exam_attempts
");
if ($dist_res) {
    $row = $dist_res->fetch_assoc();
    $distribution['0-20'] = (int)$row['b1'];
    $distribution['21-40'] = (int)$row['b2'];
    $distribution['41-60'] = (int)$row['b3'];
    $distribution['61-80'] = (int)$row['b4'];
    $distribution['81-100'] = (int)$row['b5'];
}

// 3. Top 5 Hardest Questions Panel (lowest accuracy rates)
$hard_questions = [];
$hard_res = $conn->query("
    SELECT q.id, q.question, s.name as subject_name,
           COUNT(aa.id) as total_answers,
           SUM(aa.is_correct) as correct_answers,
           (SUM(aa.is_correct) / COUNT(aa.id)) * 100 as accuracy
    FROM attempt_answers aa
    JOIN questions q ON q.id = aa.question_id
    JOIN subjects s ON s.id = q.subject_id
    GROUP BY q.id
    HAVING total_answers >= 3
    ORDER BY accuracy ASC
    LIMIT 5
");
if ($hard_res) {
    while ($row = $hard_res->fetch_assoc()) {
        $hard_questions[] = $row;
    }
}

// 4. Urgent Support Candidates (average score below 50.00%)
$struggling_candidates = [];
$strug_res = $conn->query("
    SELECT u.id, u.f_name, u.l_name, u.u_name,
           COUNT(ea.id) as attempts,
           AVG(ea.percentage) as avg_percentage
    FROM users u
    JOIN exam_attempts ea ON ea.user_id = u.id
    WHERE u.role = 'student'
    GROUP BY u.id
    HAVING avg_percentage < 50.00 AND attempts >= 1
    ORDER BY avg_percentage ASC
    LIMIT 5
");
if ($strug_res) {
    while ($row = $strug_res->fetch_assoc()) {
        $struggling_candidates[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Cohort Performance Analytics | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        @media (min-width: 992px) {
            .analytics-grid {
                grid-template-columns: 3fr 2fr;
            }
        }
        .bell-curve-container {
            position: relative;
            height: 250px;
            margin-top: 2rem;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            border-bottom: 2px solid var(--gray-300);
            padding-bottom: 0.5rem;
        }
        .bell-bar {
            flex-grow: 1;
            margin: 0 0.5rem;
            background: linear-gradient(180deg, var(--secondary) 0%, var(--primary) 100%);
            border-radius: 8px 8px 0 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            color: white;
            font-size: 0.8rem;
            font-weight: bold;
            padding-bottom: 0.5rem;
            transition: height 0.5s ease-out;
            min-height: 20px;
        }
        .bell-label {
            text-align: center;
            font-size: 0.72rem;
            color: var(--gray-600);
            margin-top: 0.5rem;
            font-weight: 600;
        }
        .bell-axis {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
        }
        .support-card {
            background: #fff5f5;
            border-left: 5px solid var(--accent);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <?php include("modern_header.php"); ?>

    <div class="container">
        <!-- Page Header -->
        <div style="margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="color: var(--white); margin-bottom: 0.25rem;">Advanced Performance Analytics</h1>
                <p style="color: rgba(255,255,255,0.85); font-size: 0.95rem;">Curriculum metrics, grade distributions, and predictive student success insights.</p>
            </div>
            <a href="admin_dashboard.php" class="btn btn-inline" style="background: rgba(255,255,255,0.2); color: var(--white); border: 1px solid rgba(255,255,255,0.4); font-size: 0.9rem;">
                &larr; Admin Portal
            </a>
        </div>

        <!-- Metric KPI Cards -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-info">
                    <h3>Total Exams Attempted</h3>
                    <div class="metric-value"><?php echo number_format($metrics['total_attempts']); ?></div>
                </div>
                <div class="metric-icon">📊</div>
            </div>
            <div class="metric-card success">
                <div class="metric-info">
                    <h3>Average Class Score</h3>
                    <div class="metric-value"><?php echo $metrics['average_score']; ?>%</div>
                </div>
                <div class="metric-icon">📈</div>
            </div>
            <div class="metric-card secondary">
                <div class="metric-info">
                    <h3>Overall Pass Rate</h3>
                    <div class="metric-value"><?php echo $metrics['pass_rate']; ?>%</div>
                </div>
                <div class="metric-icon">🏆</div>
            </div>
        </div>

        <!-- Analytics Split Grid Workspace -->
        <div class="analytics-grid">
            <!-- Left Side: Bell-Curve distribution and struggling students -->
            <div>
                <!-- Score Distribution Bell Curve Card -->
                <div class="admin-card" style="margin-bottom: 2rem;">
                    <div class="admin-card-header">
                        <h2>Grade & Score Distribution Bell-Curve</h2>
                        <span style="font-size: 0.8rem; color: var(--gray-600); font-weight: 500;">Aggregated attempts</span>
                    </div>
                    
                    <?php
                    $max_bar_count = max(array_values($distribution));
                    if ($max_bar_count === 0) $max_bar_count = 1;
                    ?>
                    
                    <div class="bell-curve-container">
                        <?php foreach ($distribution as $bucket => $count): 
                            $height_perc = ($count / $max_bar_count) * 80 + 10; // baseline height
                        ?>
                            <div class="bell-bar" style="height: <?php echo $height_perc; ?>%;">
                                <span><?php echo $count; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="bell-axis">
                        <?php foreach (array_keys($distribution) as $bucket): ?>
                            <div class="bell-label" style="width: 20%;"><?php echo $bucket; ?>%</div>
                        <?php endforeach; ?>
                    </div>
                    <p style="font-size: 0.8rem; text-align: center; color: var(--gray-600); margin-top: 1.5rem; font-style: italic;">
                        Visualizing standard performance curve: Left (Failing) to Right (Advanced Competency).
                    </p>
                </div>

                <!-- Urgent Support Candidates Panel -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>⚠️ Urgent Support Candidates</h2>
                        <span class="badge badge-admin">Action Required</span>
                    </div>
                    <p class="text-muted" style="margin-bottom: 1.5rem;">The following students have an average exam accuracy below 50% across their attempts. Intervention is recommended.</p>
                    
                    <?php if (empty($struggling_candidates)): ?>
                        <div class="text-center text-muted" style="padding: 2rem; background: var(--gray-50); border-radius: 8px;">
                            🎉 All students are performing above the support threshold!
                        </div>
                    <?php else: ?>
                        <?php foreach ($struggling_candidates as $cand): 
                            $name = trim($cand['f_name'] . ' ' . $cand['l_name']);
                            if (empty($name)) $name = $cand['u_name'];
                        ?>
                            <div class="support-card">
                                <div>
                                    <strong style="color: var(--gray-900); font-size: 0.95rem;"><?php echo e($name); ?> (<?php echo e($cand['u_name']); ?>)</strong>
                                    <div style="font-size: 0.8rem; color: var(--gray-600); margin-top: 0.25rem;">
                                        Total Test Submissions: <strong><?php echo $cand['attempts']; ?></strong>
                                    </div>
                                </div>
                                <span style="font-size: 1rem; font-weight: 700; color: var(--accent); background: #fee2e2; padding: 0.25rem 0.65rem; border-radius: 8px;">
                                    <?php echo round($cand['avg_percentage'], 2); ?>% Avg
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Side: Hardest Questions -->
            <div>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>🔥 Top 5 Hardest Questions</h2>
                    </div>
                    <p class="text-muted" style="margin-bottom: 1.5rem;">These questions have registered the lowest success rates in attempts, pointing to possible curriculum gaps.</p>
                    
                    <div class="admin-subject-list">
                        <?php if (empty($hard_questions)): ?>
                            <p class="text-muted" style="font-style: italic;">No student response statistics gathered yet.</p>
                        <?php else: ?>
                            <?php foreach ($hard_questions as $index => $q): 
                                $acc = round($q['accuracy'], 1);
                                $color = ($acc < 30) ? 'var(--accent)' : 'var(--secondary)';
                            ?>
                                <div class="admin-subject-item" style="flex-direction: column; align-items: flex-start; gap: 0.5rem; border-left: 4px solid <?php echo $color; ?>; padding: 1.25rem;">
                                    <div style="display: flex; justify-content: space-between; width: 100%; align-items: center;">
                                        <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; color: var(--gray-600);">
                                            Rank #<?php echo ($index + 1); ?> &bull; <?php echo e($q['subject_name']); ?>
                                        </span>
                                        <span style="font-size: 0.8rem; font-weight: bold; color: <?php echo $color; ?>; background: rgba(244,63,94,0.1); padding: 0.2rem 0.5rem; border-radius: 6px;">
                                            <?php echo $acc; ?>% Correct
                                        </span>
                                    </div>
                                    <blockquote style="font-size: 0.85rem; font-weight: 500; color: var(--gray-900); font-style: italic; margin-top: 0.25rem;">
                                        "<?php echo e(mb_strimwidth($q['question'], 0, 95, '...')); ?>"
                                    </blockquote>
                                    <span style="font-size: 0.72rem; color: var(--gray-600); margin-top: 0.25rem;">
                                        Correct Submissions: <strong><?php echo $q['correct_answers']; ?></strong> out of <strong><?php echo $q['total_answers']; ?></strong>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
