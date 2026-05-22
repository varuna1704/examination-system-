<?php
session_start();
include 'config.php';
require_once 'lib/security.php';

// Determine which user's passport to view
$loggedInUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$viewUserId = isset($_GET['uid']) ? (int)$_GET['uid'] : $loggedInUserId;

if ($viewUserId <= 0) {
    header("Location: index.php");
    exit;
}

// Fetch user details
$userStmt = $conn->prepare("SELECT u_name, f_name, l_name, created_at FROM users WHERE id = ?");
$userStmt->bind_param("i", $viewUserId);
$userStmt->execute();
$userInfo = $userStmt->get_result()->fetch_assoc();

if (!$userInfo) {
    die("User not found.");
}

$isOwner = ($viewUserId === $loggedInUserId);

// 1. Automatic Badge Checker & Unlocker (Run only for the logged-in owner)
if ($isOwner) {
    // Fetch all badges definitions
    $badgeRes = $conn->query("SELECT * FROM badges");
    if ($badgeRes) {
        $checkPassStmt = $conn->prepare("
            SELECT COUNT(*) as cnt 
            FROM exam_attempts ea 
            JOIN subjects s ON s.id = ea.subject_id 
            WHERE ea.user_id = ? AND ea.percentage >= 50.00 AND s.name = ?
        ");
        $checkSpeedStmt = $conn->prepare("
            SELECT COUNT(*) as cnt 
            FROM exam_attempts 
            WHERE user_id = ? AND percentage >= 50.00 AND TIMESTAMPDIFF(SECOND, started_at, submitted_at) < ?
        ");
        $checkPerfectStmt = $conn->prepare("
            SELECT COUNT(*) as cnt 
            FROM exam_attempts 
            WHERE user_id = ? AND percentage >= 100.00
        ");
        $checkAttemptsStmt = $conn->prepare("
            SELECT COUNT(*) as cnt 
            FROM exam_attempts 
            WHERE user_id = ?
        ");
        
        $unlockStmt = $conn->prepare("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?, ?)");

        while ($badge = $badgeRes->fetch_assoc()) {
            $badgeId = (int)$badge['id'];
            $condType = $badge['condition_type'];
            $condVal = $badge['condition_value'];
            $earned = false;

            if ($condType === 'subject_pass') {
                $checkPassStmt->bind_param("is", $loggedInUserId, $condVal);
                $checkPassStmt->execute();
                $earned = ($checkPassStmt->get_result()->fetch_assoc()['cnt'] > 0);
            } elseif ($condType === 'speed_run') {
                $seconds = (int)$condVal;
                $checkSpeedStmt->bind_param("ii", $loggedInUserId, $seconds);
                $checkSpeedStmt->execute();
                $earned = ($checkSpeedStmt->get_result()->fetch_assoc()['cnt'] > 0);
            } elseif ($condType === 'perfect_score') {
                $checkPerfectStmt->bind_param("i", $loggedInUserId);
                $checkPerfectStmt->execute();
                $earned = ($checkPerfectStmt->get_result()->fetch_assoc()['cnt'] > 0);
            } elseif ($condType === 'attempts_count') {
                $targetCount = (int)$condVal;
                $checkAttemptsStmt->bind_param("i", $loggedInUserId);
                $checkAttemptsStmt->execute();
                $earned = ($checkAttemptsStmt->get_result()->fetch_assoc()['cnt'] >= $targetCount);
            }

            if ($earned && $unlockStmt) {
                $unlockStmt->bind_param("ii", $loggedInUserId, $badgeId);
                $unlockStmt->execute();
            }
        }
    }
}

// 2. Fetch Earned Badges
$unlockedBadges = [];
$unlockedStmt = $conn->prepare("
    SELECT b.*, ub.unlocked_at 
    FROM user_badges ub
    JOIN badges b ON b.id = ub.badge_id
    WHERE ub.user_id = ?
    ORDER BY ub.unlocked_at DESC
");
$unlockedStmt->bind_param("i", $viewUserId);
$unlockedStmt->execute();
$unRes = $unlockedStmt->get_result();
while ($row = $unRes->fetch_assoc()) {
    $unlockedBadges[$row['id']] = $row;
}

// 3. Fetch Locked Badges
$allBadges = [];
$badgeRes = $conn->query("SELECT * FROM badges ORDER BY id ASC");
if ($badgeRes) {
    while ($row = $badgeRes->fetch_assoc()) {
        $allBadges[] = $row;
    }
}

$fullName = trim($userInfo['f_name'] . ' ' . $userInfo['l_name']);
if (empty($fullName)) $fullName = $userInfo['u_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skills Passport & Achievements | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        .passport-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            border-radius: var(--radius);
            padding: 3rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
            margin-bottom: 2rem;
            animation: fadeIn 0.5s ease-out;
        }
        .passport-avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            font-size: 3rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.25);
            border: 4px solid white;
        }
        .badge-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .badge-item {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 2px solid var(--gray-100);
            text-align: center;
            position: relative;
            transition: all 0.3s ease;
        }
        .badge-item.unlocked {
            border-color: #fbbf24;
            background: linear-gradient(180deg, rgba(254, 243, 199, 0.1) 0%, #ffffff 100%);
        }
        .badge-item.locked {
            opacity: 0.55;
            background: #f9fafb;
            cursor: not-allowed;
        }
        .badge-icon {
            font-size: 2.75rem;
            margin-bottom: 0.75rem;
            display: block;
        }
        .badge-title {
            font-weight: 700;
            font-size: 1rem;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }
        .badge-desc {
            font-size: 0.78rem;
            color: var(--gray-600);
            line-height: 1.4;
        }
        .unlock-date {
            font-size: 0.7rem;
            color: #d97706;
            font-weight: 600;
            margin-top: 0.75rem;
            display: block;
        }
        .share-banner {
            background: rgba(99, 102, 241, 0.08);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            text-align: left;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include("modern_header.php"); ?>

    <div class="container">
        <!-- Passport User Card -->
        <div class="passport-card">
            <div class="passport-avatar">🎓</div>
            
            <h1 style="margin-bottom: 0.25rem; font-size: 2.2rem; color: var(--gray-900);"><?php echo e($fullName); ?></h1>
            <p class="text-muted" style="font-size: 1.05rem;">Verified Professional Skills Passport</p>
            
            <div style="margin-top: 1rem; display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(99, 102, 241, 0.1); padding: 0.4rem 1rem; border-radius: 20px; color: var(--primary); font-size: 0.85rem; font-weight: 600;">
                🏅 <?php echo count($unlockedBadges); ?> out of <?php echo count($allBadges); ?> Achievements Unlocked
            </div>
            
            <?php if ($isOwner): ?>
                <div class="share-banner">
                    <div>
                        <strong style="color: var(--primary); font-size: 0.9rem;">🔗 Share Your Skills Passport</strong>
                        <p style="font-size: 0.8rem; color: var(--gray-600); margin: 0.25rem 0 0 0;">Allow employers or peers to publicly verify all your earned achievements.</p>
                    </div>
                    <button class="btn btn-inline" onclick="copyPassportLink()" style="margin:0; width:auto; font-size: 0.85rem;">Copy Passport Link</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Badges Directory Title -->
        <div style="margin: 3rem 0 1.5rem 0; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="color: var(--white); margin: 0;">Achievements Directory</h2>
            <span style="font-size: 0.85rem; color: rgba(255,255,255,0.75);">Gamified Skill Credentials</span>
        </div>

        <!-- Badges Grid -->
        <div class="badge-grid">
            <?php foreach ($allBadges as $badge): 
                $bid = $badge['id'];
                $isUnlocked = isset($unlockedBadges[$bid]);
                $class = $isUnlocked ? 'unlocked' : 'locked';
            ?>
                <div class="badge-item <?php echo $class; ?>">
                    <span class="badge-icon"><?php echo $badge['icon']; ?></span>
                    <div class="badge-title"><?php echo e($badge['name']); ?></div>
                    <div class="badge-desc"><?php echo e($badge['description']); ?></div>
                    <?php if ($isUnlocked): ?>
                        <span class="unlock-date">✓ Earned <?php echo date("M j, Y", strtotime($unlockedBadges[$bid]['unlocked_at'])); ?></span>
                    <?php else: ?>
                        <span class="unlock-date" style="color: var(--gray-600);">🔒 Locked</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function copyPassportLink() {
            const link = window.location.origin + window.location.pathname + "?uid=<?php echo $viewUserId; ?>";
            navigator.clipboard.writeText(link).then(() => {
                alert("Skills Passport Share Link copied to clipboard!");
            });
        }
    </script>
</body>
</html>
