<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/security.php';
require_login();

$userId = $_SESSION['user_id'];
$filter_subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

// Fetch all subjects for the navigation tabs
$subjects_res = $conn->query("SELECT * FROM subjects ORDER BY name ASC");
$subjects = [];
while ($s = $subjects_res->fetch_assoc()) {
    $subjects[] = $s;
}

// Query best attempt of each user
$sql = "
    SELECT 
        ea.id,
        ea.user_id,
        ea.score,
        ea.total_questions,
        ea.percentage,
        ea.level,
        ea.started_at,
        ea.submitted_at,
        ea.exam_mode,
        TIMESTAMPDIFF(SECOND, ea.started_at, ea.submitted_at) AS time_taken,
        u.u_name,
        u.f_name,
        u.l_name,
        s.name AS subject_name,
        s.id AS subject_id
    FROM exam_attempts ea
    JOIN users u ON ea.user_id = u.id
    JOIN subjects s ON ea.subject_id = s.id
    INNER JOIN (
        SELECT user_id, subject_id, MAX(percentage) AS max_perc
        FROM exam_attempts
        WHERE exam_mode = 'official' AND percentage >= 50.00
        " . ($filter_subject_id > 0 ? "AND subject_id = $filter_subject_id" : "") . "
        GROUP BY user_id, subject_id
    ) best ON ea.user_id = best.user_id AND ea.subject_id = best.subject_id AND ea.percentage = best.max_perc
    WHERE ea.exam_mode = 'official' AND ea.percentage >= 50.00
";

if ($filter_subject_id > 0) {
    $sql .= " AND ea.subject_id = $filter_subject_id";
}

$sql .= "
    GROUP BY ea.user_id, ea.subject_id
    ORDER BY ea.percentage DESC, time_taken ASC, ea.submitted_at DESC
    LIMIT 50
";

