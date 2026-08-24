<?php
// Jay影视 - 全局配置文件
// 兼容所有PHP版本

// 网站基本信息
define('SITE_NAME', 'Jay影视');
define('SITE_URL', ''); // 部署时填写实际域名
define('SITE_DESCRIPTION', 'Jay影视 - 精彩影视在线观看');

// TMDB API配置
define('TMDB_API_KEY', 'cb44223c5dee5676ed3a839f42ed27e3');
define('TMDB_API_URL', 'https://api.themoviedb.org/3');
define('TMDB_IMAGE_URL', 'https://image.tmdb.org/t/p');

// 播放源配置
define('DEFAULT_SOURCE_URL', 'https://api.yyzy-tv.vip/inc/apijson.php');
define('PLAYER_URL', 'https://svip.ffzyplay.com/?url=');

// 数据库配置 - 使用SQLite兼容所有PHP版本
define('DB_PATH', __DIR__ . '/../data/database.sqlite');

// SMTP邮件配置
define('SMTP_HOST', 'smtp.163.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'jtxnb886@163.com');
define('SMTP_PASS', 'FLLRDtadYAfGXp9Y');
define('SMTP_FROM', 'jtxnb886@163.com');
define('SMTP_FROM_NAME', 'Jay影视');

// 管理员配置
define('ADMIN_USERNAME', '杰同学');
define('ADMIN_PASSWORD', '101113');

// 主题色配置（默认青色主题）
define('THEME_PRIMARY', '#05d4c7');
define('THEME_SECONDARY', '#0e1929');
define('THEME_ACCENT', '#1f80d6');
define('THEME_BG', '#0b1019');
define('THEME_CARD', '#161f2e');
define('THEME_TEXT', '#ffffff');
define('THEME_TEXT_SECONDARY', '#b3b3b3');

// 会话配置
define('SESSION_LIFETIME', 3600);
define('CODE_EXPIRATION', 300);

// 错误报告
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 时区设置
date_default_timezone_set('Asia/Shanghai');
