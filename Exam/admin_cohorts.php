<?php
require_once 'lib/security.php';
require_login();

// Allow admin and teacher roles
if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header('Location: subject.php');
    exit;
}

require 'config.php';

$success_msg = '';
$error_msg = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error_msg = "Security validation failed. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_cohort') {
            $name = trim($_POST['cohort_name'] ?? '');
            if (empty($name)) {
                $error_msg = "Cohort name is required.";
            } else {
                $stmt = $conn->prepare("INSERT INTO cohorts (name) VALUES (?)");
                $stmt->bind_param("s", $name);
                if ($stmt->execute()) {
                    $success_msg = "Cohort '{$name}' created successfully.";
                } else {
                    $error_msg = "Error creating cohort. Name might already exist.";
                }
            }
        } elseif ($action === 'add_student') {
            $cohort_id = (int)($_POST['cohort_id'] ?? 0);
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($cohort_id <= 0 || $user_id <= 0) {
                $error_msg = "Invalid student or cohort selection.";
            } else {
                $stmt = $conn->prepare("INSERT INTO cohort_members (cohort_id, user_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $cohort_id, $user_id);
                if ($stmt->execute()) {
                    $success_msg = "Student enrolled in cohort successfully.";
                } else {
                    $error_msg = "Student is already enrolled in this cohort.";
                }
            }
        } elseif ($action === 'remove_student') {
            $cohort_id = (int)($_POST['cohort_id'] ?? 0);
            $user_id = (int)($_POST['user_id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM cohort_members WHERE cohort_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $cohort_id, $user_id);
            if ($stmt->execute()) {
                $success_msg = "Student removed from cohort.";
            } else {
                $error_msg = "Database error: Failed to remove student.";
            }
        } elseif ($action === 'schedule_release') {
            $cohort_id = (int)($_POST['cohort_id'] ?? 0);
            $subject_id = (int)($_POST['subject_id'] ?? 0);
            $opens_at = !empty($_POST['opens_at']) ? $_POST['opens_at'] : null;
            $closes_at = !empty($_POST['closes_at']) ? $_POST['closes_at'] : null;

            if ($cohort_id <= 0 || $subject_id <= 0) {
                $error_msg = "Invalid cohort or subject selection.";
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO subject_cohorts (subject_id, cohort_id, opens_at, closes_at) 
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE opens_at = VALUES(opens_at), closes_at = VALUES(closes_at)
                ");
                $stmt->bind_param("iiss", $subject_id, $cohort_id, $opens_at, $closes_at);
                if ($stmt->execute()) {
                    $success_msg = "Scheduled release window updated successfully.";
                } else {
                    $error_msg = "Database error: Failed to schedule release.";
                }
            }
        } elseif ($action === 'remove_schedule') {
            $schedule_id = (int)($_POST['schedule_id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM subject_cohorts WHERE id = ?");
            $stmt->bind_param("i", $schedule_id);
            if ($stmt->execute()) {
                $success_msg = "Scheduled window deleted. Subject is now unrestricted.";
            } else {
                $error_msg = "Database error: Failed to delete schedule.";
            }
        }
    }
}

// Fetch all cohorts
$cohorts = [];
$res = $conn->query("SELECT id, name, created_at FROM cohorts ORDER BY name ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $cohorts[] = $r;
    }
}

// Fetch all student users
$students = [];
$res = $conn->query("SELECT id, f_name, l_name, u_name FROM users WHERE role = 'student' ORDER BY f_name ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $students[] = $r;
    }
}

// Fetch all subjects
$subjects = [];
$res = $conn->query("SELECT id, name FROM subjects ORDER BY name ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $subjects[] = $r;
    }
}

// Fetch active schedules
$schedules = [];
$res = $conn->query("
    SELECT sc.id, sc.opens_at, sc.closes_at, s.name as subject_name, c.name as cohort_name
    FROM subject_cohorts sc
    JOIN subjects s ON s.id = sc.subject_id
    JOIN cohorts c ON c.id = sc.cohort_id
    ORDER BY sc.opens_at ASC
");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $schedules[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cohort & Classroom Management | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        .split-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        @media (min-width: 992px) {
            .split-grid {
                grid-template-columns: 3fr 2fr;
            }
        }
        .cohort-accordion {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .cohort-box {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }
        .cohort-header {
            padding: 1.25rem 1.5rem;
            background: var(--gray-50);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--gray-200);
            font-weight: 600;
        }
        .cohort-body {
            padding: 1.5rem;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
        }
        .pill-delete {
            background: none;
            border: none;
            color: var(--accent);
            cursor: pointer;
            font-weight: bold;
            font-size: 0.85rem;
            padding: 0 2px;
        }
    </style>
