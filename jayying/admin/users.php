<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
$users = [
    ['id' => 1, 'name' => '张小明', 'email' => 'zhangxm@example.com', 'status' => 'active', 'created' => '2026-08-24 10:30', 'avatar' => '张', 'bannedUntil' => null, 'banReason' => ''],
    ['id' => 2, 'name' => '李婷婷', 'email' => 'litt@example.com', 'status' => 'active', 'created' => '2026-08-23 14:20', 'avatar' => '李', 'bannedUntil' => null, 'banReason' => ''],
    ['id' => 3, 'name' => '王大壮', 'email' => 'wangdz@example.com', 'status' => 'banned', 'created' => '2026-08-20 09:15', 'avatar' => '王', 'bannedUntil' => '2026-09-20', 'banReason' => '恶意评论'],
    ['id' => 4, 'name' => '赵雪琪', 'email' => 'zhaoxq@example.com', 'status' => 'active', 'created' => '2026-08-19 16:45', 'avatar' => '赵', 'bannedUntil' => null, 'banReason' => ''],
    ['id' => 5, 'name' => '陈思远', 'email' => 'chensy@example.com', 'status' => 'active', 'created' => '2026-08-18 11:30', 'avatar' => '陈', 'bannedUntil' => null, 'banReason' => ''],
    ['id' => 6, 'name' => '刘芳', 'email' => 'liuf@example.com', 'status' => 'banned', 'created' => '2026-08-15 08:00', 'avatar' => '刘', 'bannedUntil' => '永久', 'banReason' => '违规内容'],
    ['id' => 7, 'name' => '周俊杰', 'email' => 'zhoujj@example.com', 'status' => 'active', 'created' => '2026-08-14 20:15', 'avatar' => '周', 'bannedUntil' => null, 'banReason' => ''],
    ['id' => 8, 'name' => '吴敏', 'email' => 'wum@example.com', 'status' => 'active', 'created' => '2026-08-13 13:25', 'avatar' => '吴', 'bannedUntil' => null, 'banReason' => ''],
    ['id' => 9, 'name' => '郑佳', 'email' => 'zhengj@example.com', 'status' => 'banned', 'created' => '2026-08-12 17:40', 'avatar' => '郑', 'bannedUntil' => '2026-08-26', 'banReason' => '广告推广'],
    ['id' => 10, 'name' => '孙浩', 'email' => 'sunh@example.com', 'status' => 'active', 'created' => '2026-08-11 09:50', 'avatar' => '孙', 'bannedUntil' => null, 'banReason' => ''],
    ['id' => 11, 'name' => '马超', 'email' => 'machao@example.com', 'status' => 'active', 'created' => '2026-08-10 15:30', 'avatar' => '马', 'bannedUntil' => null, 'banReason' => ''],
    ['id' => 12, 'name' => '朱琳', 'email' => 'zhul@example.com', 'status' => 'active', 'created' => '2026-08-09 10:10', 'avatar' => '朱', 'bannedUntil' => null, 'banReason' => ''],
];
$search = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$filteredUsers = array_filter($users, function($u) use ($search, $filterStatus) {
    $matchSearch = !$search || stripos($u['name'], $search) !== false || stripos($u['email'], $search) !== false;
    $matchStatus = $filterStatus === 'all' || $u['status'] === $filterStatus;
    return $matchSearch && $matchStatus;
});
$totalUsers = count($filteredUsers);
$totalPages = max(1, ceil($totalUsers / $perPage));
$pageUsers = array_slice($filteredUsers, ($page - 1) * $perPage, $perPage);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - Jay影视后台</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #1a1f2e;
            color: #e2e8f0;
            min-height: 100vh;
        }
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0;
            width: 240px; background: #252d3d;
            padding: 24px 0; overflow-y: auto;
            border-right: 1px solid rgba(255,255,255,0.05);
            transition: transform 0.3s ease; z-index: 100;
        }
        .sidebar-header { padding: 0 24px 24px; border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 16px; }
        .sidebar-logo { display: flex; align-items: center; gap: 12px; }
        .logo-icon { width: 40px; height: 40px; background: linear-gradient(135deg, #05d4c7, #03a89e); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; font-size: 18px; }
        .logo-text { color: #fff; font-size: 18px; font-weight: 600; }
        .logo-sub { color: #8b95a7; font-size: 11px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 24px; color: #8b95a7; text-decoration: none; font-size: 14px; transition: all 0.2s; border-left: 3px solid transparent; }
        .nav-item:hover { background: rgba(255,255,255,0.03); color: #fff; }
        .nav-item.active { background: rgba(5,212,199,0.1); color: #05d4c7; border-left-color: #05d4c7; }
        .nav-icon { width: 20px; text-align: center; font-size: 16px; }
        .main-content { margin-left: 240px; padding: 24px 32px; min-height: 100vh; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-title { font-size: 24px; font-weight: 600; color: #fff; }
        .page-subtitle { color: #8b95a7; font-size: 14px; margin-top: 4px; }
        .top-bar-actions { display: flex; align-items: center; gap: 16px; }
        .admin-info { display: flex; align-items: center; gap: 12px; }
        .admin-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #05d4c7, #03a89e); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; }
        .admin-name { color: #fff; font-size: 14px; }
        .admin-role { color: #8b95a7; font-size: 12px; }
        .logout-btn { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; transition: all 0.2s; }
        .logout-btn:hover { background: rgba(239,68,68,0.2); }
        .mobile-menu-btn { display: none; background: #252d3d; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 18px; }
        .toolbar { display: flex; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
        .search-box { position: relative; flex: 1; max-width: 320px; }
        .search-input { width: 100%; padding: 10px 14px 10px 40px; background: #252d3d; border: 1px solid #353f52; border-radius: 8px; color: #fff; font-size: 14px; outline: none; }
        .search-input:focus { border-color: #05d4c7; box-shadow: 0 0 0 3px rgba(5,212,199,0.15); }
        .search-box::before { content: '🔍'; position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 14px; }
        .filter-select { padding: 10px 14px; background: #252d3d; border: 1px solid #353f52; border-radius: 8px; color: #fff; font-size: 14px; outline: none; cursor: pointer; }
        .filter-select:focus { border-color: #05d4c7; }
        .card { background: #252d3d; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); overflow: hidden; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 16px 20px; color: #8b95a7; font-size: 13px; font-weight: 500; background: rgba(255,255,255,0.02); }
        .data-table td { padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover { background: rgba(255,255,255,0.02); }
        .user-cell { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: #353f52; display: flex; align-items: center; justify-content: center; color: #8b95a7; font-weight: 600; font-size: 14px; }
        .user-name-text { color: #fff; font-weight: 500; }
        .user-email { color: #8b95a7; font-size: 13px; }
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .status-active { background: rgba(16,185,129,0.1); color: #10b981; }
        .status-banned { background: rgba(239,68,68,0.1); color: #ef4444; }
        .status-dot { width: 6px; height: 6px; border-radius: 50%; }
        .status-active .status-dot { background: #10b981; }
        .status-banned .status-dot { background: #ef4444; }
        .action-buttons { display: flex; gap: 8px; }
        .btn { padding: 6px 12px; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-view { background: rgba(5,212,199,0.1); color: #05d4c7; }
        .btn-view:hover { background: rgba(5,212,199,0.2); }
        .btn-ban { background: rgba(239,68,68,0.1); color: #ef4444; }
        .btn-ban:hover { background: rgba(239,68,68,0.2); }
        .btn-unban { background: rgba(16,185,129,0.1); color: #10b981; }
        .btn-unban:hover { background: rgba(16,185,129,0.2); }
        .btn-delete { background: rgba(239,68,68,0.1); color: #ef4444; }
        .btn-delete:hover { background: rgba(239,68,68,0.2); }
        .pagination { display: flex; justify-content: space-between; align-items: center; padding: 20px; }
        .pagination-info { color: #8b95a7; font-size: 14px; }
        .pagination-controls { display: flex; gap: 8px; }
        .page-btn { width: 36px; height: 36px; border: 1px solid #353f52; background: transparent; color: #8b95a7; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .page-btn:hover:not(:disabled) { border-color: #05d4c7; color: #05d4c7; }
        .page-btn.active { background: #05d4c7; color: #fff; border-color: #05d4c7; }
        .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 200; align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.show { display: flex; }
        .modal { background: #252d3d; border-radius: 16px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; }
        .modal-title { color: #fff; font-size: 18px; font-weight: 600; }
        .modal-close { background: none; border: none; color: #8b95a7; font-size: 24px; cursor: pointer; }
        .modal-body { padding: 24px; }
        .modal-footer { padding: 20px 24px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: flex-end; gap: 12px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; color: #8b95a7; font-size: 13px; margin-bottom: 8px; font-weight: 500; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 12px 14px; background: #1a1f2e; border: 1px solid #353f52; border-radius: 8px; color: #fff; font-size: 14px; outline: none; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: #05d4c7; box-shadow: 0 0 0 3px rgba(5,212,199,0.15); }
        .form-textarea { resize: vertical; min-height: 80px; }
        .btn-primary { background: linear-gradient(135deg, #05d4c7, #03a89e); color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(5,212,199,0.3); }
        .btn-secondary { background: transparent; color: #8b95a7; border: 1px solid #353f52; padding: 10px 24px; border-radius: 8px; font-size: 14px; cursor: pointer; transition: all 0.2s; }
        .btn-secondary:hover { color: #fff; border-color: #8b95a7; }
        .tab-container { border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 20px; display: flex; gap: 4px; }
        .tab-btn { padding: 12px 20px; background: none; border: none; color: #8b95a7; font-size: 14px; cursor: pointer; border-bottom: 2px solid transparent; transition: all 0.2s; }
        .tab-btn.active { color: #05d4c7; border-bottom-color: #05d4c7; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .detail-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .detail-item:last-child { border-bottom: none; }
        .detail-label { color: #8b95a7; font-size: 13px; }
        .detail-value { color: #fff; font-size: 14px; }
        .empty-state { text-align: center; padding: 40px; color: #5a6478; }
        .empty-icon { font-size: 48px; margin-bottom: 12px; }
        .banner-info { background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3); color: #f59e0b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 16px; }
            .mobile-menu-btn { display: block; }
            .toolbar { flex-direction: column; }
            .search-box { max-width: 100%; }
            .data-table { font-size: 13px; }
            .data-table th, .data-table td { padding: 12px; }
        }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99; }
        .sidebar-overlay.show { display: block; }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="logo-icon">J</div>
                <div>
                    <div class="logo-text">Jay影视</div>
                    <div class="logo-sub">管理系统</div>
                </div>
            </div>
        </div>
        <nav>
            <a href="index.php" class="nav-item"><span class="nav-icon">📊</span><span>仪表盘</span></a>
            <a href="users.php" class="nav-item active"><span class="nav-icon">👥</span><span>用户管理</span></a>
            <a href="sources.php" class="nav-item"><span class="nav-icon">🎬</span><span>播放源管理</span></a>
            <a href="announcements.php" class="nav-item"><span class="nav-icon">📢</span><span>公告管理</span></a>
            <a href="feedback.php" class="nav-item"><span class="nav-icon">💬</span><span>反馈管理</span></a>
            <a href="email.php" class="nav-item"><span class="nav-icon">📧</span><span>邮件通知</span></a>
            <a href="theme.php" class="nav-item"><span class="nav-icon">🎨</span><span>主题设置</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <div>
                <h1 class="page-title">用户管理</h1>
                <p class="page-subtitle">管理系统用户，查看用户详情，处理违规行为</p>
            </div>
            <div class="top-bar-actions">
                <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
                <div class="admin-info">
                    <div class="admin-avatar">A</div>
                    <div>
                        <div class="admin-name"><?php echo $_SESSION['admin_username']; ?></div>
                        <div class="admin-role">超级管理员</div>
                    </div>
                </div>
                <a href="?logout=1" class="logout-btn">退出</a>
            </div>
        </div>

        <div class="toolbar">
            <div class="search-box">
                <input type="text" class="search-input" placeholder="搜索用户名或邮箱..." value="<?php echo htmlspecialchars($search); ?>" oninput="handleSearch(this.value)">
            </div>
            <select class="filter-select" onchange="handleStatusFilter(this.value)">
                <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>全部状态</option>
                <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>正常</option>
                <option value="banned" <?php echo $filterStatus === 'banned' ? 'selected' : ''; ?>>已封禁</option>
            </select>
        </div>

        <div class="card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>用户</th>
                        <th>邮箱</th>
                        <th>状态</th>
                        <th>注册时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pageUsers)): ?>
                    <tr><td colspan="5"><div class="empty-state"><div class="empty-icon">📭</div>暂无用户</div></td></tr>
                    <?php endif; ?>
                    <?php foreach ($pageUsers as $user): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar"><?php echo $user['avatar']; ?></div>
                                <span class="user-name-text"><?php echo $user['name']; ?></span>
                            </div>
                        </td>
                        <td class="user-email"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $user['status'] === 'active' ? 'status-active' : 'status-banned'; ?>">
                                <span class="status-dot"></span>
                                <?php echo $user['status'] === 'active' ? '正常' : '已封禁'; ?>
                                <?php if ($user['status'] === 'banned' && $user['bannedUntil']): ?>
                                (<?php echo $user['bannedUntil']; ?>)
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="user-email"><?php echo $user['created']; ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-view" onclick="openDetailModal(<?php echo $user['id']; ?>)">详情</button>
                                <?php if ($user['status'] === 'active'): ?>
                                <button class="btn btn-ban" onclick="openBanModal(<?php echo $user['id']; ?>, '<?php echo $user['name']; ?>')">封禁</button>
                                <?php else: ?>
                                <button class="btn btn-unban" onclick="unbanUser(<?php echo $user['id']; ?>)">解封</button>
                                <?php endif; ?>
                                <button class="btn btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo $user['name']; ?>')">删除</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="pagination">
                <div class="pagination-info">共 <?php echo $totalUsers; ?> 条记录，第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页</div>
                <div class="pagination-controls">
                    <button class="page-btn" <?php echo $page <= 1 ? 'disabled' : ''; ?> onclick="goPage(<?php echo $page - 1; ?>)">‹</button>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <button class="page-btn <?php echo $i === $page ? 'active' : ''; ?>" onclick="goPage(<?php echo $i; ?>)"><?php echo $i; ?></button>
                    <?php endfor; ?>
                    <button class="page-btn" <?php echo $page >= $totalPages ? 'disabled' : ''; ?> onclick="goPage(<?php echo $page + 1; ?>)">›</button>
                </div>
            </div>
        </div>
    </main>

    <div class="modal-overlay" id="banModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">封禁用户</h3>
                <button class="modal-close" onclick="closeModal('banModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="banner-info">正在封禁用户：<strong id="banUserName"></strong></div>
                <div class="form-group">
                    <label class="form-label">封禁时长</label>
                    <select class="form-select" id="banDuration">
                        <option value="1">1 天</option>
                        <option value="7">7 天</option>
                        <option value="30">30 天</option>
                        <option value="permanent">永久封禁</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">封禁原因</label>
                    <textarea class="form-textarea" id="banReason" placeholder="请输入封禁原因..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal('banModal')">取消</button>
                <button class="btn-primary" onclick="confirmBan()">确认封禁</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="detailModal">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">用户详情</h3>
                <button class="modal-close" onclick="closeModal('detailModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="tab-container">
                    <button class="tab-btn active" onclick="switchTab(event, 'info')">基本信息</button>
                    <button class="tab-btn" onclick="switchTab(event, 'favorites')">收藏</button>
                    <button class="tab-btn" onclick="switchTab(event, 'history')">观看历史</button>
                    <button class="tab-btn" onclick="switchTab(event, 'feedback')">反馈</button>
                </div>
                <div class="tab-content active" id="tab-info">
                    <div class="detail-item"><span class="detail-label">用户名</span><span class="detail-value" id="detailName"></span></div>
                    <div class="detail-item"><span class="detail-label">邮箱</span><span class="detail-value" id="detailEmail"></span></div>
                    <div class="detail-item"><span class="detail-label">状态</span><span class="detail-value" id="detailStatus"></span></div>
                    <div class="detail-item"><span class="detail-label">注册时间</span><span class="detail-value" id="detailCreated"></span></div>
                </div>
                <div class="tab-content" id="tab-favorites">
                    <div class="empty-state"><div class="empty-icon">⭐</div>收藏了 12 部影片</div>
                </div>
                <div class="tab-content" id="tab-history">
                    <div class="empty-state"><div class="empty-icon">🎬</div>观看历史 45 条记录</div>
                </div>
                <div class="tab-content" id="tab-feedback">
                    <div class="empty-state"><div class="empty-icon">💬</div>提交了 3 条反馈</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentBanUserId = null;
        const userData = <?php echo json_encode($users); ?>;

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }
        document.getElementById('sidebarOverlay').addEventListener('click', toggleSidebar);

        function handleSearch(val) {
            const params = new URLSearchParams(window.location.search);
            params.set('search', val);
            params.set('page', '1');
            window.location.search = params.toString();
        }
        function handleStatusFilter(val) {
            const params = new URLSearchParams(window.location.search);
            params.set('status', val);
            params.set('page', '1');
            window.location.search = params.toString();
        }
        function goPage(page) {
            const params = new URLSearchParams(window.location.search);
            params.set('page', page);
            window.location.search = params.toString();
        }

        function openBanModal(userId, userName) {
            currentBanUserId = userId;
            document.getElementById('banUserName').textContent = userName;
            document.getElementById('banReason').value = '';
            document.getElementById('banModal').classList.add('show');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }
        function confirmBan() {
            const reason = document.getElementById('banReason').value.trim();
            if (!reason) { alert('请输入封禁原因'); return; }
            alert('用户已被封禁');
            closeModal('banModal');
        }
        function unbanUser(userId) {
            if (confirm('确认解封此用户？')) { alert('用户已解封'); }
        }
        function deleteUser(userId, userName) {
            if (confirm('确认删除用户 ' + userName + '？此操作不可恢复！')) { alert('用户已删除'); }
        }

        function openDetailModal(userId) {
            const user = userData.find(u => u.id === userId);
            if (!user) return;
            document.getElementById('detailName').textContent = user.name;
            document.getElementById('detailEmail').textContent = user.email;
            document.getElementById('detailStatus').textContent = user.status === 'active' ? '正常' : '已封禁';
            document.getElementById('detailCreated').textContent = user.created;
            document.getElementById('detailModal').classList.add('show');
            switchTabByName('info');
        }
        function switchTab(event, tabName) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        }
        function switchTabByName(name) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-' + name).classList.add('active');
            document.querySelectorAll('.tab-btn').forEach(b => {
                if (b.getAttribute('onclick') && b.getAttribute('onclick').includes("'" + name + "'")) b.classList.add('active');
            });
        }
    </script>
</body>
</html>
