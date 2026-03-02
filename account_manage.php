<?php
session_start();
require_once 'sratool/check_moderator.php';
require_once "config.php";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $new_username = $conn->real_escape_string($_POST['username']);
            $new_password = $conn->real_escape_string($_POST['password']);
            $full_name = $conn->real_escape_string($_POST['full_name']);
            $email = $conn->real_escape_string($_POST['email']);
            $role = $conn->real_escape_string($_POST['role']);

            $check = $conn->query("SELECT id FROM users WHERE username = '$new_username'");
            if ($check->num_rows > 0) {
                $_SESSION['error_message'] = "Username already exists!";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username, password, full_name, email, role, created_at) 
                        VALUES ('$new_username', '$hashed_password', '$full_name', '$email', '$role', NOW())";
                if ($conn->query($sql)) {
                    $_SESSION['success_message'] = "User account created successfully!";
                } else {
                    $_SESSION['error_message'] = "Error creating account: " . $conn->error;
                }
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = (int)$_POST['id'];
            $new_username = $conn->real_escape_string($_POST['username']);
            $full_name = $conn->real_escape_string($_POST['full_name']);
            $email = $conn->real_escape_string($_POST['email']);
            $role = $conn->real_escape_string($_POST['role']);

            $check = $conn->query("SELECT id FROM users WHERE username = '$new_username' AND id != $id");
            if ($check->num_rows > 0) {
                $_SESSION['error_message'] = "Username already exists!";
            } else {
                $sql = "UPDATE users SET username='$new_username', full_name='$full_name', 
                        email='$email', role='$role', updated_at=NOW() WHERE id=$id";
                if ($conn->query($sql)) {
                    $_SESSION['success_message'] = "User account updated successfully!";
                } else {
                    $_SESSION['error_message'] = "Error updating account: " . $conn->error;
                }
            }
        } elseif ($_POST['action'] === 'change_password') {
            $id = (int)$_POST['id'];
            $new_password = $conn->real_escape_string($_POST['new_password']);
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
                $sql = "DELETE FROM users WHERE id=$id";
                if ($conn->query($sql)) {
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

$users = $conn->query("SELECT * FROM users ORDER BY role, username ASC");
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$admins = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='admin'")->fetch_assoc()['count'];
$moderators = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='moderator'")->fetch_assoc()['count'];
$regular_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='user'")->fetch_assoc()['count'];

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
    <link rel="stylesheet" href="/sratool/css/consumables.css">
    <link rel="stylesheet" href="/sratool/css/dashboard.css">
    <link rel="stylesheet" href="/sratool/css/portal.css">
    <link rel="stylesheet" href="/sratool/css/responsive.css">
</head>
<body>

<div class="top-header">
    <div class="logo-section">
        <img src="https://smartrye.com.ph/ams/public/backend/images/logo-sra.png" alt="Logo" class="logo-img">
        <h1 class="system-title">Account Management</h1>
    </div>
    <div class="header-right">
        <div class="current-date">
            <?php echo date('l, jS F Y'); ?>
        </div>
        <div class="user-info">
            <div class="user-icon">
                <i class="fas fa-user"></i>
            </div>
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
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-info">
                <h3>Total Accounts</h3>
                <div class="stat-number"><?php echo $total_users; ?></div>
            </div>
            <div class="stat-circle circle-blue" style="--percent: 100%;">
                <div class="circle-inner">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3>Admins</h3>
                <div class="stat-number"><?php echo $admins; ?></div>
            </div>
            <div class="stat-circle circle-purple" style="--percent: <?php echo $total_users > 0 ? round(($admins / $total_users) * 100) : 0; ?>%;">
                <div class="circle-inner">
                    <?php echo $total_users > 0 ? round(($admins / $total_users) * 100) : 0; ?>%
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3>Regular Users</h3>
                <div class="stat-number"><?php echo $regular_users; ?></div>
            </div>
            <div class="stat-circle circle-green" style="--percent: <?php echo $total_users > 0 ? round(($regular_users / $total_users) * 100) : 0; ?>%;">
                <div class="circle-inner">
                    <?php echo $total_users > 0 ? round(($regular_users / $total_users) * 100) : 0; ?>%
                </div>
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
        <?php if ($users->num_rows > 0): ?>
            <table class="consumables-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $users->fetch_assoc()): ?>
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
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action btn-edit" onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['username']); ?>', '<?php echo htmlspecialchars($row['full_name'] ?? ''); ?>', '<?php echo htmlspecialchars($row['email'] ?? ''); ?>', '<?php echo $row['role']; ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn-action btn-adjust" onclick="openPasswordModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['username']); ?>')">
                                        <i class="fas fa-key"></i> Password
                                    </button>
                                    <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn-action btn-delete" onclick="deleteUser(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['username']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
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
                <input type="hidden" name="id" id="userId">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" id="username" required>
                </div>
                <div class="form-group" id="passwordGroup">
                    <label>Password *</label>
                    <input type="password" name="password" id="password">
                    <small style="color: #666;">Leave blank to keep current password when editing</small>
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
                    <select name="role" id="role" required>
                        <option value="user">User</option>
                        <option value="moderator">Moderator</option>
                        <option value="admin">Admin</option>
                    </select>
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
                    <label id="passwordUsername" style="font-size: 16px; color: #333; margin-bottom: 15px;"></label>
                </div>
                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="new_password" id="newPassword" required minlength="4">
                    <small style="color: #666;">Minimum 8 characters</small>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" id="confirmPassword" required minlength="4">
                    <small id="passwordMatch" style="color: #666;"></small>
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
<script src="js/add_user.js"></script>
<script src="js/dropdown.js"></script>
<script>
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
</script>
</body>

</html>

