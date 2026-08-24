<?php
require_once __DIR__ . '/api/common.php';

$user = getCurrentUser();
if (!$user) {
    header('Location: login.php');
    exit;
}

$pageTitle = '个人中心';
$activeTab = $_GET['tab'] ?? 'favorites';

$db = Database::getInstance();

$favCount = $db->fetch("SELECT COUNT(*) as c FROM favorites WHERE user_id = ?", [$user['id']])['c'];
$historyCount = $db->fetch("SELECT COUNT(*) as c FROM watch_history WHERE user_id = ?", [$user['id']])['c'];

$favorites = $db->fetchAll("SELECT * FROM favorites WHERE user_id = ? ORDER BY id DESC LIMIT 100", [$user['id']]);
$history = $db->fetchAll("SELECT * FROM watch_history WHERE user_id = ? ORDER BY updated_at DESC LIMIT 100", [$user['id']]);

$userAvatarUrl = !empty($user['avatar']) ? $user['avatar'] : '';
$userAvatarChar = mb_substr($user['username'], 0, 1, 'UTF-8');

$defaultAvatars = [
    'https://api.dicebear.com/7.x/shapes/svg?seed=' . urlencode($user['username']),
    'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($user['username']),
    'https://api.dicebear.com/7.x/adventurer/svg?seed=' . urlencode($user['username']),
];
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
.container { max-width:1400px; margin:0 auto; padding:20px; }

.profile-header {
    background: var(--theme-gradient);
    border-radius: var(--radius-xl);
    padding: 40px;
    margin: 24px 0;
    display: flex;
    align-items: center;
    gap: 28px;
    position: relative;
    overflow: hidden;
}
.profile-header::before {
    content:'';
    position:absolute;
    top:-50%; right:-20%;
    width:500px; height:500px;
    background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 60%);
}
.profile-avatar-wrap { position:relative; flex-shrink:0; }
.profile-avatar {
    width:110px; height:110px;
    border-radius:50%;
    border:4px solid rgba(255,255,255,0.3);
    object-fit:cover;
    background:rgba(255,255,255,0.2);
}
.avatar-upload-btn {
    position:absolute;
    bottom:4px; right:4px;
    width:36px; height:36px;
    background:#fff;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    box-shadow: var(--shadow-md);
    color: var(--theme-color);
    transition: all 0.2s;
    border:none;
}
.avatar-upload-btn:hover { transform:scale(1.1); }
.avatar-upload-btn::before {
    content:'';
    width:16px; height:16px;
    position:relative;
    display:block;
    background: var(--theme-color);
    mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'/%3E%3Cpolyline points='17 8 12 3 7 8'/%3E%3Cline x1='12' y1='3' x2='12' y2='15'/%3E%3C/svg%3E") no-repeat center/contain;
    -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'/%3E%3Cpolyline points='17 8 12 3 7 8'/%3E%3Cline x1='12' y1='3' x2='12' y2='15'/%3E%3C/svg%3E") no-repeat center/contain;
}
.profile-info { position:relative; z-index:1; flex:1; }
.profile-info h2 { font-size:32px; font-weight:900; margin-bottom:6px; }
.profile-info p { opacity:0.9; font-size:14px; }
.profile-stats { display:flex; gap:30px; margin-left:auto; position:relative; z-index:1; }
.profile-stat { text-align:center; }
.profile-stat-num { font-size:32px; font-weight:900; }
.profile-stat-label { font-size:13px; opacity:0.85; margin-top:4px; }

.tabs {
    display:flex;
    gap:6px;
    border-bottom:2px solid var(--border-color);
    margin:28px 0;
    flex-wrap:wrap;
}
.tab-btn {
    padding:12px 22px;
    background:none;
    color: var(--text-secondary);
    font-weight:600;
    font-size:14px;
    border:none;
    border-bottom:2px solid transparent;
    margin-bottom:-2px;
    cursor:pointer;
    transition: all 0.2s;
    display:flex;
    align-items:center;
    gap:8px;
}
.tab-btn:hover { color: var(--text-primary); }
.tab-btn.active { color: var(--theme-light); border-bottom-color: var(--theme-color); }
.tab-pane { display:none; }
.tab-pane.active { display:block; animation: fadeIn 0.3s; }
@keyframes fadeIn { from{opacity:0;transform:translateY(10px);} to{opacity:1;transform:translateY(0);} }