</head>
<body>
    <?php include("modern_header.php"); ?>

    <div class="container">
        <!-- Page Header -->
        <div style="margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="color: var(--white); margin-bottom: 0.25rem;">Cohorts & Classroom Directory</h1>
                <p style="color: rgba(255,255,255,0.85); font-size: 0.95rem;">Group students, assign Multi-Tenant classes, and schedule exam release windows.</p>
            </div>
            <a href="admin_dashboard.php" class="btn btn-inline" style="background: rgba(255,255,255,0.2); color: var(--white); border: 1px solid rgba(255,255,255,0.4); font-size: 0.9rem;">
                &larr; Admin Portal
            </a>
        </div>

        <!-- Success/Error Feedback Alerts -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">
                <span style="font-size: 1.25rem;">✅</span>
                <div><strong>Success!</strong> <?php echo e($success_msg); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error">
                <span style="font-size: 1.25rem;">⚠️</span>
                <div><strong>Failed!</strong> <?php echo e($error_msg); ?></div>
            </div>
        <?php endif; ?>

        <!-- Split Grid Workspace -->
        <div class="split-grid">
            <!-- Left Panel: Cohort Accordion and Management -->
            <div>
                <div class="admin-card" style="margin-bottom: 2rem;">
                    <div class="admin-card-header">
                        <h2>Active Cohorts & Enrolled Students</h2>
                    </div>
                    
                    <div class="cohort-accordion">
                        <?php if (empty($cohorts)): ?>
                            <div class="text-center text-muted" style="padding: 3rem;">No cohorts defined yet. Use the panel on the right to create one.</div>
                        <?php else: ?>
                            <?php foreach ($cohorts as $cohort): 
                                $cid = (int)$cohort['id'];
                                // Fetch members
                                $members = [];
                                $m_res = $conn->query("
                                    SELECT u.id, u.f_name, u.l_name, u.u_name 
                                    FROM cohort_members cm
                                    JOIN users u ON u.id = cm.user_id
                                    WHERE cm.cohort_id = $cid
                                    ORDER BY u.f_name ASC
                                ");
                                if ($m_res) {
                                    while ($mr = $m_res->fetch_assoc()) {
                                        $members[] = $mr;
                                    }
                                }
                            ?>
                                <div class="cohort-box">
                                    <div class="cohort-header">
                                        <span>📂 <?php echo e($cohort['name']); ?></span>
                                        <span class="badge badge-student" style="background: #dbeafe; color: #1e40af;">
                                            <?php echo count($members); ?> Enrolled
                                        </span>
                                    </div>
                                    <div class="cohort-body">
                                        <h4 style="font-size: 0.82rem; text-transform: uppercase; color: var(--gray-600); margin-bottom: 1rem; letter-spacing: 0.5px;">Student Roster</h4>
                                        <?php if (empty($members)): ?>
                                            <p class="text-muted" style="font-style: italic;">No students assigned to this classroom yet.</p>
                                        <?php else: ?>
                                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                                <?php foreach ($members as $m): 
                                                    $fname = trim($m['f_name'] . ' ' . $m['l_name']);
                                                    if (empty($fname)) $fname = $m['u_name'];
                                                ?>
                                                    <span class="pill">
                                                        👤 <?php echo e($fname); ?>
                                                        <form action="" method="post" style="display:inline; margin:0;" onsubmit="return confirm('Remove student from cohort?');">
                                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                                            <input type="hidden" name="action" value="remove_student">
                                                            <input type="hidden" name="cohort_id" value="<?php echo $cid; ?>">
                                                            <input type="hidden" name="user_id" value="<?php echo $m['id']; ?>">
                                                            <button type="submit" class="pill-delete">&times;</button>
                                                        </form>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Quick Add Student to Cohort -->
                                        <form action="" method="post" style="margin-top: 1.5rem; display: flex; gap: 0.5rem; align-items: center; border-top: 1px solid var(--gray-100); padding-top: 1rem;">
                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                            <input type="hidden" name="action" value="add_student">
                                            <input type="hidden" name="cohort_id" value="<?php echo $cid; ?>">
                                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--gray-700); white-space: nowrap;">Add Student:</span>
                                            <select name="user_id" class="role-select" style="flex-grow:1;" required>
                                                <option value="">-- Choose student to enroll --</option>
                                                <?php foreach ($students as $student): 
                                                    $sname = trim($student['f_name'] . ' ' . $student['l_name']);
                                                    if (empty($sname)) $sname = $student['u_name'];
                                                ?>
                                                    <option value="<?php echo $student['id']; ?>"><?php echo e($sname); ?> (<?php echo e($student['u_name']); ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-inline" style="padding: 0.4rem 1rem; width: auto; font-size: 0.82rem;">Enroll</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Create Cohort and Scheduling Release Times -->
            <div>
                <!-- Create Cohort Card -->
                <div class="admin-card" style="margin-bottom: 2rem;">
                    <div class="admin-card-header">
                        <h2>Create New Cohort</h2>
                    </div>
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="action" value="create_cohort">
                        <div class="form-group">
                            <label class="form-label">Cohort/Classroom Name</label>
                            <input type="text" name="cohort_name" class="form-control" placeholder="e.g. B.Tech CS - Section C (2026)" required>
                        </div>
                        <button type="submit" class="btn">Create Cohort</button>
                    </form>
                </div>

                <!-- Schedule Release Card -->
                <div class="admin-card" style="margin-bottom: 2rem;">
                    <div class="admin-card-header">
                        <h2>Schedule Subject Releases</h2>
                    </div>
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="action" value="schedule_release">
                        <div class="form-group">
                            <label class="form-label">Target Cohort</label>
                            <select name="cohort_id" class="form-control" required>
                                <option value="">-- Select Cohort --</option>
                                <?php foreach ($cohorts as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo e($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-control" required>
                                <option value="">-- Select Subject --</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo e($s['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Opens At (Dynamic Release Window)</label>
                            <input type="datetime-local" name="opens_at" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Closes At (Dynamic Release Window)</label>
                            <input type="datetime-local" name="closes_at" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-secondary">Apply Schedule Rule</button>
                    </form>
                </div>

                <!-- Active Release Rules Card -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Active Cohort Schedules</h2>
                    </div>
                    <div class="admin-subject-list" style="max-height: 300px; overflow-y: auto;">
                        <?php if (empty($schedules)): ?>
                            <p class="text-muted" style="font-style: italic;">No active constraints. All subjects are open to everyone by default.</p>
                        <?php else: ?>
                            <?php foreach ($schedules as $s): ?>
                                <div class="admin-subject-item" style="flex-direction: column; align-items: flex-start; gap: 0.5rem; padding: 1rem;">
                                    <div style="display: flex; justify-content: space-between; width: 100%; align-items: center;">
                                        <strong style="color: var(--primary);"><?php echo e($s['subject_name']); ?></strong>
                                        <form action="" method="post" style="margin:0;" onsubmit="return confirm('Remove schedule window for this cohort?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                            <input type="hidden" name="action" value="remove_schedule">
                                            <input type="hidden" name="schedule_id" value="<?php echo $s['id']; ?>">
                                            <button type="submit" class="btn-sm-danger">Delete</button>
                                        </form>
                                    </div>
                                    <span style="font-size: 0.8rem; color: var(--gray-600);">Classroom: <strong><?php echo e($s['cohort_name']); ?></strong></span>
                                    <div style="font-size: 0.75rem; color: var(--gray-600); margin-top: 0.25rem;">
                                        🕒 Opens: <?php echo $s['opens_at'] ? date("Y-m-d H:i", strtotime($s['opens_at'])) : 'Immediate'; ?><br>
                                        🕒 Closes: <?php echo $s['closes_at'] ? date("Y-m-d H:i", strtotime($s['closes_at'])) : 'Never'; ?>
                                    </div>
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
