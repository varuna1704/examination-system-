<?php
require_once 'lib/security.php';
require_admin(); // Restricts to logged-in admins only
require 'config.php';

$success_msg = '';
$error_msg = '';

// Handle POST actions securely
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error_msg = "Security validation failed. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'change_role') {
            $target_user_id = (int)($_POST['target_user_id'] ?? 0);
            $new_role = trim($_POST['new_role'] ?? '');

            // Allowed roles
            $allowed_roles = ['student', 'teacher', 'admin'];

            if (!in_array($new_role, $allowed_roles)) {
                $error_msg = "Invalid role specified.";
            } elseif ($target_user_id === (int)$_SESSION['user_id']) {
                $error_msg = "Security constraint: You cannot modify your own administrative role.";
            } else {
                // Fetch target user's details for logging
                $user_stmt = $conn->prepare("SELECT u_name FROM users WHERE id = ?");
                $user_stmt->bind_param("i", $target_user_id);
                $user_stmt->execute();
                $user_res = $user_stmt->get_result()->fetch_assoc();
                $target_username = $user_res['u_name'] ?? 'Unknown User';

                $update_stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                $update_stmt->bind_param("si", $new_role, $target_user_id);
                if ($update_stmt->execute()) {
                    $success_msg = "Successfully updated permission role of user '{$target_username}' to '" . ucfirst($new_role) . "'.";
                } else {
                    $error_msg = "Database error: Failed to update user role.";
                }
            }
        } elseif ($action === 'add_subject') {
            $subject_name = trim($_POST['subject_name'] ?? '');

            if (empty($subject_name)) {
                $error_msg = "Subject name cannot be empty.";
            } else {
                // Check if subject already exists
                $check_stmt = $conn->prepare("SELECT id FROM subjects WHERE LOWER(name) = LOWER(?) LIMIT 1");
                $check_stmt->bind_param("s", $subject_name);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error_msg = "Subject '{$subject_name}' already exists in catalog.";
                } else {
                    $insert_stmt = $conn->prepare("INSERT INTO subjects (name) VALUES (?)");
                    $insert_stmt->bind_param("s", $subject_name);
                    if ($insert_stmt->execute()) {
                        $success_msg = "Subject '{$subject_name}' has been successfully added to catalog.";
                    } else {
                        $error_msg = "Database error: Failed to add subject.";
                    }
                }
            }
        } elseif ($action === 'delete_subject') {
            $subject_id = (int)($_POST['subject_id'] ?? 0);

            // Fetch subject name for confirmation notice
            $sub_stmt = $conn->prepare("SELECT name FROM subjects WHERE id = ?");
            $sub_stmt->bind_param("i", $subject_id);
            $sub_stmt->execute();
            $sub_res = $sub_stmt->get_result()->fetch_assoc();
            $subject_name = $sub_res['name'] ?? 'Unknown Subject';

            if ($subject_id <= 0) {
                $error_msg = "Invalid subject ID.";
            } else {
                $delete_stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
                $delete_stmt->bind_param("i", $subject_id);
                if ($delete_stmt->execute()) {
                    $success_msg = "Subject '{$subject_name}' and all associated test questions & records have been deleted.";
                } else {
                    $error_msg = "Database error: Failed to delete subject.";
                }
            }
        }
    }
}

// Fetch Metrics Counts
$students_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'")->fetch_assoc()['count'];
$subjects_count = $conn->query("SELECT COUNT(*) as count FROM subjects")->fetch_assoc()['count'];
$questions_count = $conn->query("SELECT COUNT(*) as count FROM questions")->fetch_assoc()['count'];
$attempts_count = $conn->query("SELECT COUNT(*) as count FROM exam_attempts")->fetch_assoc()['count'];

// Fetch Users List
$users_query = $conn->query("SELECT id, f_name, m_name, l_name, u_name, u_email, role, created_at FROM users ORDER BY role ASC, created_at DESC");
$users = [];
if ($users_query) {
    while ($row = $users_query->fetch_assoc()) {
        $users[] = $row;
    }
}