.icon-svg {
    width:16px; height:16px;
    display:inline-block;
    background: currentColor;
    -webkit-mask-size: contain;
    mask-size: contain;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
    -webkit-mask-position: center;
    mask-position: center;
}
.icon-fav {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='currentColor'%3E%3Cpath d='M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='currentColor'%3E%3Cpath d='M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z'/%3E%3C/svg%3E");
}
.icon-history {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8'/%3E%3Cpath d='M3 3v5h5'/%3E%3Cpath d='M12 7v5l4 2'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8'/%3E%3Cpath d='M3 3v5h5'/%3E%3Cpath d='M12 7v5l4 2'/%3E%3C/svg%3E");
}

.fav-grid {
    display:grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap:18px;
}
.fav-card {
    background: var(--bg-card);
    border:1px solid var(--border-color);
    border-radius: var(--radius-md);
    overflow:hidden;
    transition: all 0.3s;
    position:relative;
}
.fav-card:hover { transform:translateY(-4px); border-color: var(--theme-color); box-shadow: 0 12px 30px rgba(0,0,0,0.4); }
.fav-poster {
    aspect-ratio: 2/3;
    background: var(--bg-tertiary);
    position:relative;
    cursor:pointer;
    overflow:hidden;
}
.fav-poster img { width:100%; height:100%; object-fit:cover; transition: transform 0.4s; }
.fav-card:hover .fav-poster img { transform: scale(1.06); }
.fav-poster::after {
    content:'';
    position:absolute;
    bottom:0; left:0; right:0;
    height:50%;
    background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
    pointer-events:none;
}
.fav-badge {
    position:absolute;
    top:8px; left:8px;
    background: rgba(0,0,0,0.75);
    backdrop-filter: blur(8px);
    padding:3px 10px;
    border-radius: 6px;
    font-size:11px;
    font-weight:600;
    color: var(--theme-light);
    z-index:2;
}
.fav-remove {
    position:absolute;
    top:8px; right:8px;
    width:30px; height:30px;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(8px);
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    z-index:3;
    transition: all 0.2s;
    color:#fff;
    border:none;
}
.fav-remove:hover { background: var(--danger); transform:scale(1.1); }
.fav-remove::before {
    content:'×';
    font-size:18px;
    font-weight:700;
    line-height:1;
}
.fav-info { padding:12px; }
.fav-title {
    font-size:14px;
    font-weight:700;
    margin-bottom:4px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.fav-meta { font-size:12px; color: var(--text-muted); }
.fav-actions { display:flex; gap:8px; margin-top:8px; }
.fav-actions .btn { flex:1; padding:6px 10px; font-size:12px; }

.history-list { display:flex; flex-direction:column; gap:12px; }
.history-item {
    display:flex;
    gap:16px;
    padding:16px;
    background: var(--bg-card);
    border:1px solid var(--border-color);
    border-radius: var(--radius-md);
    align-items:center;
    transition: all 0.2s;
}
.history-item:hover { border-color: var(--theme-color); background: rgba(5,212,199,0.04); }
.history-poster {
    width:70px; height:95px;
    border-radius:8px;
    overflow:hidden;
    flex-shrink:0;
    cursor:pointer;
    background: var(--bg-tertiary);
}
.history-poster img { width:100%; height:100%; object-fit:cover; }
.history-info { flex:1; min-width:0; }
.history-title {
    font-size:16px;
    font-weight:700;
    margin-bottom:4px;
    cursor:pointer;
}
.history-title:hover { color: var(--theme-light); }
.history-meta { font-size:13px; color: var(--text-muted); margin-bottom:6px; }
.history-progress {
    height:4px;
    background: rgba(255,255,255,0.08);
    border-radius:2px;
    overflow:hidden;
    margin:8px 0;
    width:100%;
    max-width:300px;
}
.history-progress-bar {
    height:100%;
    background: var(--theme-gradient);
    border-radius:2px;
}
.history-actions { display:flex; gap:8px; align-items:center; flex-shrink:0; }

.btn {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:10px 20px;
    border-radius:10px;
    font-weight:600;
    font-size:14px;
    transition: all 0.25s;
    cursor:pointer;
    border:none;
    text-decoration:none;
    white-space:nowrap;
}
.btn-primary { background: var(--theme-gradient); color:#fff; box-shadow: 0 4px 15px rgba(5,212,199,0.3); }
.btn-primary:hover { transform:translateY(-2px); box-shadow: 0 8px 25px rgba(5,212,199,0.5); }
.btn-outline { background:transparent; border:1px solid var(--border-light); color: var(--text-primary); }
.btn-outline:hover { border-color: var(--theme-color); background: rgba(5,212,199,0.08); color: var(--theme-light); }
.btn-ghost { background: rgba(255,255,255,0.05); color: var(--text-primary); }
.btn-ghost:hover { background: rgba(255,255,255,0.1); }
.btn-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color:#fff; }
.btn-sm { padding:6px 14px; font-size:13px; border-radius:8px; }
.btn-block { width:100%; }
.btn-icon {
    width:36px; height:36px;
    padding:0;
    border-radius:10px;
    background: rgba(255,255,255,0.06);
    color: var(--text-secondary);
    display:inline-flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition: all 0.2s;
    border:none;
}
.btn-icon:hover { background: rgba(239,68,68,0.12); color: var(--danger); }
.btn-icon::before {
    content:'';
    width:16px; height:16px;
    background: currentColor;
    -webkit-mask-size: contain;
    mask-size: contain;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
    -webkit-mask-position: center;
    mask-position: center;
}
.btn-delete::before {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 6h18'/%3E%3Cpath d='M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6'/%3E%3Cpath d='M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 6h18'/%3E%3Cpath d='M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6'/%3E%3Cpath d='M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2'/%3E%3C/svg%3E");
}
.btn-edit::before {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 20h9'/%3E%3Cpath d='M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 20h9'/%3E%3Cpath d='M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z'/%3E%3C/svg%3E");
}
.btn-play::before {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpolygon points='5 3 19 12 5 21 5 3'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpolygon points='5 3 19 12 5 21 5 3'/%3E%3C/svg%3E");
}

.empty-state { text-align:center; padding:60px 20px; color: var(--text-muted); }
.empty-icon {
    width:100px; height:100px;
    margin:0 auto 20px;
    background: var(--bg-card);
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
}
.empty-icon::before {
    content:'';
    width:44px; height:44px;
    background: var(--text-muted);
    -webkit-mask-size: contain;
    mask-size: contain;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
    -webkit-mask-position: center;
    mask-position: center;
}
.empty-icon-fav::before {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z'/%3E%3C/svg%3E");
}
.empty-icon-history::before {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8'/%3E%3Cpath d='M3 3v5h5'/%3E%3Cpath d='M12 7v5l4 2'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8'/%3E%3Cpath d='M3 3v5h5'/%3E%3Cpath d='M12 7v5l4 2'/%3E%3C/svg%3E");
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
    max-width:440px;
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
    padding:24px 28px;
    position:relative;
}
.modal-header h3 { font-size:20px; font-weight:800; }
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
    font-size:18px;
    transition: all 0.2s;
}
.modal-close:hover { background: rgba(255,255,255,0.32); }
.modal-body { padding:24px 28px; }
.form-group { margin-bottom:16px; }
.form-label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; color: var(--text-secondary); }
.form-input, .form-textarea {
    width:100%;
    padding:12px 14px;
    background: var(--bg-input);
    border:1px solid var(--border-color);
    border-radius:10px;
    color: var(--text-primary);
    font-size:14px;
    transition: all 0.2s;
}
.form-input:focus { border-color: var(--theme-color); box-shadow: 0 0 0 3px rgba(5,212,199,0.12); }
.form-hint { font-size:12px; color: var(--text-muted); margin-top:4px; }
.form-row { display:flex; gap:12px; }
.form-row .form-group { flex:1; }

