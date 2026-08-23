<?php
require_once dirname(__FILE__) . '/functions.php';
$simple = isset($simple_header) ? (bool)$simple_header : false;
$themeColor = function_exists('getThemeColor') ? getThemeColor() : '#5b5cff';
$siteName = defined('SITE_NAME') ? SITE_NAME : 'Jay影视';
$siteUrl = defined('SITE_URL') ? SITE_URL : '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' - ' : ''; ?><?php echo e($siteName); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        a { text-decoration: none; color: inherit; }
        input, button { font-family: inherit; }
        :root {
            --theme-color: <?php echo $themeColor; ?>;
            --theme-color-dark: <?php
                $hex = ltrim($themeColor, '#');
                $r = max(0, hexdec(substr($hex, 0, 2)) - 30);
                $g = max(0, hexdec(substr($hex, 2, 2)) - 30);
                $b = max(0, hexdec(substr($hex, 4, 2)) - 30);
                echo sprintf('#%02x%02x%02x', $r, $g, $b);
            ?>;
        }
        <?php if (!$simple): ?>
        .site-navbar {
            background: #fff;
            border-bottom: 1px solid #eee;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-logo {
            font-size: 20px;
            font-weight: 700;
            background: linear-gradient(135deg, #5b5cff 0%, #7c4dff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-logo-icon { font-size: 24px; -webkit-text-fill-color: initial; }
        .nav-links { display: flex; align-items: center; gap: 24px; }
        .nav-links a {
            color: #555;
            font-size: 14px;
            transition: color 0.2s;
        }
        .nav-links a:hover { color: var(--theme-color); }
        <?php endif; ?>
    </style>
</head>
<body>
<?php if (!$simple): ?>
    <nav class="site-navbar">
        <div class="nav-container">
            <a href="<?php echo e($siteUrl); ?>/index.php" class="nav-logo">
                <span class="nav-logo-icon">🎬</span>
                <span><?php echo e($siteName); ?></span>
            </a>
            <div class="nav-links">
                <a href="<?php echo e($siteUrl); ?>/index.php">首页</a>
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo e($siteUrl); ?>/user.php">个人中心</a>
                    <a href="<?php echo e($siteUrl); ?>/logout.php">退出登录</a>
                <?php else: ?>
                    <a href="<?php echo e($siteUrl); ?>/login.php">登录</a>
                    <a href="<?php echo e($siteUrl); ?>/register.php">注册</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
<?php endif; ?>
