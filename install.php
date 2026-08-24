<?php
// 自动安装脚本 - 首次访问自动创建数据库和默认配置
$installFile = __DIR__ . '/includes/installed.lock';
if (file_exists($installFile)) {
    header('Location: index.php');
    exit();
}

$pageTitle = '安装向导';
require_once __DIR__ . '/includes/functions.php';

$step = intval($_GET['step'] ?? 1);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'check') {
        // 检查并写入配置
        $dbHost = trim($_POST['db_host'] ?? 'localhost');
        $dbName = trim($_POST['db_name'] ?? 'jay_ys');
        $dbUser = trim($_POST['db_user'] ?? 'root');
        $dbPass = $_POST['db_pass'] ?? '';
        
        // 测试连接
        try {
            $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));
            // 创建数据库
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$dbName`");
            
            // 写入配置文件
            $configContent = '<?php
define(\'DB_HOST\', ' . var_export($dbHost, true) . ');
define(\'DB_NAME\', ' . var_export($dbName, true) . ');
define(\'DB_USER\', ' . var_export($dbUser, true) . ');
define(\'DB_PASS\', ' . var_export($dbPass, true) . ');
define(\'DB_CHARSET\', \'utf8mb4\');

define(\'SMTP_HOST\', \'smtp.163.com\');
define(\'SMTP_PORT\', 465);
define(\'SMTP_USER\', \'jtxnb886@163.com\');
define(\'SMTP_PASS\', \'FLLRDtadYAfGXp9Y\');
define(\'SMTP_FROM\', \'jtxnb886@163.com\');
define(\'SMTP_FROM_NAME\', \'Jay影视\');

define(\'SITE_URL\', (isset($_SERVER[\'HTTPS\']) ? \'https\' : \'http\') . \'://\' . $_SERVER[\'HTTP_HOST\'] . dirname($_SERVER[\'SCRIPT_NAME\']));
define(\'SITE_NAME\', \'Jay影视\');

date_default_timezone_set(\'Asia/Shanghai\');

error_reporting(E_ALL);
ini_set(\'display_errors\', 0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>';
            @file_put_contents(__DIR__ . '/includes/config.php', $configContent);
            
            // 导入数据库
            $sql = file_get_contents(__DIR__ . '/database.sql');
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $stmt) {
                if (!empty($stmt) && stripos($stmt, 'CREATE DATABASE') === false && stripos($stmt, 'USE ') === false) {
                    try { $pdo->exec($stmt); } catch (Exception $e) {}
                }
            }
            
            // 重哈希默认管理员密码
            require_once __DIR__ . '/includes/auth.php';
            try {
                $pdo2 = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
                $newHash = Auth::generatePassword('101113');
                $pdo2->exec("UPDATE users SET password = " . $pdo2->quote($newHash) . " WHERE username = '杰同学'");
            } catch (Exception $e) {}
            
            touch($installFile);
            $success = '安装成功！';
            header('Refresh: 2; url=index.php');
        } catch (Exception $e) {
            $error = '数据库连接失败: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jay影视 - 安装向导</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
    body { padding: 40px 20px; }
    .install-wrap { max-width: 620px; margin: 0 auto; }
    .install-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 24px;
        padding: 40px;
        box-shadow: var(--shadow-lg);
        position: relative;
        overflow: hidden;
    }
    .install-card::before { content: ''; position: absolute; top:0; left:0; right:0; height: 4px; background: var(--theme-gradient); }
    .check-item { display:flex; align-items:center; justify-content:space-between; padding: 12px 14px; background: var(--bg-card); border-radius:10px; margin-bottom:8px; }
    .check-pass { color:#6ee7b7; font-weight: 700; }
    .check-fail { color:#fca5a5; font-weight: 700; }
</style>
</head>
<body>
<div class="install-wrap">
    <div style="display:flex; align-items:center; gap:14px; margin-bottom: 26px; justify-content:center;">
        <div class="logo-icon" style="width:44px;height:44px;"></div>
        <div>
            <div style="font-size: 28px; font-weight: 900; background: var(--theme-gradient-2); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Jay影视</div>
            <div style="color:var(--text-muted); font-size: 13px; margin-top: -2px;">安装向导</div>
        </div>
    </div>
    <div class="install-card">
        <?php if ($success): ?>
            <div class="auth-alert auth-alert-success" style="margin-bottom:20px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg>
                <div style="flex:1;">
                    <div style="font-weight:700; margin-bottom:4px;">🎉 <?php echo e($success); ?></div>
                    <div style="font-size:13px;">正在跳转到首页，如未跳转请<a href="index.php" style="text-decoration:underline;">点此进入</a></div>
                </div>
            </div>
            <div style="padding:20px; background: rgba(16,185,129,0.1); border-radius:12px; color:#6ee7b7; font-size:14px; line-height:1.8;">
                <div style="font-weight: 800; color: #fff; margin-bottom: 8px;">📌 初始管理员账号</div>
                用户名：<strong style="color:#fff;">杰同学</strong><br>
                登录密码：<strong style="color:#fff;">101113</strong><br>
                <span style="color: #fcd34d;">请登录后第一时间修改默认密码！</span>
            </div>
        <?php else: ?>
            <h2 style="font-size:24px; font-weight: 900; margin-bottom: 14px;">环境检测</h2>
            <?php
                $checks = array();
                $checks[] = array('PHP 版本 >= 5.6', PHP_VERSION_ID >= 50600, '当前 ' . PHP_VERSION);
                $checks[] = array('PDO 扩展', extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql') ? '已安装' : '未安装');
                $checks[] = array('cURL 扩展', extension_loaded('curl'), extension_loaded('curl') ? '已安装' : '未安装');
                $checks[] = array('JSON 扩展', extension_loaded('json'), extension_loaded('json') ? '已安装' : '未安装');
                $checks[] = array('写入权限 includes/', is_writable(__DIR__ . '/includes') || !file_exists(__DIR__ . '/includes/config.php'), is_writable(__DIR__ . '/includes') ? '可写' : '需设置777权限');
                $checks[] = array('写入权限 uploads/', is_writable(__DIR__ . '/uploads') || !is_dir(__DIR__ . '/uploads'), is_writable(__DIR__ . '/uploads') ? '可写' : '需设置777权限');
                $allPass = true;
                foreach ($checks as $c) if (!$c[1]) $allPass = false;
            ?>
            <div style="margin-bottom: 20px;">
                <?php foreach ($checks as $c): ?>
                    <div class="check-item">
                        <span><?php echo e($c[0]); ?></span>
                        <span>
                            <span class="<?php echo $c[1] ? 'check-pass' : 'check-fail'; ?>">
                                <?php echo $c[1] ? '✓ ' : '✗ '; ?><?php echo e($c[2]); ?>
                            </span>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($error): ?>
                <div class="auth-alert auth-alert-error" style="margin-bottom:20px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    <span><?php echo e($error); ?></span>
                </div>
            <?php endif; ?>

            <h2 style="font-size:24px; font-weight: 900; margin: 26px 0 14px;">数据库配置</h2>
            <form method="POST">
                <input type="hidden" name="action" value="check">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">数据库主机</label>
                        <input type="text" name="db_host" class="form-input" value="localhost" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">数据库名称</label>
                        <input type="text" name="db_name" class="form-input" value="jay_ys" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">数据库用户名</label>
                        <input type="text" name="db_user" class="form-input" value="root" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">数据库密码</label>
                        <input type="text" name="db_pass" class="form-input" placeholder="留空则无密码">
                    </div>
                </div>
                
                <div class="admin-card" style="margin: 16px 0 24px; padding:18px;">
                    <div style="font-weight:700; margin-bottom: 10px; color:var(--theme-light);">💡 预先配置好的内容（安装后可在后台修改）</div>
                    <ul style="margin-left:20px; font-size:14px; color:var(--text-secondary); line-height:2;">
                        <li>SMTP邮箱：jtxnb886@163.com（注册验证码、封禁通知使用）</li>
                        <li>默认管理员：杰同学 / 101113</li>
                        <li>默认播放源：yyzy-tv.vip</li>
                        <li>默认解析播放器：svip.ffzyplay.com</li>
                        <li>TMDB API：已配置（图片提供）</li>
                    </ul>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg btn-block" <?php if (!$allPass) echo 'disabled'; ?>>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:6px; vertical-align:-3px;"><polyline points="20 6 9 17 4 12"/></svg>
                    开始安装
                </button>
                <?php if (!$allPass): ?>
                    <div style="color:#fca5a5; font-size:13px; text-align:center; margin-top:10px;">请先解决上面的环境问题后再安装</div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
    <div style="text-align:center; color: var(--text-muted); font-size:12px; margin-top: 20px;">
        © <?php echo date('Y'); ?> Jay影视 · 基于 PHP + MySQL 的影视站点系统
    </div>
</div>
</body>
</html>