.avatar-options { display:flex; gap:10px; flex-wrap:wrap; margin:12px 0; }
.avatar-option {
    width:56px; height:56px;
    border-radius:50%;
    overflow:hidden;
    cursor:pointer;
    border:2px solid var(--border-color);
    transition: all 0.2s;
    background: var(--bg-tertiary);
}
.avatar-option:hover { transform:scale(1.1); }
.avatar-option.active { border-color: var(--theme-color); box-shadow: 0 0 0 3px rgba(5,212,199,0.3); }
.avatar-option img { width:100%; height:100%; object-fit:cover; }

.toast {
    position:fixed;
    top:20px; left:50%;
    transform:translateX(-50%) translateY(-100px);
    background: var(--bg-card);
    border:1px solid var(--border-color);
    padding:12px 24px;
    border-radius:10px;
    color: var(--text-primary);
    font-size:14px;
    z-index:10000;
    transition: transform 0.3s;
    box-shadow: var(--shadow-lg);
}
.toast.show { transform:translateX(-50%) translateY(0); }
.toast.success { border-color: var(--success); }
.toast.error { border-color: var(--danger); }

.confirm-overlay {
    position:fixed; inset:0;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(4px);
    z-index:9998;
    display:none;
    align-items:center;
    justify-content:center;
    padding:20px;
}
.confirm-overlay.active { display:flex; }
.confirm-box {
    background: var(--bg-secondary);
    border:1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding:28px;
    max-width:340px;
    width:100%;
    text-align:center;
}
.confirm-title { font-size:18px; font-weight:700; margin-bottom:10px; }
.confirm-desc { color: var(--text-secondary); font-size:14px; margin-bottom:22px; }
.confirm-actions { display:flex; gap:12px; justify-content:center; }

