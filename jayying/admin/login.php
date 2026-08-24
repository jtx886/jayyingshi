<?php
session_start();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: index.php');
        exit;
    } else {
        $error = '用户名或密码错误！';
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jay影视 - 管理员登录</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #1a1f2e 0%, #0f1419 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        .login-card {
            background: #252d3d;
            border-radius: 16px;
            padding: 48px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .login-header {
            text-align: center;
            margin-bottom: 36px;
        }
        .login-logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #05d4c7 0%, #03a89e 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
            color: #fff;
            font-weight: bold;
        }
        .login-title {
            color: #fff;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .login-subtitle {
            color: #8b95a7;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            color: #8b95a7;
            font-size: 13px;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-input {
            width: 100%;
            padding: 14px 16px;
            background: #1a1f2e;
            border: 1px solid #353f52;
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s ease;
            outline: none;
        }
        .form-input:focus {
            border-color: #05d4c7;
            box-shadow: 0 0 0 3px rgba(5, 212, 199, 0.15);
        }
        .form-input::placeholder {
            color: #5a6478;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #05d4c7 0%, #03a89e 100%);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(5, 212, 199, 0.3);
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .error-message::before {
            content: '⚠';
            font-size: 16px;
        }
        .login-footer {
            text-align: center;
            margin-top: 32px;
            color: #5a6478;
            font-size: 13px;
        }
        .login-footer a {
            color: #05d4c7;
            text-decoration: none;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
        @media (max-width: 480px) {
            .login-card {
                padding: 36px 24px;
            }
            .login-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">J</div>
                <h1 class="login-title">Jay影视后台</h1>
                <p class="login-subtitle">欢迎回来，请登录您的管理员账户</p>
            </div>

            <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">用户名</label>
                    <input type="text" name="username" class="form-input" placeholder="请输入管理员用户名" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">密码</label>
                    <input type="password" name="password" class="form-input" placeholder="请输入密码" required>
                </div>
                <button type="submit" class="btn-login">登 录</button>
            </form>

            <div class="login-footer">
                Jay影视管理系统 &copy; 2026
            </div>
        </div>
    </div>
</body>
</html>
