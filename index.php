<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: portal");
    exit();
}

$error = '';
$login_success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'config.php';

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            header("Location: portal");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    } else {
        $error = "Invalid username or password";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart Rye Automatics</title>
    <link rel="icon" type="image/png" sizes="32x32" href="sratool/img/favicon-32x32.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sratool/css/index.css">
    <link rel="stylesheet" href="sratool/css/responsive.css">
</head>
<body>

    <div class="login-orb login-orb-1"></div>
    <div class="login-orb login-orb-2"></div>
    <div class="login-orb login-orb-3"></div>

    <div id="loadingOverlay">
        <div class="spinner"></div>
    </div>

<div class="container h-p100 d-flex justify-content-center align-items-center">
    <div class="border-shadow">

                            <div class="content-top-agile pb-0">
                                <img src="https://smartrye.com.ph/ams/public/backend/images/logo-sra.png"
                                     class="logo-img" alt="Smart Rye Automatics Logo">
                                <p class="subtitle mt-2">Smart Rye Automatics Portal</p>
                            </div>

                            <div class="p-30">
                                <?php if ($error): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="" class="form-horizontal"
                                      autocomplete="off" onsubmit="showLoading()">

                                    <div class="form-group">
                                        <div class="input-group mb-3">
                                            <span class="input-group-text bg-transparent">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <input type="text"
                                                   class="form-control ps-15 bg-transparent"
                                                   name="username"
                                                   placeholder="Username"
                                                   required autofocus>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="input-group mb-3">
                                            <span class="input-group-text bg-transparent">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password"
                                                   class="form-control ps-15 bg-transparent"
                                                   name="password"
                                                   id="passwordInput"
                                                   placeholder="Password"
                                                   required>
                                            <button type="button" class="toggle-pw"
                                                    id="togglePw"
                                                    aria-label="Show/hide password"
                                                    title="Show/hide password">
                                                <i class="fas fa-eye" id="togglePwIcon"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-6"></div>
                                        <div class="col-6">
                                            <div class="fog-pwd text-end">
                                                <a href="javascript:void(0)"
                                                   class="hover-warning"
                                                   style="display: none;">
                                                    <i class="fas fa-lock"></i> Forgot pwd?
                                                </a><br>
                                            </div>
                                        </div>
                                        <div class="col-12 text-center">
                                            <button type="submit" class="btn btn-primary mt-10">
                                                SIGN IN
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        <?php if ($login_success): ?>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('loadingOverlay').style.display = 'flex';
            setTimeout(function () { window.location.href = 'portal'; }, 1000);
        });
        <?php elseif ($error): ?>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('loadingOverlay').style.display = 'flex';
            setTimeout(function () {
                document.getElementById('loadingOverlay').style.display = 'none';
            }, 800);
        });
        <?php endif; ?>

        document.getElementById('togglePw').addEventListener('click', function () {
            var input = document.getElementById('passwordInput');
            var icon  = document.getElementById('togglePwIcon');
            var isHidden = input.type === 'password';

            input.type       = isHidden ? 'text' : 'password';
            icon.className   = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
            this.title       = isHidden ? 'Hide password' : 'Show password';
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    
</body>
</html>