@media (max-width: 992px) {
    .profile-header { flex-direction:column; text-align:center; align-items:center; padding:28px 20px; }
    .profile-stats { gap:20px; margin-left:0; margin-top:20px; }
    .fav-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap:14px; }
    .history-item { flex-wrap:wrap; }
    .history-actions { width:100%; justify-content:flex-end; margin-top:8px; }
}
@media (max-width: 600px) {
    .container { padding:14px; }
    .profile-header { padding:24px 18px; }
    .profile-avatar { width:84px; height:84px; }
    .profile-info h2 { font-size:24px; }
    .profile-stats { gap:16px; flex-wrap:wrap; justify-content:center; }
    .profile-stat-num { font-size:26px; }
    .tab-btn { padding:10px 16px; font-size:13px; }
    .fav-grid { grid-template-columns: repeat(2, 1fr); gap:12px; }
    .history-item { padding:12px; gap:12px; }
    .history-poster { width:56px; height:76px; }
    .btn { padding:8px 16px; font-size:13px; }
    .btn-sm { padding:5px 10px; font-size:12px; }
}
</style>
</head>
<body>

<div class="container">

    <div class="profile-header">
        <div class="profile-avatar-wrap">
            <?php if ($userAvatarUrl): ?>
                <img src="<?php echo htmlspecialchars($userAvatarUrl); ?>" class="profile-avatar" id="profileAvatar" alt="avatar">
            <?php else: ?>
                <div class="profile-avatar" id="profileAvatar" style="display:flex;align-items:center;justify-content:center;font-size:48px;font-weight:900;color:#fff;background:linear-gradient(135deg,#05d4c7,#1f80d6);"><?php echo htmlspecialchars($userAvatarChar); ?></div>
            <?php endif; ?>
            <button class="avatar-upload-btn" id="avatarBtn" title="更换头像"></button>
        </div>
        <div class="profile-info">
            <h2><?php echo htmlspecialchars($user['username']); ?>
                <button class="btn-icon btn-edit" id="editProfileBtn" style="margin-left:10px;vertical-align:middle;width:32px;height:32px;background:rgba(255,255,255,0.1);color:#fff;" title="编辑资料"></button>
            </h2>
            <p>📧 <?php echo htmlspecialchars($user['email']); ?></p>
            <p style="margin-top:6px;">📅 加入于 <?php echo date('Y年m月d日', strtotime($user['created_at'])); ?></p>
        </div>
        <div class="profile-stats">
            <div class="profile-stat">
                <div class="profile-stat-num"><?php echo intval($favCount); ?></div>
                <div class="profile-stat-label">收藏</div>
            </div>
            <div class="profile-stat">
                <div class="profile-stat-num"><?php echo intval($historyCount); ?></div>
                <div class="profile-stat-label">观看</div>
            </div>
        </div>
    </div>

    <div class="tabs">
        <button class="tab-btn <?php echo $activeTab == 'favorites' ? 'active' : ''; ?>" data-tab="tab-favorites">
            <span class="icon-svg icon-fav"></span>
            我的收藏 (<?php echo intval($favCount); ?>)
        </button>
        <button class="tab-btn <?php echo $activeTab == 'history' ? 'active' : ''; ?>" data-tab="tab-history">
            <span class="icon-svg icon-history"></span>
            观看历史 (<?php echo intval($historyCount); ?>)
        </button>
    </div>

    <!-- 收藏 -->
    <div class="tab-pane <?php echo $activeTab == 'favorites' ? 'active' : ''; ?>" id="tab-favorites">
        <?php if (empty($favorites)): ?>
            <div class="empty-state">
                <div class="empty-icon empty-icon-fav"></div>
                <div class="empty-title">还没有收藏任何影视</div>
                <div class="empty-desc">去首页逛逛看到喜欢的收藏起来吧~</div>
                <a href="index.php" class="btn btn-primary">去逛逛</a>
            </div>
        <?php else: ?>
            <div class="fav-grid">
                <?php foreach ($favorites as $fav): ?>
                    <div class="fav-card">
                        <div class="fav-poster" onclick="window.location='detail.php?id=<?php echo urlencode($fav['tmdb_id']); ?>&type=<?php echo htmlspecialchars($fav['media_type']); ?>'">
                            <?php if (!empty($fav['poster'])): ?>
                                <img src="<?php echo htmlspecialchars($fav['poster']); ?>" alt="<?php echo htmlspecialchars($fav['title']); ?>" loading="lazy">
                            <?php else: ?>
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:13px;">无海报</div>
                            <?php endif; ?>
                            <span class="fav-badge"><?php echo htmlspecialchars($fav['media_type'] === 'movie' ? '电影' : '电视剧'); ?></span>
                            <button class="fav-remove" data-delete-fav="<?php echo $fav['id']; ?>" title="移除收藏"></button>
                        </div>
                        <div class="fav-info">
                            <div class="fav-title"><?php echo htmlspecialchars($fav['title']); ?></div>
                            <div class="fav-meta"><?php echo date('Y-m-d', strtotime($fav['created_at'])); ?></div>
                            <div class="fav-actions">
                                <a href="player.php?id=<?php echo urlencode($fav['tmdb_id']); ?>&type=<?php echo htmlspecialchars($fav['media_type']); ?>" class="btn btn-primary btn-sm">
                                    <span class="icon-svg btn-play" style="width:12px;height:12px;"></span>
                                    播放
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- 观看历史 -->
    <div class="tab-pane <?php echo $activeTab == 'history' ? 'active' : ''; ?>" id="tab-history">
        <?php if (empty($history)): ?>
            <div class="empty-state">
                <div class="empty-icon empty-icon-history"></div>
                <div class="empty-title">还没有观看记录</div>
                <div class="empty-desc">去挑一部精彩的影片来看看吧~</div>
                <a href="index.php" class="btn btn-primary">去看看</a>
            </div>
        <?php else: ?>
            <div style="margin-bottom:16px; text-align:right;">
                <button class="btn btn-ghost btn-sm" id="clearHistoryBtn">
                    <span class="icon-svg" style="width:14px;height:14px;background:var(--danger);"></span>
                    清空全部
                </button>
            </div>
            <div class="history-list">
                <?php foreach ($history as $h):
                    $epLabel = '';
                    if ($h['media_type'] !== 'movie' && !empty($h['episode'])) {
                        $epLabel = ' 第' . $h['episode'] . '集';
                    }
                ?>
                    <div class="history-item">
                        <div class="history-poster" onclick="window.location='detail.php?id=<?php echo urlencode($h['tmdb_id']); ?>&type=<?php echo htmlspecialchars($h['media_type']); ?>'">
                            <?php if (!empty($h['poster'])): ?>
                                <img src="<?php echo htmlspecialchars($h['poster']); ?>" alt="<?php echo htmlspecialchars($h['title']); ?>" loading="lazy">
                            <?php else: ?>
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:12px;">无图</div>
                            <?php endif; ?>
                        </div>
                        <div class="history-info">
                            <div class="history-title" onclick="window.location='player.php?id=<?php echo urlencode($h['tmdb_id']); ?>&type=<?php echo htmlspecialchars($h['media_type']); ?><?php if (!empty($h['episode'])) echo '&episode=' . intval($h['episode']); ?>'">
                                <?php echo htmlspecialchars($h['title']); ?><?php echo $epLabel; ?>
                            </div>
                            <div class="history-meta">
                                <?php echo htmlspecialchars($h['media_type'] === 'movie' ? '电影' : '电视剧'); ?>
                                <span style="margin-left:12px;"><?php echo date('Y-m-d H:i', strtotime($h['updated_at'])); ?></span>
                            </div>
                            <?php if (!empty($h['progress']) && $h['progress'] > 0): ?>
                                <div class="history-progress">
                                    <div class="history-progress-bar" style="width: <?php echo min(100, intval($h['progress'])); ?>%;"></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="history-actions">
                            <a href="player.php?id=<?php echo urlencode($h['tmdb_id']); ?>&type=<?php echo htmlspecialchars($h['media_type']); ?><?php if (!empty($h['episode'])) echo '&episode=' . intval($h['episode']); ?>" class="btn btn-primary btn-sm">继续观看</a>
                            <button class="btn-icon btn-delete" data-delete-history="<?php echo $h['id']; ?>" title="删除记录"></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 头像更换弹窗 -->
