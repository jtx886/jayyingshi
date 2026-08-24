<?php
// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'jay_ys');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// SMTP配置
define('SMTP_HOST', 'smtp.163.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'jtxnb886@163.com');
define('SMTP_PASS', 'FLLRDtadYAfGXp9Y');
define('SMTP_FROM', 'jtxnb886@163.com');
define('SMTP_FROM_NAME', 'Jay影视');

// 站点配置
define('SITE_URL', (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
define('SITE_NAME', 'Jay影视');

// 时间设置
date_default_timezone_set('Asia/Shanghai');

// 错误显示（生产环境关闭）
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 会话设置
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
