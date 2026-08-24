<?php
require_once __DIR__ . '/api/common.php';

$user = getCurrentUser();
$pageTitle = '意见反馈';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$db = Database::getInstance();

$totalCount = $db->fetch("SELECT COUNT(*) as c FROM feedback WHERE status = 1")['c'];
$totalPages = max(1, ceil($totalCount / $limit));

$feedbacks = $db->fetchAll("
    SELECT f.*, u.username, u.avatar, u.is_admin as poster_is_admin
    FROM feedback f
    LEFT JOIN users u ON u.id = f.user_id
    WHERE f.status = 1
    ORDER BY f.id DESC
    LIMIT {$limit} OFFSET {$offset}
");

$likedIds = [];
if ($user) {
    $liked = $db->fetchAll("SELECT feedback_id FROM feedback_likes WHERE user_id = ?", [$user['id']]);
    foreach ($liked as $l) $likedIds[] = $l['feedback_id'];
}

function formatTimeAgo($datetime) {
    $now = time();
    $ago = strtotime($datetime);
    $diff = $now - $ago;
    if ($diff < 60) return $diff . '秒前';
    if ($diff < 3600) return floor($diff / 60) . '分钟前';
    if ($diff < 86400) return floor($diff / 3600) . '小时前';
    if ($diff < 2592000) return floor($diff / 86400) . '天前';
    return date('Y-m-d', $ago);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle; ?> - Jay影视</title>
<style>
:root {
    --theme-color: #05d4c7;
    --theme-light: #3de8db;
    --theme-dark: #03a398;
    --theme-gradient: linear-gradient(135deg, #05d4c7 0%, #1f80d6 100%);
    --bg-primary: #0b1019;
    --bg-secondary: #111827;
    --bg-tertiary: #1a2236;
    --bg-card: rgba(26, 34, 54, 0.6);
    --bg-input: rgba(255,255,255,0.04);
    --text-primary: #ffffff;
    --text-secondary: #9ca3af;
    --text-muted: #6b7280;
    --border-color: rgba(255,255,255,0.07);
    --border-light: rgba(255,255,255,0.14);
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --info: #3b82f6;
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 24px;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
    --shadow-md: 0 4px 20px rgba(0,0,0,0.4);
    --shadow-lg: 0 10px 40px rgba(0,0,0,0.5);
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
    background: var(--bg-primary);
    color: var(--text-primary);
    line-height:1.6;
    min-height:100vh;
    overflow-x:hidden;
}
body::before {
    content:'';
    position:fixed;
    inset:0;
    background:
        radial-gradient(ellipse at top left, rgba(5,212,199,0.12) 0%, transparent 50%),
        radial-gradient(ellipse at bottom right, rgba(31,128,214,0.1) 0%, transparent 50%);
    pointer-events:none;
    z-index:-1;
}
.container { max-width:900px; margin:0 auto; padding:20px; }

.page-header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin:24px 0 20px;
    flex-wrap:wrap;
    gap:14px;
}
.page-title {
    display:flex;
    align-items:center;
    gap:10px;
    font-size:26px;
    font-weight:800;
}
.page-title-icon {
    width:38px; height:38px;
    background: var(--theme-gradient);
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
}
.submit-feedback-btn {
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:12px 24px;
    background: var(--theme-gradient);
    color:#fff;
    border:none;
    border-radius:12px;
    font-weight:700;
    font-size:14px;
    cursor:pointer;
    box-shadow: 0 4px 15px rgba(5,212,199,0.3);
    transition: all 0.25s;
}
.submit-feedback-btn:hover { transform:translateY(-2px); box-shadow: 0 8px 25px rgba(5,212,199,0.5); }

.icon-svg {
    display:inline-block;
    background: currentColor;
    -webkit-mask-size: contain;
    mask-size: contain;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
    -webkit-mask-position: center;
    mask-position: center;
}
.icon-chat {
    width:20px; height:20px;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'/%3E%3C/svg%3E");
}
.icon-pencil {
    width:16px; height:16px;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 20h9'/%3E%3Cpath d='M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 20h9'/%3E%3Cpath d='M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z'/%3E%3C/svg%3E");
}
.icon-heart {
    width:16px; height:16px;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z'/%3E%3C/svg%3E");
}
.icon-heart-filled {
    width:16px; height:16px;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpath d='M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z'/%3E%3C/svg%3E");
}
.icon-reply {
    width:16px; height:16px;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='9 17 4 12 9 7'/%3E%3Cpath d='M20 18v-2a4 4 0 0 0-4-4H4'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='9 17 4 12 9 7'/%3E%3Cpath d='M20 18v-2a4 4 0 0 0-4-4H4'/%3E%3C/svg%3E");
}
.icon-send {
    width:16px; height:16px;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='22' y1='2' x2='11' y2='13'/%3E%3Cpolygon points='22 2 15 22 11 13 2 9 22 2'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='22' y1='2' x2='11' y2='13'/%3E%3Cpolygon points='22 2 15 22 11 13 2 9 22 2'/%3E%3C/svg%3E");
}
.icon-close {
    width:18px; height:18px;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='18' y1='6' x2='6' y2='18'/%3E%3Cline x1='6' y1='6' x2='18' y2='18'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='18' y1='6' x2='6' y2='18'/%3E%3Cline x1='6' y1='6' x2='18' y2='18'/%3E%3C/svg%3E");
}
.icon-dev {
    width:14px; height:14px;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='16 18 22 12 16 6'/%3E%3Cpolyline points='8 6 2 12 8 18'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='16 18 22 12 16 6'/%3E%3Cpolyline points='8 6 2 12 8 18'/%3E%3C/svg%3E");
}

.feedback-timeline {
    position: relative;
    padding-left: 24px;
}
.feedback-timeline::before {
    content:'';
    position:absolute;
    left:8px; top:0; bottom:0;
    width:2px;
    background: linear-gradient(to bottom, var(--theme-color), transparent);
    opacity:0.3;
}
.feedback-list { display:flex; flex-direction:column; gap:20px; }

.feedback-card {
    position:relative;
    background: var(--bg-card);
    border:1px solid var(--border-color);
    border-radius: var(--radius-lg);
    overflow:hidden;
    transition: all 0.2s;
}
.feedback-card:hover { border-color: rgba(5,212,199,0.3); }

.feedback-head {
    padding:22px;
    display:flex;
    gap:14px;
    align-items:flex-start;
}
.feedback-avatar {
    width:44px; height:44px;
    border-radius:50%;
    overflow:hidden;
    flex-shrink:0;
    background: var(--bg-tertiary);
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    font-size:18px;
    color: var(--theme-light);
}
.feedback-avatar img { width:100%; height:100%; object-fit:cover; }
.feedback-content { flex:1; min-width:0; }
.feedback-user-line {
    display:flex;
    align-items:center;
    gap:8px;
    margin-bottom:8px;
    flex-wrap:wrap;
}
.feedback-username {
    font-weight:700;
    font-size:15px;
    display:flex;
    align-items:center;
    gap:6px;
}
.admin-badge {
    display:inline-flex;
    align-items:center;
    gap:3px;
    padding:2px 8px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border-radius:6px;
    font-size:10px;
    font-weight:800;
    color:#fff;
    letter-spacing:0.5px;
}
.feedback-time { font-size:12px; color: var(--text-muted); }
.feedback-body {
    color: var(--text-secondary);
    font-size:14px;
    line-height:1.7;
    word-break: break-word;
    white-space: pre-wrap;
}

.feedback-actions {
    display:flex;
    align-items:center;
    gap:6px;
    padding:12px 22px;
    border-top:1px solid var(--border-color);
    background: rgba(0,0,0,0.15);
}
.action-btn {
    display:inline-flex;
    align-items:center;
    gap:6px;
    background:none;
    border:none;
    color: var(--text-secondary);
    font-size:13px;
    font-weight:500;
    cursor:pointer;
    padding:6px 12px;
    border-radius:8px;
    transition: all 0.2s;
}
.action-btn:hover { background: rgba(255,255,255,0.06); color: var(--text-primary); }
.action-btn.liked { color: #ef4444; }
.action-btn.liked .icon-heart { display:none; }
.action-btn.liked .icon-heart-filled { display:inline-block; }
.action-btn .icon-heart-filled { display:none; }
.action-count { font-weight:600; }

.like-animation {
    position:relative;
}
.like-pop {
    position:absolute;
    left:50%;
    top:50%;
    transform: translate(-50%, -50%) scale(0);
    pointer-events:none;
    font-size:14px;
    color:#ef4444;
    animation: likePop 0.5s ease-out;
}
@keyframes likePop {
    0% { transform: translate(-50%, -50%) scale(0); opacity:0; }
    50% { transform: translate(-50%, -50%) scale(1.4); opacity:1; }
    100% { transform: translate(-50%, -80%) scale(1); opacity:0; }
}

.replies-section {
    padding: 0 22px 18px;
    border-top: 1px solid var(--border-color);
    display:none;
}
.replies-section.active { display:block; animation: fadeIn 0.25s; }
@keyframes fadeIn { from{opacity:0;} to{opacity:1;} }

.reply-card {
    display:flex;
    gap:10px;
    padding:12px 14px;
    border-radius:12px;
    margin-top:10px;
    background: rgba(255,255,255,0.03);
    border:1px solid var(--border-color);
}
.reply-card.admin-reply {
    background: rgba(239,68,68,0.07);
    border-color: rgba(239,68,68,0.25);
}
.reply-avatar {
    width:32px; height:32px;
    border-radius:50%;
    overflow:hidden;
    flex-shrink:0;
    background: var(--bg-tertiary);
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    font-size:13px;
    color: var(--theme-light);
}
.reply-avatar img { width:100%; height:100%; object-fit:cover; }
.reply-body { flex:1; min-width:0; }
.reply-head { display:flex; align-items:center; gap:8px; margin-bottom:4px; font-size:13px; }
.reply-username { font-weight:700; color: var(--text-primary); }
.reply-text { font-size:13px; color: var(--text-secondary); line-height:1.6; word-break: break-word; white-space: pre-wrap; }

.collapsed-replies .reply-card:nth-child(n+4) { display:none; }
.expand-replies-btn, .collapse-replies-btn {
    display:block;
    margin: 10px auto 0;
    padding: 7px 18px;
    background: rgba(5,212,199,0.1);
    color: var(--theme-light);
    border:1px solid rgba(5,212,199,0.3);
    border-radius:8px;
    font-size:12px;
    font-weight:600;
    cursor:pointer;
    transition: all 0.2s;
}
.expand-replies-btn:hover, .collapse-replies-btn:hover {
    background: var(--theme-color);
    color:#fff;
    border-color: transparent;
}

.reply-input-wrap {
    display:flex;
    gap:10px;
    margin-top:14px;
}
.reply-input {
    flex:1;
    padding:10px 14px;
    background: var(--bg-input);
    border:1px solid var(--border-color);
    border-radius:10px;
    color: var(--text-primary);
    font-size:14px;
    transition: all 0.2s;
}
.reply-input:focus { border-color: var(--theme-color); }
.reply-submit-btn {
    padding:10px 20px;
    background: var(--theme-gradient);
    color:#fff;
    border:none;
    border-radius:10px;
    font-weight:600;
    font-size:13px;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:6px;
    transition: all 0.2s;
}
.reply-submit-btn:hover { transform:translateY(-1px); box-shadow: 0 4px 12px rgba(5,212,199,0.3); }
.reply-login-hint {
    padding:10px 14px;
    background: rgba(59,130,246,0.1);
    border:1px solid rgba(59,130,246,0.3);
    border-radius:10px;
    font-size:13px;
    color: var(--info);
    text-align:center;
    margin-top:14px;
}
.reply-login-hint a { color: var(--info); font-weight:600; text-decoration:underline; }

.pagination {
    display:flex;
    justify-content:center;
    align-items:center;
    gap:8px;
    margin:30px 0;
}
.page-btn {
    min-width:40px; height:40px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:10px;
    background: var(--bg-card);
    border:1px solid var(--border-color);
    color: var(--text-secondary);
    font-weight:600;
    font-size:14px;
    padding: 0 12px;
    transition: all 0.2s;
    text-decoration:none;
}
.page-btn:hover { background: var(--theme-color); color:#fff; border-color: var(--theme-color); }
.page-btn.active {
    background: var(--theme-gradient);
    color:#fff;
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(5,212,199,0.4);
}
.page-btn:disabled { opacity:0.4; cursor:not-allowed; }

.load-more-wrap { text-align:center; margin:24px 0; }
.load-more-btn {
    padding:12px 32px;
    background: var(--bg-card);
    border:1px solid var(--border-color);
    border-radius:12px;
    color: var(--text-primary);
    font-weight:600;
    cursor:pointer;
    transition: all 0.2s;
}
.load-more-btn:hover { border-color: var(--theme-color); color: var(--theme-light); }

.empty-state { text-align:center; padding:60px 20px; color: var(--text-muted); }
.empty-icon {
    width:90px; height:90px;
    margin:0 auto 16px;
    background: var(--bg-card);
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
}
.empty-icon::before {
    content:'';
    width:40px; height:40px;
    background: var(--text-muted);
    -webkit-mask-size: contain;
    mask-size: contain;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
    -webkit-mask-position: center;
    mask-position: center;
}
.empty-icon-feedback::before {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'/%3E%3C/svg%3E");
}
.empty-title { font-size:18px; font-weight:700; color: var(--text-primary); margin-bottom:8px; }
.empty-desc { margin-bottom:20px; }

.modal-overlay {
    position:fixed; inset:0;
    background: rgba(0,0,0,0.75);
    backdrop-filter: blur(6px);
    z-index:9999;
    display:none;
    align-items:center;
    justify-content:center;
    padding:20px;
}
.modal-overlay.active { display:flex; animation: fadeIn 0.25s; }
.modal {
    width:100%;
    max-width:520px;
    background: var(--bg-secondary);
    border:1px solid var(--border-color);
    border-radius: var(--radius-xl);
    overflow:hidden;
    box-shadow: var(--shadow-lg);
    animation: modalIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes modalIn { from{opacity:0;transform:translateY(30px) scale(0.95);} to{opacity:1;transform:translateY(0) scale(1);} }
.modal-header {
    background: var(--theme-gradient);
    padding:22px 26px;
    position:relative;
}
.modal-header h3 { font-size:20px; font-weight:800; display:flex; align-items:center; gap:8px; }
.modal-close {
    position:absolute;
    top:14px; right:14px;
    width:32px; height:32px;
    background: rgba(255,255,255,0.18);
    border:none;
    border-radius:8px;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition: all 0.2s;
}
.modal-close:hover { background: rgba(255,255,255,0.32); }
.modal-body { padding:24px 26px; }
.form-group { margin-bottom:16px; }
.form-label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; color: var(--text-secondary); }
.form-textarea {
    width:100%;
    padding:14px 16px;
    background: var(--bg-input);
    border:1px solid var(--border-color);
    border-radius:12px;
    color: var(--text-primary);
    font-size:14px;
    font-family:inherit;
    resize:vertical;
    min-height:140px;
    transition: all 0.2s;
}
.form-textarea:focus { border-color: var(--theme-color); box-shadow: 0 0 0 3px rgba(5,212,199,0.12); }
.form-hint { font-size:12px; color: var(--text-muted); margin-top:4px; }
.submit-btn-lg {
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    width:100%;
    padding:14px;
    background: var(--theme-gradient);
    color:#fff;
    border:none;
    border-radius:12px;
    font-weight:700;
    font-size:15px;
    cursor:pointer;
    box-shadow: 0 4px 15px rgba(5,212,199,0.3);
    transition: all 0.25s;
}
.submit-btn-lg:hover { transform:translateY(-2px); box-shadow: 0 8px 25px rgba(5,212,199,0.5); }
.submit-btn-lg:disabled { opacity:0.5; cursor:not-allowed; transform:none; }

.toast {
    position:fixed;
    top:20px; left:50%;
    transform:translateX(-50%) translateY(-100px);
    background: var(--bg-secondary);
    border:1px solid var(--border-color);
    padding:12px 24px;
    border-radius:12px;
    color: var(--text-primary);
    font-size:14px;
    font-weight:500;
    z-index:10000;
    transition: transform 0.3s;
    box-shadow: var(--shadow-lg);
}
.toast.show { transform:translateX(-50%) translateY(0); }
.toast.success { border-color: var(--success); }
.toast.error { border-color: var(--danger); }
.toast.info { border-color: var(--info); }

.user-avatar-display {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:44px; height:44px;
    border-radius:50%;
    background: var(--theme-gradient);
    color:#fff;
    font-weight:700;
    font-size:18px;
}

@media (max-width: 600px) {
    .container { padding:14px; }
    .page-header { flex-direction:column; align-items:flex-start; }
    .page-title { font-size:22px; }
    .feedback-head { padding:18px; gap:12px; }
    .feedback-avatar { width:38px; height:38px; }
    .feedback-username { font-size:14px; }
    .feedback-actions { padding:10px 18px; }
    .action-btn { padding:5px 8px; font-size:12px; }
    .replies-section { padding:0 18px 14px; }
    .submit-feedback-btn { padding:10px 18px; font-size:13px; }
    .pagination { gap:6px; }
    .page-btn { min-width:36px; height:36px; font-size:13px; }
}
</style>
</head>
<body>

<div class="container">
    <div class="page-header">
        <div class="page-title">
            <span class="page-title-icon">
                <span class="icon-svg icon-chat" style="color:#fff;"></span>
            </span>
            意见反馈
        </div>
        <button class="submit-feedback-btn" id="openFeedbackModal">
            <span class="icon-svg icon-pencil"></span>
            提交反馈
        </button>
    </div>

    <?php if (empty($feedbacks)): ?>
        <div class="empty-state">
            <div class="empty-icon empty-icon-feedback"></div>
            <div class="empty-title">还没有任何反馈</div>
            <div class="empty-desc">成为第一个提建议的人吧~</div>
            <button class="submit-feedback-btn" style="margin:0 auto;" id="openFeedbackModal2">
                <span class="icon-svg icon-pencil"></span>
                提交反馈
            </button>
        </div>
    <?php else: ?>
        <div class="feedback-timeline">
            <div class="feedback-list" id="feedbackList">
                <?php foreach ($feedbacks as $f):
                    $fbUser = ['username' => $f['username'], 'avatar' => $f['avatar']];
                    $replies = $db->fetchAll("
                        SELECT r.*, u.username, u.avatar, u.is_admin
                        FROM feedback_replies r
                        LEFT JOIN users u ON u.id = r.user_id
                        WHERE r.feedback_id = ?
                        ORDER BY
                            CASE WHEN u.is_admin = 1 THEN 1 ELSE 2 END,
                            r.created_at ASC
                    ", [$f['id']]);

                    $replyCount = count($replies);
                    $likeCount = intval($db->fetch("SELECT COUNT(*) as c FROM feedback_likes WHERE feedback_id = ?", [$f['id']])['c']);
                    $isLiked = in_array($f['id'], $likedIds);
                ?>
                <div class="feedback-card" data-fid="<?php echo $f['id']; ?>">
                    <div class="feedback-head">
                        <div class="feedback-avatar">
                            <?php if (!empty($f['avatar'])): ?>
                                <img src="<?php echo htmlspecialchars($f['avatar']); ?>" alt="">
                            <?php else: ?>
                                <?php echo htmlspecialchars(mb_substr($f['username'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="feedback-content">
                            <div class="feedback-user-line">
                                <span class="feedback-username">
                                    <?php echo htmlspecialchars($f['username']); ?>
                                    <?php if (!empty($f['poster_is_admin'])): ?>
                                        <span class="admin-badge">
                                            <span class="icon-svg icon-dev" style="color:#fff;"></span>
                                            开发者
                                        </span>
                                    <?php endif; ?>
                                </span>
                                <span class="feedback-time"><?php echo formatTimeAgo($f['created_at']); ?></span>
                            </div>
                            <div class="feedback-body"><?php echo nl2br(htmlspecialchars($f['content'])); ?></div>
                        </div>
                    </div>

                    <div class="feedback-actions">
                        <button class="action-btn like-btn <?php echo $isLiked ? 'liked' : ''; ?>" data-like="<?php echo $f['id']; ?>">
                            <span class="icon-svg icon-heart"></span>
                            <span class="icon-svg icon-heart-filled"></span>
                            <span class="action-count like-count"><?php echo $likeCount; ?></span>
                        </button>
                        <button class="action-btn reply-toggle" data-reply-toggle="<?php echo $f['id']; ?>">
                            <span class="icon-svg icon-reply"></span>
                            <span class="action-count reply-count"><?php echo $replyCount; ?></span>
                        </button>
                    </div>

                    <div class="replies-section collapsed-replies" id="replies-<?php echo $f['id']; ?>">
                        <?php if (!empty($replies)): ?>
                            <?php foreach ($replies as $r):
                                $rUser = ['username' => $r['username'], 'avatar' => $r['avatar']];
                            ?>
                                <div class="reply-card <?php echo !empty($r['is_admin']) ? 'admin-reply' : ''; ?>">
                                    <div class="reply-avatar">
                                        <?php if (!empty($r['avatar'])): ?>
                                            <img src="<?php echo htmlspecialchars($r['avatar']); ?>" alt="">
                                        <?php else: ?>
                                            <?php echo htmlspecialchars(mb_substr($r['username'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="reply-body">
                                        <div class="reply-head">
                                            <span class="reply-username">
                                                <?php echo htmlspecialchars($r['username']); ?>
                                                <?php if (!empty($r['is_admin'])): ?>
                                                    <span class="admin-badge" style="font-size:9px;">
                                                        <span class="icon-svg icon-dev" style="color:#fff;width:10px;height:10px;"></span>
                                                        开发者
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                            <span class="feedback-time"><?php echo formatTimeAgo($r['created_at']); ?></span>
                                        </div>
                                        <div class="reply-text"><?php echo nl2br(htmlspecialchars($r['content'])); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($replies) > 3): ?>
                                <button class="expand-replies-btn" data-fid="<?php echo $f['id']; ?>">展开全部 <?php echo count($replies); ?> 条回复</button>
                                <button class="collapse-replies-btn" data-fid="<?php echo $f['id']; ?>" style="display:none;">收起回复</button>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($user): ?>
                        <div class="reply-input-wrap">
                            <input type="text" class="reply-input" placeholder="写下你的回复..." data-reply-input="<?php echo $f['id']; ?>">
                            <button class="reply-submit-btn" data-fid="<?php echo $f['id']; ?>">
                                <span class="icon-svg icon-send"></span>
                                回复
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="reply-login-hint">
                            请先<a href="login.php">登录</a>后参与讨论
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a class="page-btn" href="?page=<?php echo $page - 1; ?>">‹</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a class="page-btn <?php echo $i === $page ? 'active' : ''; ?>" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a class="page-btn" href="?page=<?php echo $page + 1; ?>">›</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="load-more-wrap">
            <button class="load-more-btn" id="loadMoreBtn" data-page="<?php echo $page; ?>" data-total="<?php echo $totalPages; ?>">
                加载更多
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- 提交反馈弹窗 -->
<div class="modal-overlay" id="feedbackModal">
    <div class="modal">
        <div class="modal-header">
            <h3>
                <span class="icon-svg icon-pencil" style="color:#fff;"></span>
                发布新反馈
            </h3>
            <button class="modal-close" data-close-modal="feedbackModal">
                <span class="icon-svg icon-close" style="color:#fff;"></span>
            </button>
        </div>
        <div class="modal-body">
            <?php if (!$user): ?>
                <div style="text-align:center; padding:20px 0;">
                    <div style="font-size:42px; margin-bottom:12px;">🔒</div>
                    <div style="color: var(--text-secondary); margin-bottom:16px;">请先登录后发布反馈</div>
                    <a href="login.php" class="submit-btn-lg" style="max-width:200px; margin:0 auto;">去登录</a>
                </div>
            <?php else: ?>
                <form id="feedbackForm">
                    <div class="form-group">
                        <label class="form-label">反馈内容</label>
                        <textarea name="content" class="form-textarea" id="feedbackContent" placeholder="请详细描述您的问题或建议，我们会认真对待每一条反馈~" required></textarea>
                        <div class="form-hint"><span id="charCount">0</span> / 2000 字</div>
                    </div>
                    <button type="submit" class="submit-btn-lg" id="submitFeedbackBtn">
                        <span class="icon-svg icon-send"></span>
                        提交反馈
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function(){
    var feedbackModal = document.getElementById('feedbackModal');
    var openBtns = [document.getElementById('openFeedbackModal'), document.getElementById('openFeedbackModal2')];
    for (var i = 0; i < openBtns.length; i++) {
        if (openBtns[i]) {
            openBtns[i].addEventListener('click', function(){
                feedbackModal.classList.add('active');
            });
        }
    }

    var closeBtns = document.querySelectorAll('[data-close-modal]');
    for (var i = 0; i < closeBtns.length; i++) {
        closeBtns[i].addEventListener('click', function(){
            var id = this.getAttribute('data-close-modal');
            document.getElementById(id).classList.remove('active');
        });
    }

    feedbackModal.addEventListener('click', function(e){
        if (e.target === feedbackModal) feedbackModal.classList.remove('active');
    });

    var feedbackForm = document.getElementById('feedbackForm');
    var feedbackContent = document.getElementById('feedbackContent');
    var charCount = document.getElementById('charCount');
    if (feedbackContent && charCount) {
        feedbackContent.addEventListener('input', function(){
            charCount.textContent = this.value.length;
        });
    }

    if (feedbackForm) {
        feedbackForm.addEventListener('submit', function(e){
            e.preventDefault();
            var content = feedbackContent.value.trim();
            if (!content) {
                showToast('请输入反馈内容', 'error');
                return;
            }
            var submitBtn = document.getElementById('submitFeedbackBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = '提交中...';

            var formData = new FormData();
            formData.append('action', 'create');
            formData.append('content', content);

            fetch('api/feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.code === 200) {
                    showToast('反馈提交成功', 'success');
                    feedbackModal.classList.remove('active');
                    feedbackForm.reset();
                    charCount.textContent = '0';
                    setTimeout(function(){ location.reload(); }, 800);
                } else {
                    showToast(d.message || '提交失败', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span class="icon-svg icon-send"></span>提交反馈';
                }
            })
            .catch(function(){
                showToast('网络错误，请重试', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="icon-svg icon-send"></span>提交反馈';
            });
        });
    }

    document.body.addEventListener('click', function(e){
        var likeBtn = e.target.closest('.like-btn');
        if (likeBtn) {
            var fid = likeBtn.getAttribute('data-like');
            var isLiked = likeBtn.classList.contains('liked');

            var formData = new FormData();
            formData.append('action', 'like');
            formData.append('feedback_id', fid);

            fetch('api/feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.code === 200) {
                    var countEl = likeBtn.querySelector('.like-count');
                    likeBtn.classList.toggle('liked', d.liked);
                    var currentCount = parseInt(countEl.textContent) || 0;
                    countEl.textContent = d.liked ? currentCount + 1 : Math.max(0, currentCount - 1);

                    if (d.liked) {
                        var pop = document.createElement('span');
                        pop.className = 'like-pop';
                        pop.textContent = '♥';
                        likeBtn.appendChild(pop);
                        setTimeout(function(){ pop.remove(); }, 500);
                    }
                } else {
                    showToast(d.message || '操作失败', 'error');
                }
            })
            .catch(function(){ showToast('网络错误', 'error'); });
            return;
        }

        var replyToggle = e.target.closest('.reply-toggle');
        if (replyToggle) {
            var fid = replyToggle.getAttribute('data-reply-toggle');
            var section = document.getElementById('replies-' + fid);
            section.classList.toggle('active');
            if (section.classList.contains('active') && section.children.length <= 1) {
                setTimeout(function(){
                    var input = section.querySelector('.reply-input');
                    if (input) input.focus();
                }, 100);
            }
            return;
        }

        var replyBtn = e.target.closest('.reply-submit-btn');
        if (replyBtn) {
            var fid = replyBtn.getAttribute('data-fid');
            var card = replyBtn.closest('.feedback-card');
            var input = card.querySelector('.reply-input');
            var content = input.value.trim();
            if (!content) {
                showToast('请输入回复内容', 'error');
                input.focus();
                return;
            }
            var origHTML = replyBtn.innerHTML;
            replyBtn.disabled = true;
            replyBtn.textContent = '发送中...';

            var formData = new FormData();
            formData.append('action', 'reply');
            formData.append('feedback_id', fid);
            formData.append('content', content);

            fetch('api/feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.code === 200) {
                    showToast('回复成功', 'success');
                    input.value = '';
                    setTimeout(function(){ location.reload(); }, 600);
                } else {
                    showToast(d.message || '回复失败', 'error');
                    replyBtn.disabled = false;
                    replyBtn.innerHTML = origHTML;
                }
            })
            .catch(function(){
                showToast('网络错误', 'error');
                replyBtn.disabled = false;
                replyBtn.innerHTML = origHTML;
            });
            return;
        }

        var expandBtn = e.target.closest('.expand-replies-btn');
        if (expandBtn) {
            var fid = expandBtn.getAttribute('data-fid');
            var section = document.getElementById('replies-' + fid);
            section.classList.remove('collapsed-replies');
            expandBtn.style.display = 'none';
            var collapseBtn = section.querySelector('.collapse-replies-btn');
            if (collapseBtn) collapseBtn.style.display = 'block';
            return;
        }

        var collapseBtn = e.target.closest('.collapse-replies-btn');
        if (collapseBtn) {
            var fid = collapseBtn.getAttribute('data-fid');
            var section = document.getElementById('replies-' + fid);
            section.classList.add('collapsed-replies');
            collapseBtn.style.display = 'none';
            var expandBtn = section.querySelector('.expand-replies-btn');
            if (expandBtn) expandBtn.style.display = 'block';
            return;
        }
    });

    var loadMoreBtn = document.getElementById('loadMoreBtn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function(){
            var currentPage = parseInt(loadMoreBtn.getAttribute('data-page'));
            var totalPages = parseInt(loadMoreBtn.getAttribute('data-total'));
            var nextPage = currentPage + 1;

            if (nextPage > totalPages) {
                showToast('已经是最后一页了', 'info');
                return;
            }

            loadMoreBtn.textContent = '加载中...';
            loadMoreBtn.disabled = true;

            fetch('feedback.php?page=' + nextPage, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r){ return r.text(); })
            .then(function(html){
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newCards = doc.querySelectorAll('.feedback-card');
                var list = document.getElementById('feedbackList');

                if (newCards.length > 0) {
                    for (var i = 0; i < newCards.length; i++) {
                        list.appendChild(newCards[i]);
                    }
                    showToast('加载成功', 'success');
                }

                loadMoreBtn.setAttribute('data-page', nextPage);
                if (nextPage >= totalPages) {
                    loadMoreBtn.textContent = '没有更多了';
                    loadMoreBtn.style.display = 'none';
                } else {
                    loadMoreBtn.textContent = '加载更多';
                    loadMoreBtn.disabled = false;
                }
            })
            .catch(function(){
                showToast('加载失败', 'error');
                loadMoreBtn.textContent = '加载更多';
                loadMoreBtn.disabled = false;
            });
        });
    }

    function showToast(msg, type) {
        var toast = document.getElementById('toast');
        toast.textContent = msg;
        toast.className = 'toast show' + (type ? ' ' + type : '');
        setTimeout(function(){ toast.classList.remove('show'); }, 2200);
    }
})();
</script>
</body>
</html>