<div class="modal-overlay" id="avatarModal">
    <div class="modal">
        <div class="modal-header">
            <h3>更换头像</h3>
            <button class="modal-close" data-close-modal="avatarModal">×</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">自定义头像URL</label>
                <input type="text" class="form-input" id="avatarUrlInput" placeholder="输入图片URL地址">
                <div class="form-hint">支持JPG/PNG格式的图片链接</div>
            </div>
            <div class="form-group">
                <label class="form-label">选择默认头像</label>
                <div class="avatar-options" id="avatarOptions">
                    <?php foreach ($defaultAvatars as $i => $url): ?>
                        <div class="avatar-option <?php echo $i === 0 ? 'active' : ''; ?>" data-url="<?php echo htmlspecialchars($url); ?>">
                            <img src="<?php echo htmlspecialchars($url); ?>" alt="avatar" loading="lazy">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="display:flex; gap:10px; margin-top:16px;">
                <button class="btn btn-ghost" data-close-modal="avatarModal" style="flex:1;">取消</button>
                <button class="btn btn-primary" id="saveAvatarBtn" style="flex:1;">保存头像</button>
            </div>
        </div>
    </div>
</div>

<!-- 编辑资料弹窗 -->
<div class="modal-overlay" id="editProfileModal">
    <div class="modal">
        <div class="modal-header">
            <h3>编辑资料</h3>
            <button class="modal-close" data-close-modal="editProfileModal">×</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">用户名</label>
                <input type="text" class="form-input" id="newUsername" value="<?php echo htmlspecialchars($user['username']); ?>" placeholder="3-20个字符" maxlength="20">
                <div class="form-hint">用户名长度3-20位，支持中英文和数字</div>
            </div>
            <div style="display:flex; gap:10px; margin-top:16px;">
                <button class="btn btn-ghost" data-close-modal="editProfileModal" style="flex:1;">取消</button>
                <button class="btn btn-primary" id="saveProfileBtn" style="flex:1;">保存修改</button>
            </div>
        </div>
    </div>