$leaderboard_res = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Leaderboard | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        .leaderboard-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            margin-top: 1.5rem;
        }
        
        /* Subject Tab Filters */
        .tabs-container {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 2rem;
            overflow-x: auto;
            padding-bottom: 0.75rem;
            scrollbar-width: thin;
        }
        .tabs-container::-webkit-scrollbar {
            height: 4px;
        }
        .tabs-container::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }
        .tab-btn {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            color: white;
            padding: 0.6rem 1.25rem;
            border-radius: 30px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.1);
            white-space: nowrap;
            transition: all 0.3s ease;
        }
        .tab-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        .tab-btn.active {
            background: white;
            color: var(--primary-dark);
            border-color: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        /* Leaderboard Table */
        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .leaderboard-table th {
            background: var(--gray-50);
            padding: 1.2rem 1.5rem;
            font-weight: 700;
            color: var(--gray-700);
            border-bottom: 2px solid var(--gray-200);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
        }
        .leaderboard-table td {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid var(--gray-100);
            color: var(--gray-900);
            vertical-align: middle;
        }
        .leaderboard-table tr:last-child td {
            border-bottom: none;
        }
        .leaderboard-table tr:hover td {
            background: var(--gray-50);
        }
        
        /* Highlight logged in user */
        .leaderboard-table tr.current-user-row td {
            background: #f0fdf4;
            border-top: 1px solid #bbf7d0;
            border-bottom: 1px solid #bbf7d0;
        }
        .leaderboard-table tr.current-user-row:hover td {
            background: #f0fdf4;
        }

        /* Rank Badges */
        .rank-badge {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--gray-700);
            background: var(--gray-100);
        }
        .rank-1 {
            background: linear-gradient(135deg, #fef08a 0%, #facc15 100%);
            color: #854d0e;
            box-shadow: 0 2px 6px rgba(250, 204, 21, 0.4);
            font-size: 1.2rem;
        }
        .rank-2 {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            color: #334155;
            box-shadow: 0 2px 6px rgba(203, 213, 225, 0.4);
            font-size: 1.2rem;
        }
        .rank-3 {
            background: linear-gradient(135deg, #ffedd5 0%, #fdba74 100%);
            color: #9a3412;
            box-shadow: 0 2px 6px rgba(253, 186, 116, 0.4);
            font-size: 1.2rem;
        }

        /* User Info block */
        .user-info-cell {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }
        .avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.85rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .username-txt {
            font-weight: 700;
            color: var(--gray-900);
        }
        .user-tag {
            background: #dcfce7;
            color: #15803d;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            margin-left: 0.4rem;
            text-transform: uppercase;
        }

        /* Metric badges */
        .metric-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .metric-time {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #ffedd5;
        }
        .metric-score {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
        }
        
        .speed-lightning {
            background: #fef08a;
            color: #a16207;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 0.1rem;
            text-transform: uppercase;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        /* Difficulty Badge styling */
        .diff-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.6rem;
            border-radius: 12px;
            text-transform: uppercase;
        }
        .diff-Easy { background: #e0f2fe; color: #0369a1; }
        .diff-Medium { background: #fef3c7; color: #b45309; }
        .diff-Hard { background: #fee2e2; color: #b91c1c; }
        .diff-Advanced { background: #faf5ff; color: #6b21a8; }
        .diff-Expert { background: #ecfdf5; color: #047857; }

        /* Banner */
        .podium-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .podium-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--radius);
            padding: 1.5rem;
            text-align: center;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: all 0.3s ease;
        }
        .podium-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        .podium-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
        }
        .podium-1::before { background: #facc15; }
        .podium-2::before { background: #cbd5e1; }
        .podium-3::before { background: #fdba74; }

        .podium-crown {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            line-height: 1;
        }
        .podium-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.35rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            margin-bottom: 1rem;
            border: 3px solid white;
        }
        .podium-1 .podium-avatar { border-color: #fef08a; }
        .podium-2 .podium-avatar { border-color: #e2e8f0; }
        .podium-3 .podium-avatar { border-color: #ffedd5; }
        
        .podium-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.2rem;
        }
        .podium-score {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 0.25rem;
        }
        .podium-meta {
            font-size: 0.78rem;
            color: var(--gray-600);
        }
    </style>
</head>
<body>
    <?php include("modern_header.php"); ?>
    
    <div class="container" style="max-width: 1200px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 2rem;">
            <div>
                <h1 style="color: white; margin-bottom: 0.25rem;">🏆 Portal Leaderboard</h1>
                <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem;">Rankings are computed based on highest accuracy, followed by completion speed.</p>
            </div>
            <a href="subject.php" class="btn" style="width: auto;">&larr; Back to Dashboard</a>
        </div>

        <!-- Subject Navigation Tabs -->
        <div class="tabs-container">
            <a href="leaderboard.php" class="tab-btn <?php echo ($filter_subject_id == 0) ? 'active' : ''; ?>">🌐 All Subjects</a>
            <?php foreach ($subjects as $sub): ?>
                <a href="leaderboard.php?subject_id=<?php echo $sub['id']; ?>" class="tab-btn <?php echo ($filter_subject_id == $sub['id']) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($sub['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php
        // Fetch top rows into memory to build Podium cards for the top 3!
        $rows = [];
        while ($row = $leaderboard_res->fetch_assoc()) {
            $rows[] = $row;
        }
        
        $top_three = array_slice($rows, 0, 3);
        ?>

        <?php if (!empty($top_three)): ?>
            <!-- Podium Section -->
            <div class="podium-section">
                <!-- Rank 2 -->
                <?php if (isset($top_three[1])): 
                    $p2 = $top_three[1];
                    $initials2 = strtoupper(substr($p2['f_name'], 0, 1) . substr($p2['l_name'], 0, 1));
                    if(empty($initials2)) $initials2 = strtoupper(substr($p2['u_name'], 0, 2));
                    $grad2 = abs(crc32($p2['u_name'])) % 8;
                    $gradients = [
                        'linear-gradient(135deg, #f6d365 0%, #fda085 100%)',
                        'linear-gradient(135deg, #abecd6 0%, #fbed96 100%)',
                        'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)',
                        'linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%)',
                        'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
                        'linear-gradient(135deg, #cfd9df 0%, #e2ebf0 100%)',
                        'linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%)',
                        'linear-gradient(135deg, #fdcbf1 0%, #e6dee9 100%)'
                    ];
                    $p2_time = $p2['time_taken'];
                    $p2_time_str = ($p2_time >= 60) ? floor($p2_time / 60) . 'm ' . ($p2_time % 60) . 's' : $p2_time . 's';
                ?>
                    <div class="podium-card podium-2">
                        <div class="podium-crown">🥈</div>
                        <div class="podium-avatar" style="background: <?php echo $gradients[$grad2]; ?>;">
                            <?php echo $initials2; ?>
                        </div>
                        <div class="podium-name">
                            <?php echo htmlspecialchars($p2['f_name'] . ' ' . $p2['l_name']); ?>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--gray-600); margin-bottom: 0.5rem;">@<?php echo htmlspecialchars($p2['u_name']); ?></div>
                        <div class="podium-score"><?php echo round($p2['percentage'], 1); ?>%</div>
                        <div class="podium-meta">
                            Score: <strong><?php echo $p2['score']; ?>/<?php echo $p2['total_questions']; ?></strong> | Time: <strong><?php echo $p2_time_str; ?></strong>
                        </div>
                        <div style="margin-top: 0.5rem; font-size: 0.72rem; font-weight: 600; color: var(--gray-600);">
                            <?php echo htmlspecialchars($p2['subject_name']); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Rank 1 -->
                <?php if (isset($top_three[0])): 
                    $p1 = $top_three[0];
                    $initials1 = strtoupper(substr($p1['f_name'], 0, 1) . substr($p1['l_name'], 0, 1));
                    if(empty($initials1)) $initials1 = strtoupper(substr($p1['u_name'], 0, 2));
                    $grad1 = abs(crc32($p1['u_name'])) % 8;
                    $p1_time = $p1['time_taken'];
                    $p1_time_str = ($p1_time >= 60) ? floor($p1_time / 60) . 'm ' . ($p1_time % 60) . 's' : $p1_time . 's';
                ?>
                    <div class="podium-card podium-1" style="transform: scale(1.05); z-index: 2;">
                        <div class="podium-crown">👑</div>
                        <div class="podium-avatar" style="background: <?php echo $gradients[$grad1]; ?>;">
                            <?php echo $initials1; ?>
                        </div>
                        <div class="podium-name" style="font-size: 1.35rem;">
                            <?php echo htmlspecialchars($p1['f_name'] . ' ' . $p1['l_name']); ?>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--gray-600); margin-bottom: 0.5rem;">@<?php echo htmlspecialchars($p1['u_name']); ?></div>
                        <div class="podium-score" style="font-size: 1.55rem; color: #eab308;"><?php echo round($p1['percentage'], 1); ?>%</div>
                        <div class="podium-meta">
                            Score: <strong><?php echo $p1['score']; ?>/<?php echo $p1['total_questions']; ?></strong> | Time: <strong><?php echo $p1_time_str; ?></strong>
                        </div>
                        <div style="margin-top: 0.5rem; font-size: 0.72rem; font-weight: 600; color: var(--primary-dark);">
                            🥇 <?php echo htmlspecialchars($p1['subject_name']); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Rank 3 -->
                <?php if (isset($top_three[2])): 
                    $p3 = $top_three[2];
                    $initials3 = strtoupper(substr($p3['f_name'], 0, 1) . substr($p3['l_name'], 0, 1));
                    if(empty($initials3)) $initials3 = strtoupper(substr($p3['u_name'], 0, 2));
                    $grad3 = abs(crc32($p3['u_name'])) % 8;
                    $p3_time = $p3['time_taken'];
                    $p3_time_str = ($p3_time >= 60) ? floor($p3_time / 60) . 'm ' . ($p3_time % 60) . 's' : $p3_time . 's';
                ?>
                    <div class="podium-card podium-3">
                        <div class="podium-crown">🥉</div>
                        <div class="podium-avatar" style="background: <?php echo $gradients[$grad3]; ?>;">
                            <?php echo $initials3; ?>
                        </div>
                        <div class="podium-name">
                            <?php echo htmlspecialchars($p3['f_name'] . ' ' . $p3['l_name']); ?>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--gray-600); margin-bottom: 0.5rem;">@<?php echo htmlspecialchars($p3['u_name']); ?></div>
                        <div class="podium-score"><?php echo round($p3['percentage'], 1); ?>%</div>
                        <div class="podium-meta">
                            Score: <strong><?php echo $p3['score']; ?>/<?php echo $p3['total_questions']; ?></strong> | Time: <strong><?php echo $p3_time_str; ?></strong>
                        </div>
                        <div style="margin-top: 0.5rem; font-size: 0.72rem; font-weight: 600; color: var(--gray-600);">
                            <?php echo htmlspecialchars($p3['subject_name']); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($rows)): ?>
            <div class="card text-center" style="padding: 4rem 2rem; max-width: 100%;">
                <span style="font-size: 4rem; display: block; margin-bottom: 1rem;">🏁</span>
                <h2>Leaderboard is Empty</h2>
                <p class="text-muted" style="margin-top: 0.5rem; margin-bottom: 1.5rem;">Be the first one to qualify for the official exam and rank here!</p>
                <a href="subject.php" class="btn" style="width: auto; padding: 0.75rem 2rem;">Take an Exam Now</a>
            </div>
        <?php else: ?>
            <div class="leaderboard-container">
                <table class="leaderboard-table">
                    <thead>
                        <tr>
                            <th style="width: 80px; text-align: center;">Rank</th>
                            <th>Candidate</th>
                            <th>Subject</th>
                            <th>Difficulty</th>
                            <th style="text-align: center;">Score</th>
                            <th>Completion Time</th>
                            <th>Accuracy</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 0;
                        foreach ($rows as $row): 
                            $rank++;
                            $isCurrentUser = ($row['user_id'] == $userId);
                            $time = $row['time_taken'];
                            $time_str = ($time >= 60) ? floor($time / 60) . 'm ' . ($time % 60) . 's' : $time . 's';
                            $lightning = ($time < 300); // completed under 5 minutes
                            
                            $initials = strtoupper(substr($row['f_name'], 0, 1) . substr($row['l_name'], 0, 1));
                            if (empty($initials)) {
                                $initials = strtoupper(substr($row['u_name'], 0, 2));
                            }
                            $gradients = [
                                'linear-gradient(135deg, #f6d365 0%, #fda085 100%)',
                                'linear-gradient(135deg, #abecd6 0%, #fbed96 100%)',
                                'linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%)',
                                'linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%)',
                                'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
                                'linear-gradient(135deg, #cfd9df 0%, #e2ebf0 100%)',
                                'linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%)',
                                'linear-gradient(135deg, #fdcbf1 0%, #e6dee9 100%)'
                            ];
                            $grad_idx = abs(crc32($row['u_name'])) % count($gradients);
                            $avatar_bg = $gradients[$grad_idx];
                            
                            $rankClass = '';
                            $rankContent = $rank;
                            if ($rank === 1) {
                                $rankClass = 'rank-1';
                                $rankContent = '🥇';
                            } else if ($rank === 2) {
                                $rankClass = 'rank-2';
                                $rankContent = '🥈';
                            } else if ($rank === 3) {
                                $rankClass = 'rank-3';
                                $rankContent = '🥉';
                            }
                        ?>
                            <tr class="<?php echo $isCurrentUser ? 'current-user-row' : ''; ?>">
                                <td style="text-align: center;">
                                    <span class="rank-badge <?php echo $rankClass; ?>"><?php echo $rankContent; ?></span>
                                </td>
                                <td>
                                    <div class="user-info-cell">
                                        <div class="avatar-circle" style="background: <?php echo $avatar_bg; ?>;">
                                            <?php echo $initials; ?>
                                        </div>
                                        <div>
                                            <span class="username-txt">
                                                <?php echo htmlspecialchars($row['f_name'] . ' ' . $row['l_name']); ?>
                                            </span>
                                            <?php if ($isCurrentUser): ?>
                                                <span class="user-tag">You</span>
                                            <?php endif; ?>
                                            <div style="font-size: 0.75rem; color: var(--gray-600);">@<?php echo htmlspecialchars($row['u_name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--gray-900); font-size: 0.92rem;">
                                        <?php echo htmlspecialchars($row['subject_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="diff-badge diff-<?php echo $row['level']; ?>">
                                        <?php echo htmlspecialchars($row['level']); ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="metric-badge metric-score">
                                        <strong><?php echo $row['score']; ?></strong>
                                        <span style="opacity: 0.6; font-weight: 400;">/<?php echo $row['total_questions']; ?></span>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                        <span class="metric-badge metric-time">
                                            ⏱️ <?php echo $time_str; ?>
                                        </span>
                                        <?php if ($lightning): ?>
                                            <div>
                                                <span class="speed-lightning">⚡ Turbo speed</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <strong style="font-size: 1.05rem; color: var(--gray-900); min-width: 45px; display: inline-block; text-align: right;">
                                            <?php echo round($row['percentage'], 1); ?>%
                                        </strong>
                                        <div style="background: var(--gray-200); border-radius: 4px; height: 6px; width: 60px; overflow: hidden; display: inline-block;">
                                            <div style="background: var(--primary); height: 100%; width: <?php echo $row['percentage']; ?>%;"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
