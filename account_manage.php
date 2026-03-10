<?php
session_start();
require_once 'sratool/check_moderator.php';
require_once "config.php";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function getUserPermissions($conn, $user_id) {
    $perms = [];
    $res = $conn->query("SELECT system_key FROM user_permissions WHERE user_id = $user_id");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $perms[] = $r['system_key'];
        }
    }
    return $perms;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {

        if ($_POST['action'] === 'add') {
            $new_username = $conn->real_escape_string($_POST['username']);
            $new_password = $conn->real_escape_string($_POST['password']);
            $full_name    = $conn->real_escape_string($_POST['full_name']);
            $email        = $conn->real_escape_string($_POST['email']);
            $role         = $conn->real_escape_string($_POST['role']);

            $check = $conn->query("SELECT id FROM users WHERE username = '$new_username'");
            if ($check->num_rows > 0) {
                $_SESSION['error_message'] = "Username already exists!";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username, password, full_name, email, role, created_at)
                        VALUES ('$new_username', '$hashed_password', '$full_name', '$email', '$role', NOW())";
                if ($conn->query($sql)) {
                    $new_user_id = $conn->insert_id;
                    $selected = isset($_POST['systems']) ? $_POST['systems'] : [];
                    foreach ($selected as $sys) {
                        $sys = $conn->real_escape_string($sys);
                        $conn->query("INSERT IGNORE INTO user_permissions (user_id, system_key) VALUES ($new_user_id, '$sys')");
                    }
                    $_SESSION['success_message'] = "User account created successfully!";
                } else {
                    $_SESSION['error_message'] = "Error creating account: " . $conn->error;
                }
            }

        } elseif ($_POST['action'] === 'edit') {
            $id           = (int)$_POST['id'];
            $new_username = $conn->real_escape_string($_POST['username']);
            $full_name    = $conn->real_escape_string($_POST['full_name']);
            $email        = $conn->real_escape_string($_POST['email']);
            $role         = $conn->real_escape_string($_POST['role']);

            $check = $conn->query("SELECT id FROM users WHERE username = '$new_username' AND id != $id");
            if ($check->num_rows > 0) {
                $_SESSION['error_message'] = "Username already exists!";
            } else {
                $sql = "UPDATE users SET username='$new_username', full_name='$full_name',
                        email='$email', role='$role', updated_at=NOW() WHERE id=$id";
                if ($conn->query($sql)) {
                    $conn->query("DELETE FROM user_permissions WHERE user_id = $id");
                    $selected = isset($_POST['systems']) ? $_POST['systems'] : [];
                    foreach ($selected as $sys) {
                        $sys = $conn->real_escape_string($sys);
                        $conn->query("INSERT IGNORE INTO user_permissions (user_id, system_key) VALUES ($id, '$sys')");
                    }
                    $_SESSION['success_message'] = "User account updated successfully!";
                } else {
                    $_SESSION['error_message'] = "Error updating account: " . $conn->error;
                }
            }

        } elseif ($_POST['action'] === 'change_password') {
            $id              = (int)$_POST['id'];
            $new_password    = $conn->real_escape_string($_POST['new_password']);
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password='$hashed_password', updated_at=NOW() WHERE id=$id";
            if ($conn->query($sql)) {
                $_SESSION['success_message'] = "Password changed successfully!";
            } else {
                $_SESSION['error_message'] = "Error changing password: " . $conn->error;
            }

        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            if ($id == $_SESSION['user_id']) {
                $_SESSION['error_message'] = "You cannot delete your own account!";
            } else {
                $conn->query("DELETE FROM user_permissions WHERE user_id = $id");
                if ($conn->query("DELETE FROM users WHERE id=$id")) {
                    $_SESSION['success_message'] = "User account deleted successfully!";
                } else {
                    $_SESSION['error_message'] = "Error deleting account: " . $conn->error;
                }
            }
        }

        header("Location: account_manage");
        exit();
    }
}