</div>

<!-- 确认弹窗 -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <div class="confirm-title" id="confirmTitle">确认操作</div>
        <div class="confirm-desc" id="confirmDesc">此操作不可恢复，确定继续吗？</div>
        <div class="confirm-actions">
            <button class="btn btn-ghost" id="confirmCancel">取消</button>
            <button class="btn btn-primary" id="confirmOk">确定</button>
        </div>
    </div>
</div>

<!-- Toast提示 -->
<div class="toast" id="toast"></div>

<script>
(function(){
    var avatarModal = document.getElementById('avatarModal');
    var avatarBtn = document.getElementById('avatarBtn');
    var avatarUrlInput = document.getElementById('avatarUrlInput');
    var avatarOptions = document.getElementById('avatarOptions');
    var saveAvatarBtn = document.getElementById('saveAvatarBtn');
    var currentAvatarUrl = '';

    avatarBtn.addEventListener('click', function(){
        avatarModal.classList.add('active');
        avatarUrlInput.value = '';
        currentAvatarUrl = '';
        var opts = avatarOptions.querySelectorAll('.avatar-option');
        for (var i = 0; i < opts.length; i++) {
            opts[i].classList.remove('active');
        }
    });

    var closeBtns = document.querySelectorAll('[data-close-modal]');
    for (var i = 0; i < closeBtns.length; i++) {
        closeBtns[i].addEventListener('click', function(){
            var id = this.getAttribute('data-close-modal');
            document.getElementById(id).classList.remove('active');
        });
    }

    avatarUrlInput.addEventListener('input', function(){
        var opts = avatarOptions.querySelectorAll('.avatar-option');
        for (var i = 0; i < opts.length; i++) {
            opts[i].classList.remove('active');
        }
        currentAvatarUrl = this.value.trim();
    });

    avatarOptions.addEventListener('click', function(e){
        var opt = e.target.closest('.avatar-option');
        if (!opt) return;
        var opts = avatarOptions.querySelectorAll('.avatar-option');
        for (var i = 0; i < opts.length; i++) {
            opts[i].classList.remove('active');
        }
        opt.classList.add('active');
        currentAvatarUrl = opt.getAttribute('data-url');
        avatarUrlInput.value = '';
    });

    saveAvatarBtn.addEventListener('click', function(){
        if (!currentAvatarUrl) {
            showToast('请输入URL或选择默认头像', 'error');
            return;
        }
        var formData = new FormData();
        formData.append('action', 'user_profile');
        formData.append('avatar', currentAvatarUrl);
        fetch('api/data.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.code === 200) {
                showToast('头像更新成功', 'success');
                var avatarEl = document.getElementById('profileAvatar');
                if (avatarEl.tagName === 'IMG') {
                    avatarEl.src = currentAvatarUrl;
                } else {
                    var newImg = document.createElement('img');
                    newImg.src = currentAvatarUrl;
                    newImg.className = 'profile-avatar';
                    newImg.id = 'profileAvatar';
                    newImg.alt = 'avatar';
                    avatarEl.parentNode.replaceChild(newImg, avatarEl);
                }
                userAvatarUrl = currentAvatarUrl;
                avatarModal.classList.remove('active');
            } else {
                showToast(d.message || '更新失败', 'error');
            }
        })
        .catch(function(){ showToast('网络错误', 'error'); });
    });

    var editProfileBtn = document.getElementById('editProfileBtn');
    var editProfileModal = document.getElementById('editProfileModal');
    var newUsernameInput = document.getElementById('newUsername');
    var saveProfileBtn = document.getElementById('saveProfileBtn');

    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', function(){
            newUsernameInput.value = '<?php echo htmlspecialchars($user['username']); ?>';
            editProfileModal.classList.add('active');
        });
    }

    if (saveProfileBtn) {
        saveProfileBtn.addEventListener('click', function(){
            var newUsername = newUsernameInput.value.trim();
            if (newUsername.length < 3 || newUsername.length > 20) {
                showToast('用户名长度需在3-20位之间', 'error');
                return;
            }
            saveProfileBtn.disabled = true;
            saveProfileBtn.textContent = '保存中...';

            var formData = new FormData();
            formData.append('action', 'user_profile');
            formData.append('username', newUsername);

            fetch('api/data.php', {
                method: 'POST',
                body: formData
            })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.code === 200) {
                    showToast('资料更新成功', 'success');
                    editProfileModal.classList.remove('active');
                    setTimeout(function(){ location.reload(); }, 600);
                } else {
                    showToast(d.message || '更新失败', 'error');
                    saveProfileBtn.disabled = false;
                    saveProfileBtn.textContent = '保存修改';
                }
            })
            .catch(function(){
                showToast('网络错误', 'error');
                saveProfileBtn.disabled = false;
                saveProfileBtn.textContent = '保存修改';
            });
        });
    }

    var tabs = document.querySelectorAll('.tab-btn');
    var tabPanes = document.querySelectorAll('.tab-pane');
    for (var i = 0; i < tabs.length; i++) {
        tabs[i].addEventListener('click', function(){
            var target = this.getAttribute('data-tab');
            for (var j = 0; j < tabs.length; j++) tabs[j].classList.remove('active');
            for (var j = 0; j < tabPanes.length; j++) tabPanes[j].classList.remove('active');
            this.classList.add('active');
            document.getElementById(target).classList.add('active');
            if (history.replaceState) {
                history.replaceState(null, '', '?tab=' + target.replace('tab-', ''));
            }
        });
    }

    document.body.addEventListener('click', function(e){
        var delFav = e.target.closest('[data-delete-fav]');
        if (delFav) {
            var id = delFav.getAttribute('data-delete-fav');
            confirmAction('删除收藏', '确定要从收藏中移除这部影视吗？', function(){
                fetch('api/data.php', {
                    method: 'POST',
                    body: new URLSearchParams('action=favorites&sub_action=remove&id=' + id)
                })
                .then(function(r){ return r.json(); })
                .then(function(d){
                    if (d.code === 200) {
                        showToast('已移除收藏', 'success');
                        delFav.closest('.fav-card').style.transition = 'all 0.3s';
                        delFav.closest('.fav-card').style.opacity = '0';
                        delFav.closest('.fav-card').style.transform = 'scale(0.9)';
                        setTimeout(function(){ delFav.closest('.fav-card').remove(); }, 300);
                    } else {
                        showToast(d.message || '删除失败', 'error');
                    }
                })
                .catch(function(){ showToast('网络错误', 'error'); });
            });
            return;
        }

        var delHis = e.target.closest('[data-delete-history]');
        if (delHis) {
            var hid = delHis.getAttribute('data-delete-history');
            confirmAction('删除记录', '确定要删除这条观看记录吗？', function(){
                fetch('api/data.php', {
                    method: 'POST',
                    body: new URLSearchParams('action=watch_history&sub_action=remove&id=' + hid)
                })
                .then(function(r){ return r.json(); })
                .then(function(d){
                    if (d.code === 200) {
                        showToast('已删除', 'success');
                        delHis.closest('.history-item').style.transition = 'all 0.3s';
                        delHis.closest('.history-item').style.opacity = '0';
                        setTimeout(function(){ delHis.closest('.history-item').remove(); }, 300);
                    } else {
                        showToast(d.message || '删除失败', 'error');
                    }
                })
                .catch(function(){ showToast('网络错误', 'error'); });
            });
            return;
        }
    });

    var clearBtn = document.getElementById('clearHistoryBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function(){
            confirmAction('清空历史', '确定要清空全部观看历史吗？此操作不可恢复！', function(){
                fetch('api/data.php', {
                    method: 'POST',
                    body: new URLSearchParams('action=watch_history&sub_action=clear')
                })
                .then(function(r){ return r.json(); })
                .then(function(d){
                    if (d.code === 200) {
                        showToast('已清空', 'success');
                        document.querySelectorAll('.history-item').forEach(function(item){
                            item.style.transition = 'all 0.3s';
                            item.style.opacity = '0';
                        });
                        setTimeout(function(){ location.reload(); }, 400);
                    } else {
                        showToast(d.message || '操作失败', 'error');
                    }
                })
                .catch(function(){ showToast('网络错误', 'error'); });
            });
        });
    }

    var confirmOverlay = document.getElementById('confirmOverlay');
    var confirmOk = document.getElementById('confirmOk');
    var confirmCancel = document.getElementById('confirmCancel');
    var confirmTitle = document.getElementById('confirmTitle');
    var confirmDesc = document.getElementById('confirmDesc');
    var confirmCallback = null;

    function confirmAction(title, desc, callback) {
        confirmTitle.textContent = title;
        confirmDesc.textContent = desc;
        confirmCallback = callback;
        confirmOverlay.classList.add('active');
    }
    confirmCancel.addEventListener('click', function(){
        confirmOverlay.classList.remove('active');
        confirmCallback = null;
    });
    confirmOk.addEventListener('click', function(){
        if (confirmCallback) confirmCallback();
        confirmOverlay.classList.remove('active');
        confirmCallback = null;
    });

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