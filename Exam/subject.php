<?php
session_start();
include 'config.php';
if(!isset($_SESSION['u_name'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
</head>
<body>
    <?php include("modern_header.php"); ?>
    
    <div class="container">
        <div style="margin-bottom: 3rem;">
            <h1>Subject Selection Dashboard</h1>
            <p class="text-muted">Choose a subject below to begin your examination. Each test is timed and monitored.</p>
        </div>

        <div class="subject-grid">
            <?php
            // Ensure canonical subjects table has baseline data
            $check = $conn->query("SELECT * FROM subjects");
            if($check->num_rows == 0) {
                $conn->query("INSERT INTO subjects (name) VALUES ('Java Programming Language'), ('PHP Programming Language'), ('Python Programming Language'), ('C Language'), ('Data Structure')");
                $check = $conn->query("SELECT * FROM subjects");
            }

            $icons = [
                'Java' => '☕',
                'Python' => '🐍',
                'C Language' => '💻',
                'PHP' => '🐘',
                'Data Structure' => '📊',
                'Default' => '📚'
            ];

            $userId = (int)($_SESSION['user_id'] ?? 0);
            $bestAttemptStmt = $conn->prepare("
                SELECT percentage, level, exam_mode 
                FROM exam_attempts 
                WHERE user_id = ? AND subject_id = ? 
                ORDER BY percentage DESC 
                LIMIT 1
            ");

            $passedAttemptsStmt = $conn->prepare("
                SELECT level 
                FROM exam_attempts 
                WHERE user_id = ? AND subject_id = ? AND percentage >= 50.00
            ");

            $tier_vals = ['Easy' => 1, 'Medium' => 2, 'Hard' => 3, 'Advanced' => 4, 'Expert' => 5];
            $tier_colors = [
                0 => 'var(--gray-300)',
                20 => '#ef4444', // Red
                40 => '#f97316', // Orange
                60 => '#eab308', // Gold
                80 => '#3b82f6', // Blue
                100 => '#22c55e' // Green
            ];

            while($row = $check->fetch_assoc()) {
                $subject_name = $row['name'];
                $sub_id = $row['id'];
                
                // 1. Cohort and Schedule Enforcement for Student Dashboard
                $userRole = $_SESSION['role'] ?? 'student';
                $isAllowed = true;
                $lockReason = '';
                $scheduleInfo = '';
                
                if ($userRole === 'student') {
                    // Check if subject is bound to ANY cohorts
                    $cohortBoundStmt = $conn->prepare("SELECT sc.*, c.name as cohort_name FROM subject_cohorts sc JOIN cohorts c ON c.id = sc.cohort_id WHERE sc.subject_id = ?");
                    $cohortBoundStmt->bind_param("i", $sub_id);
                    $cohortBoundStmt->execute();
                    $bounds = $cohortBoundStmt->get_result();
                    
                    if ($bounds->num_rows > 0) {
                        // Yes, this subject has restrictions. Check if student belongs to any of these cohorts.
                        $memberStmt = $conn->prepare("
                            SELECT sc.opens_at, sc.closes_at, c.name as cohort_name
                            FROM subject_cohorts sc
                            JOIN cohort_members cm ON cm.cohort_id = sc.cohort_id
                            JOIN cohorts c ON c.id = sc.cohort_id
                            WHERE sc.subject_id = ? AND cm.user_id = ?
                        ");
                        $memberStmt->bind_param("ii", $sub_id, $userId);
                        $memberStmt->execute();
                        $memberRes = $memberStmt->get_result()->fetch_assoc();
                        
                        if (!$memberRes) {
                            // Student is not enrolled in a classroom that has access to this subject
                            $isAllowed = false;
                        } else {
                            // Student belongs to the cohort. Check dynamic open/close times.
                            $now = date('Y-m-d H:i:s');
                            $opens = $memberRes['opens_at'];
                            $closes = $memberRes['closes_at'];
                            $cname = $memberRes['cohort_name'];
                            
                            if ($opens && $now < $opens) {
                                $isAllowed = false;
                                $lockReason = "Locked: Opens " . date("M j, H:i", strtotime($opens));
                                $scheduleInfo = "🔑 Bound to cohort: $cname";
                            } elseif ($closes && $now > $closes) {
                                $isAllowed = false;
                                $lockReason = "Expired: Closed " . date("M j, H:i", strtotime($closes));
                                $scheduleInfo = "🔑 Bound to cohort: $cname";
                            } else {
                                $scheduleInfo = "🔓 Cohort Release Active ($cname)";
                            }
                        }
                    }
                }
                
                // If not allowed and no lockReason (e.g. they aren't even in the cohort), skip showing the card entirely!
                if (!$isAllowed && $lockReason === '') {
                    continue;
                }

                $icon = '📚';
                foreach($icons as $key => $val) {
                    if(strpos($subject_name, $key) !== false) {
                        $icon = $val;
                        break;
                    }
                }

                $badge_html = "<span style='font-size: 0.8rem; color: var(--gray-400);'>✨ No attempts yet</span>";
                if ($userId > 0 && $bestAttemptStmt) {
                    $bestAttemptStmt->bind_param("ii", $userId, $sub_id);
                    $bestAttemptStmt->execute();
                    $bestAttempt = $bestAttemptStmt->get_result()->fetch_assoc();
                    if ($bestAttempt) {
                        $perc = round($bestAttempt['percentage']);
                        $lvl = $bestAttempt['level'];
                        $mode = $bestAttempt['exam_mode'];
                        if ($mode === 'official' && $perc >= 50) {
                            $badge_html = "<span style='font-size: 0.8rem; padding: 0.25rem 0.6rem; border-radius: 12px; background: #fef3c7; color: #b45309; font-weight: 700;'>🏆 Certified ($lvl: $perc%)</span>";
                        } else {
                            $mode_label = ($mode === 'mock') ? 'Mock' : 'Exam';
                            $badge_html = "<span style='font-size: 0.8rem; padding: 0.25rem 0.6rem; border-radius: 12px; background: #e0f2fe; color: #0369a1; font-weight: 600;'>📈 Best: $perc% ($lvl $mode_label)</span>";
                        }
                    }
                }

                // Calculate progress bar metric based on highest level passed (percentage >= 50)
                $progress_percent = 0;
                if ($userId > 0 && $passedAttemptsStmt) {
                    $passedAttemptsStmt->bind_param("ii", $userId, $sub_id);
                    $passedAttemptsStmt->execute();
                    $passedRes = $passedAttemptsStmt->get_result();
                    $max_tier = 0;
                    while($pr = $passedRes->fetch_assoc()) {
                        $lvl = $pr['level'];
                        $tier = $tier_vals[$lvl] ?? 0;
                        if ($tier > $max_tier) {
                            $max_tier = $tier;
                        }
                    }
                    $progress_percent = $max_tier * 20;
                }
                $progress_color = $tier_colors[$progress_percent];

                if ($isAllowed) {
                    echo "
                    <a href='levels.php?subid=$sub_id&subject_id=$sub_id&subname=".urlencode($subject_name)."' class='subject-card' style='display: flex; flex-direction: column; justify-content: space-between; height: 100%; min-height: 250px; text-decoration: none;'>
                        <div>
                            <div style='display: flex; align-items: center; justify-content: space-between;'>
                                <span class='subject-icon'>$icon</span>
                                " . ($scheduleInfo !== '' ? "<span style='font-size: 0.72rem; color: #059669; font-weight: 600;'>$scheduleInfo</span>" : "") . "
                            </div>
                            <span class='subject-title' style='margin-top: 1rem; display: block;'>$subject_name</span>
                            
                            <!-- Linear Progress Bar -->
                            <div style='margin-top: 1.25rem;'>
                                <div style='display: flex; justify-content: space-between; font-size: 0.72rem; color: var(--gray-500); margin-bottom: 0.25rem;'>
                                    <span>Subject Progress:</span>
                                    <strong>$progress_percent%</strong>
                                </div>
                                <div style='background: var(--gray-200); border-radius: 10px; height: 6px; width: 100%; overflow: hidden;'>
                                    <div style='background: $progress_color; height: 100%; width: $progress_percent%; transition: width 0.4s ease;'></div>
                                </div>
                            </div>
                        </div>
                        
                        <div style='margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--gray-100); padding-top: 0.8rem;'>
                            $badge_html
                            <span style='font-size: 0.85rem; font-weight: 600; color: var(--primary);'>Start &rarr;</span>
                        </div>
                    </a>";
                } else {
                    // Styled locked / expired subject card
                    echo "
                    <div class='subject-card' style='display: flex; flex-direction: column; justify-content: space-between; height: 100%; min-height: 250px; opacity: 0.65; cursor: not-allowed; background: #f3f4f6;'>
                        <div>
                            <div style='display: flex; align-items: center; justify-content: space-between;'>
                                <span class='subject-icon'>🔒</span>
                                <span style='font-size: 0.72rem; color: #dc2626; font-weight: 700;'>$lockReason</span>
                            </div>
                            <span class='subject-title' style='margin-top: 1rem; display: block; color: var(--gray-600);'>$subject_name</span>
                            <div style='font-size: 0.72rem; color: var(--gray-500); margin-top: 0.5rem;'>$scheduleInfo</div>
                        </div>
                        <div style='margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--gray-200); padding-top: 0.8rem;'>
                            <span style='font-size: 0.8rem; color: var(--gray-400);'>🔒 Content Scheduled</span>
                            <span style='font-size: 0.85rem; font-weight: 600; color: var(--gray-400);'>Locked</span>
                        </div>
                    </div>";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>