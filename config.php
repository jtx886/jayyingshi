<?php

// ============================================
// 网站基础配置
// ============================================

// 网站名称
define('SITE_NAME', 'Jay影视');

// 主题颜色默认值（支持自定义，前端使用时可读取此常量）
define('THEME_COLOR', '#1890ff');

// TMDB API Key（The Movie Database 接口密钥）
define('TMDB_API_KEY', 'cb44223c5dee5676ed3a839f42ed27e3');

// TMDB API 基础请求URL
define('TMDB_API_BASE_URL', 'https://api.themoviedb.org/3');

// TMDB 图片资源基础URL
define('TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p');

// 解析播放器URL（用于播放视频解析接口）
define('PLAYER_URL', 'https://svip.ffzyplay.com/?url=');

// 默认播放源接口地址
define('DEFAULT_SOURCE_URL', 'https://api.yyzy-tv.vip/inc/apijson.php');

// ============================================
// SMTP 邮件发送配置
// ============================================

// SMTP 服务器地址
define('SMTP_HOST', 'smtp.163.com');

// SMTP 服务器端口（465为SSL加密端口）
define('SMTP_PORT', 465);

// SMTP 登录用户名
define('SMTP_USER', 'jtxnb886@163.com');

// SMTP 登录密码或授权码
define('SMTP_PASS', 'FLLRDtadYAfGXp9Y');

// 发件人邮箱地址
define('SMTP_FROM', 'jtxnb886@163.com');

// 发件人名称
define('SMTP_FROM_NAME', 'Jay影视');

// ============================================
// 管理员账号配置
// ============================================

// 管理员用户名
define('ADMIN_USERNAME', '杰同学');

// 管理员登录密码
define('ADMIN_PASSWORD', '101113');

// ============================================
// 数据库配置（SQLite）
// ============================================

// SQLite 数据库文件路径
define('DB_PATH', '/workspace/data/jayying.db');

// 数据库类型标识
define('DB_TYPE', 'sqlite');

// ============================================
// Session 配置
// ============================================

// Session 前缀，防止同服务器下多个项目冲突
define('SESSION_PREFIX', 'jaytv_');

// ============================================
// 网站URL配置
// ============================================

// 网站基础URL（末尾不带斜杠）
if (!defined('SITE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
    $scriptName = str_replace('\\', '/', $scriptName);
    $scriptName = rtrim($scriptName, '/');
    define('SITE_URL', rtrim($protocol . $host . $scriptName, '/'));
}

// ============================================
// TMDB API 兼容常量
// ============================================

if (!defined('TMDB_BASE_URL')) {
    define('TMDB_BASE_URL', TMDB_API_BASE_URL);
}