$users_result = $conn->query("SELECT * FROM users ORDER BY role, username ASC");
$users_data   = [];
while ($row = $users_result->fetch_assoc()) {
    $row['permissions'] = getUserPermissions($conn, $row['id']);
    $users_data[] = $row;
}

$total_users   = count($users_data);
$admins        = count(array_filter($users_data, fn($u) => $u['role'] === 'admin'));
$moderators    = count(array_filter($users_data, fn($u) => $u['role'] === 'moderator'));
$regular_users = count(array_filter($users_data, fn($u) => $u['role'] === 'user'));

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management</title>
    <link rel="icon" type="image/png" sizes="32x32" href="sratool/img/favicon-32x32.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sratool/css/consumables.css">
    <link rel="stylesheet" href="sratool/css/dashboard.css">
    <link rel="stylesheet" href="sratool/css/portal.css">
    <link rel="stylesheet" href="sratool/css/responsive.css">
</head>
<body>

<div class="top-header">
    <div class="logo-section">
        <img src="https://smartrye.com.ph/ams/public/backend/images/logo-sra.png" alt="Logo" class="logo-img">
        <h1 class="system-title">Account Management</h1>
    </div>
    <div class="header-right">
        <div class="current-date"><?php echo date('l, jS F Y'); ?></div>
        <div class="user-info">
            <div class="user-icon"><i class="fas fa-user"></i></div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION['role']); ?></div>
            </div>
            <div class="user-dropdown-wrap">
                <button class="user-dropdown-toggle" id="userDropdownBtn">
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="user-dropdown-menu" id="userDropdownMenu">
                    <a href="portal" class="dropdown-item">
                        <i class="fas fa-arrow-left"></i> Back to Portal
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="sratool/logout" class="dropdown-item dropdown-item-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="main-content">

    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-info">
                <h3>Total Accounts</h3>
                <div class="stat-number"><?php echo $total_users; ?></div>
            </div>
            <div class="stat-circle circle-blue" style="--percent: 100%;">
                <div class="circle-inner"><i class="fas fa-users"></i></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Admins</h3>
                <div class="stat-number"><?php echo $admins; ?></div>
            </div>
            <div class="stat-circle circle-purple" style="--percent: <?php echo $total_users > 0 ? round(($admins/$total_users)*100) : 0; ?>%;">
                <div class="circle-inner"><?php echo $total_users > 0 ? round(($admins/$total_users)*100) : 0; ?>%</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Regular Users</h3>
                <div class="stat-number"><?php echo $regular_users; ?></div>
            </div>
            <div class="stat-circle circle-green" style="--percent: <?php echo $total_users > 0 ? round(($regular_users/$total_users)*100) : 0; ?>%;">
                <div class="circle-inner"><?php echo $total_users > 0 ? round(($regular_users/$total_users)*100) : 0; ?>%</div>
            </div>
        </div>
    </div>

    <div class="action-bar">
        <h2><i class="fas fa-users-cog"></i> User Account Management</h2>
        <button class="btn-add" onclick="openAddModal()">
            <i class="fas fa-user-plus"></i> Add New User
        </button>
    </div>

    <div class="table-container">
        <?php if (count($users_data) > 0): ?>
        <table class="consumables-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>System Access</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $perm_labels = [
                    'tool_room'  => 'Tool Room',
                    'scheduling' => 'Scheduling',
                    'attendance' => 'Attendance',
                    'payroll'    => 'Payroll',
                    'employee_info' => 'Employee Info',
                    'system_logs' => 'System Logs',
                ];
                foreach ($users_data as $row):
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['full_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                    <td>
                        <?php if ($row['role'] == 'admin'): ?>
                            <span class="stock-badge stock-out">Admin</span>
                        <?php elseif ($row['role'] == 'moderator'): ?>
                            <span class="stock-badge stock-low">Moderator</span>
                        <?php else: ?>
                            <span class="stock-badge stock-good">User</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['role'] === 'moderator'): ?>
                            <span class="perm-badge perm-badge-all">
                                <i class="fas fa-check-circle"></i> All Systems
                            </span>
                        <?php elseif (empty($row['permissions'])): ?>
                            <span class="perm-badge-none">— None assigned</span>
                        <?php else: ?>
                            <div class="perm-badges">
                                <?php foreach ($row['permissions'] as $p): ?>
                                <span class="perm-badge"><?php echo $perm_labels[$p] ?? $p; ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-edit edit-btn"
                                data-id="<?php echo $row['id']; ?>"
                                data-username="<?php echo htmlspecialchars($row['username'], ENT_QUOTES); ?>"
                                data-fullname="<?php echo htmlspecialchars($row['full_name'] ?? '', ENT_QUOTES); ?>"
                                data-email="<?php echo htmlspecialchars($row['email'] ?? '', ENT_QUOTES); ?>"
                                data-role="<?php echo $row['role']; ?>"
                                data-permissions="<?php echo htmlspecialchars(json_encode($row['permissions']), ENT_QUOTES); ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn-action btn-adjust"
                                onclick="openPasswordModal(<?php echo $row['id']; ?>, '<?php echo addslashes(htmlspecialchars($row['username'])); ?>')">
                                <i class="fas fa-key"></i> Password
                            </button>
                            <?php if ($row['id'] != $_SESSION['user_id']): ?>
                            <button class="btn-action btn-delete"
                                onclick="deleteUser(<?php echo $row['id']; ?>, '<?php echo addslashes(htmlspecialchars($row['username'])); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <p>No user accounts found</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add New User</h3>
            <button class="close" onclick="closeModal()">&times;</button>
        </div>
        <form id="userForm" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id"     id="userId">

                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" id="username" required>
                </div>

                <div class="form-group" id="passwordGroup">
                    <label>Password *</label>
                    <input type="password" name="password" id="password">
                </div>

                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" id="fullName" required>
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="email" required>
                </div>

                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" id="role" required onchange="toggleSystemAccess(this.value)">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="moderator">Moderator</option>
                    </select>
                </div>

                <div class="form-group" id="systemAccessSection">
                    <label>System Access</label>

                    <div id="modNote" class="moderator-note" style="display:none;">
                        <i class="fas fa-shield-alt"></i>
                        Moderators automatically have access to <strong>all systems</strong>.
                    </div>

                    <div class="system-access-group" id="sysCheckboxes">
                        <span class="group-label">
                            <i class="fas fa-lock-open"></i> &nbsp;Select which systems this user can access
                        </span>

                        <label class="sys-check-item">
                            <input type="checkbox" name="systems[]" value="tool_room" id="sys_tool_room">
                            <span class="sys-icon"><i class="fas fa-tools"></i></span>
                            <span class="sys-label">SRA Tool Room</span>
                        </label>

                        <label class="sys-check-item">
                            <input type="checkbox" name="systems[]" value="scheduling" id="sys_scheduling">
                            <span class="sys-icon"><i class="fas fa-calendar-alt"></i></span>
                            <span class="sys-label">SRA Event Scheduling</span>
                        </label>

                        <label class="sys-check-item">
                            <input type="checkbox" name="systems[]" value="attendance" id="sys_attendance">
                            <span class="sys-icon"><i class="fas fa-address-book"></i></span>
                            <span class="sys-label">SRA Attendance</span>
                        </label>

                        <label class="sys-check-item">
                            <input type="checkbox" name="systems[]" value="payroll" id="sys_payroll">
                            <span class="sys-icon"><i class="fas fa-calculator"></i></span>
                            <span class="sys-label">SRA Payroll</span>
                        </label>

                        <label class="sys-check-item">
                            <input type="checkbox" name="systems[]" value="employee_info" id="sys_employee_info">
                            <span class="sys-icon"><i class="fas fa-id-card"></i></span>
                            <span class="sys-label">SRA Employee Information</span>
                        </label>

                        <label class="sys-check-item">
                            <input type="checkbox" name="systems[]" value="system_logs" id="sys_system_logs">
                            <span class="sys-icon"><i class="fas fa-clipboard-list"></i></span>
                            <span class="sys-label">System Logs</span>
                        </label>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-submit">Save</button>
            </div>
        </form>
    </div>