// Fetch Subjects Catalog List
$subjects_query = $conn->query("SELECT id, name FROM subjects ORDER BY name ASC");
$subjects = [];
if ($subjects_query) {
    while ($row = $subjects_query->fetch_assoc()) {
        $subjects[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        /* Specific layout styles for the Admin Dashboard */
        .admin-layout {
            animation: fadeIn 0.4s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include("modern_header.php"); ?>

    <div class="container admin-layout">
        <!-- Page Header -->
        <div style="margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="color: var(--white); margin-bottom: 0.25rem;">Administrative Command Center</h1>
                <p style="color: rgba(255,255,255,0.85); font-size: 0.95rem;">Manage user roles, platform permissions, and subject catalogs.</p>
                <div style="margin-top: 0.75rem; display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(255, 255, 255, 0.15); padding: 0.4rem 0.8rem; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.25); color: var(--white); font-size: 0.82rem; font-weight: 500;">
                    <span style="color: #4ade80;">●</span> Active Session: <strong><?php echo e($_SESSION['u_name']); ?></strong> (System Administrator)
                </div>
            </div>
            <a href="subject.php" class="btn btn-inline" style="background: rgba(255,255,255,0.2); color: var(--white); border: 1px solid rgba(255,255,255,0.4); font-size: 0.9rem;">
                &larr; Back to Dashboard
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

        <!-- Platform Analytics Metrics Grid -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-info">
                    <h3>Registered Students</h3>
                    <div class="metric-value"><?php echo number_format($students_count); ?></div>
                </div>
                <div class="metric-icon">🎓</div>
            </div>

            <div class="metric-card success">
                <div class="metric-info">
                    <h3>Active Subjects</h3>
                    <div class="metric-value"><?php echo number_format($subjects_count); ?></div>
                </div>
                <div class="metric-icon">📚</div>
            </div>

            <div class="metric-card secondary">
                <div class="metric-info">
                    <h3>Questions Pool</h3>
                    <div class="metric-value"><?php echo number_format($questions_count); ?></div>
                </div>
                <div class="metric-icon">❓</div>
            </div>

            <div class="metric-card accent">
                <div class="metric-info">
                    <h3>Exams Attempted</h3>
                    <div class="metric-value"><?php echo number_format($attempts_count); ?></div>
                </div>
                <div class="metric-icon">⚡</div>
            </div>
        </div>

        <!-- Main Workspace Split Grid -->
        <div class="admin-row">
            <!-- Left Panel: User & Permission Management Directory -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>User Directory & Permissions</h2>
                    <span style="font-size: 0.85rem; color: var(--gray-600); font-weight: 500;">
                        Total Users: <strong><?php echo count($users); ?></strong>
                    </span>
                </div>

                <!-- Instant client-side search box -->
                <div class="search-container">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="userSearch" class="search-input" placeholder="Search by name, email, username or role..." onkeyup="filterUsers()">
                </div>

                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Email Address</th>
                                <th>Joined</th>
                                <th>Current Role</th>
                                <th style="text-align: center; width: 140px;">Modify Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted" style="padding: 2rem;">No registered users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): 
                                    $fullName = trim($user['f_name'] . ' ' . $user['m_name'] . ' ' . $user['l_name']);
                                    if (empty($fullName)) $fullName = 'N/A';
                                    
                                    $role = $user['role'];
                                    $badgeClass = 'badge-student';
                                    if ($role === 'admin') $badgeClass = 'badge-admin';
                                    elseif ($role === 'teacher') $badgeClass = 'badge-teacher';
                                    
                                    $isSelf = ((int)$user['id'] === (int)$_SESSION['user_id']);
                                ?>
                                    <tr class="user-row">
                                        <td style="font-weight: 600; color: var(--gray-600);"><?php echo $user['id']; ?></td>
                                        <td style="font-weight: 600;"><?php echo e($fullName); ?></td>
                                        <td style="font-family: monospace; color: var(--primary);"><?php echo e($user['u_name']); ?></td>
                                        <td style="font-size: 0.85rem;"><?php echo e($user['u_email']); ?></td>
                                        <td style="font-size: 0.8rem; color: var(--gray-600);"><?php echo date("Y-m-d", strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo $role; ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($isSelf): ?>
                                                <span style="font-size: 0.8rem; color: var(--gray-600); font-style: italic; font-weight: 500;">Active Session (Self)</span>
                                            <?php else: ?>
                                                <form action="" method="post" style="margin: 0;" onsubmit="return confirmRoleChange('<?php echo e($user['u_name']); ?>', this.new_role.value)">
                                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                                    <input type="hidden" name="action" value="change_role">
                                                    <input type="hidden" name="target_user_id" value="<?php echo $user['id']; ?>">
                                                    <select name="new_role" class="role-select" onchange="confirmAndSubmit(this)">
                                                        <option value="student" <?php if ($role === 'student') echo 'selected'; ?>>Student</option>
                                                        <option value="teacher" <?php if ($role === 'teacher') echo 'selected'; ?>>Teacher</option>
                                                        <option value="admin" <?php if ($role === 'admin') echo 'selected'; ?>>Admin</option>
                                                    </select>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Panel: Subject Manager Catalog -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>Subject Manager</h2>
                </div>

                <!-- Add Subject Inline Form -->
                <form action="" method="post" style="margin-bottom: 2rem;">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="action" value="add_subject">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.85rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Add New Subject</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" name="subject_name" class="form-control" placeholder="e.g. React JS Framework" required style="padding: 0.65rem 1rem;">
                            <button type="submit" class="btn btn-inline" style="padding: 0.65rem 1.5rem; width: auto; font-size: 0.9rem; flex-shrink: 0;">Add</button>
                        </div>
                    </div>
                </form>

                <!-- Scrollable Subject Catalog Directory -->
                <h3 style="font-size: 0.85rem; text-transform: uppercase; color: var(--gray-600); letter-spacing: 0.5px; margin-bottom: 0.75rem;">Subject Catalog Directory</h3>
                <div class="admin-subject-list" style="max-height: 480px; overflow-y: auto; padding-right: 5px;">
                    <?php if (empty($subjects)): ?>
                        <div class="text-center text-muted" style="padding: 2rem; background: var(--gray-50); border-radius: 8px;">No active subjects registered.</div>
                    <?php else: ?>
                        <?php foreach ($subjects as $sub): ?>
                            <div class="admin-subject-item">
                                <span class="admin-subject-name"><?php echo e($sub['name']); ?></span>
                                <form action="" method="post" style="margin: 0;" onsubmit="return confirm('WARNING: Deleting this subject will permanently wipe all associated test questions and candidate history results. Are you sure you want to delete this subject?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="action" value="delete_subject">
                                    <input type="hidden" name="subject_id" value="<?php echo $sub['id']; ?>">
                                    <button type="submit" class="btn-sm-danger">Delete</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Client-side Interactive Scripts -->
    <script>
        // Filters rows in the user table in real time
        function filterUsers() {
            const query = document.getElementById('userSearch').value.toLowerCase();
            const rows = document.querySelectorAll('.user-row');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Small JS confirmation helper to handle role selector changes cleanly
        function confirmRoleChange(username, role) {
            return confirm(`Are you sure you want to change the permission role of user "${username}" to "${role.toUpperCase()}"?`);
        }

        // Programmatically trigger form submission on change with confirm validation
        function confirmAndSubmit(selectElement) {
            const form = selectElement.form;
            const targetUsername = selectElement.closest('tr').querySelector('td:nth-child(3)').innerText;
            const chosenRole = selectElement.value;

            if (confirm(`Are you sure you want to change the permission role of user "${targetUsername}" to "${chosenRole.toUpperCase()}"?`)) {
                form.submit();
            } else {
                // Revert to original selected option
                selectElement.value = selectElement.querySelector('option[selected]').value;
            }
        }
    </script>
</body>
</html>