</div>

<div id="passwordModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Change Password</h3>
            <button class="close" onclick="closePasswordModal()">&times;</button>
        </div>
        <form id="passwordForm" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="id" id="passwordUserId">
                <div class="form-group">
                    <label id="passwordUsername" style="font-size:16px; color:#333; margin-bottom:15px;"></label>
                </div>
                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="new_password" id="newPassword" required minlength="4">
                    <small style="color:#666;">Minimum 4 characters</small>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" id="confirmPassword" required minlength="4">
                    <small id="passwordMatch" style="color:#666;"></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closePasswordModal()">Cancel</button>
                <button type="submit" class="btn-submit" id="passwordSubmit">Change Password</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="sratool/js/delete-confirm.js"></script>
<script src="js/dropdown.js"></script>
<script>

function toggleSystemAccess(role) {
    const section    = document.getElementById('systemAccessSection');
    const checkboxes = document.getElementById('sysCheckboxes');
    const modNote    = document.getElementById('modNote');

    section.style.display    = 'block';
    checkboxes.style.display = (role === 'moderator') ? 'none' : 'block';
    modNote.style.display    = (role === 'moderator') ? 'flex' : 'none';
}

function openAddModal() {
    document.getElementById('modalTitle').textContent    = 'Add New User';
    document.getElementById('formAction').value          = 'add';
    document.getElementById('userId').value              = '';
    document.getElementById('username').value            = '';
    document.getElementById('password').value            = '';
    document.getElementById('fullName').value            = '';
    document.getElementById('email').value               = '';
    document.getElementById('role').value                = 'user';
    document.getElementById('password').required         = true;

    document.querySelectorAll('input[name="systems[]"]').forEach(cb => cb.checked = false);
    toggleSystemAccess('user');

    document.getElementById('userModal').style.display = 'flex';
}

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.edit-btn');
    if (!btn) return;

    const id          = btn.dataset.id;
    const username    = btn.dataset.username;
    const fullName    = btn.dataset.fullname;
    const email       = btn.dataset.email;
    const role        = btn.dataset.role;
    const permissions = JSON.parse(btn.dataset.permissions || '[]');

    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('formAction').value       = 'edit';
    document.getElementById('userId').value           = id;
    document.getElementById('username').value         = username;
    document.getElementById('password').value         = '';
    document.getElementById('fullName').value         = fullName;
    document.getElementById('email').value            = email;
    document.getElementById('role').value             = role;
    document.getElementById('password').required      = false;

    document.querySelectorAll('input[name="systems[]"]').forEach(cb => {
        cb.checked = permissions.includes(cb.value);
    });
    toggleSystemAccess(role);

    document.getElementById('userModal').style.display = 'flex';
});

function closeModal() {
    document.getElementById('userModal').style.display = 'none';
}

function openPasswordModal(id, username) {
    document.getElementById('passwordUserId').value      = id;
    document.getElementById('passwordUsername').textContent = 'Changing password for: ' + username;
    document.getElementById('newPassword').value         = '';
    document.getElementById('confirmPassword').value     = '';
    document.getElementById('passwordMatch').textContent = '';
    document.getElementById('passwordSubmit').disabled   = false;
    document.getElementById('passwordModal').style.display = 'flex';
}

function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
}

document.getElementById('confirmPassword').addEventListener('input', function () {
    const match  = document.getElementById('passwordMatch');
    const submit = document.getElementById('passwordSubmit');
    const same   = this.value === document.getElementById('newPassword').value;
    match.textContent = same ? '✓ Passwords match' : '✗ Passwords do not match';
    match.style.color = same ? '#16a34a' : '#dc2626';
    submit.disabled   = !same;
});

function deleteUser(id, username) {
    confirmDelete(username, function () {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    });
}

window.addEventListener('click', function (e) {
    if (e.target.id === 'userModal')     closeModal();
    if (e.target.id === 'passwordModal') closePasswordModal();
});
</script>
</body>
</html>

