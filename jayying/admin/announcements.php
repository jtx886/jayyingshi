<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
$announcements = [
    ['id' => 1, 'title' => '网站版本更新通知 v2.0', 'content' => '尊敬的用户，Jay影视已升级至v2.0版本，新增了以下功能：\n1. 全新UI界面设计\n2. 支持多倍速播放\n3. 新增弹幕功能\n4. 优化播放体验\n\n感谢您的支持！', 'status' => 'enabled', 'created' => '2026-08-20 10:30'],
    ['id' => 2, 'title' => '服务器维护通知', 'content' => '为了提供更好的服务，我们将于本周六凌晨2:00-6:00进行服务器维护。期间网站可能无法访问，请提前保存您的观看进度。', 'status' => 'enabled', 'created' => '2026-08-18 15:00'],
    ['id' => 3, 'title' => '新电影上线公告', 'content' => '本周新上线影片：\n- 流浪地球3\n- 满江红\n- 消失的她\n\n快来观看吧！', 'status' => 'disabled', 'created' => '2026-08-15 09:20'],
    ['id' => 4, 'title' => '关于加强账号安全的通知', 'content' => '近期发现部分账号存在异常登录行为，建议您：\n1. 修改复杂密码\n2. 开启两步验证\n3. 不要在公共设备上保存登录状态', 'status' => 'disabled', 'created' => '2026-08-10 14:45'],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>公告管理 - Jay影视后台</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #1a1f2e; color: #e2e8f0; min-height: 100vh;
        }
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0; width: 240px;
            background: #252d3d; padding: 24px 0; overflow-y: auto;
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
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .add-btn { background: linear-gradient(135deg, #05d4c7, #03a89e); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .add-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(5,212,199,0.3); }
        .announcement-list { display: flex; flex-direction: column; gap: 16px; }
        .announcement-card {
            background: #252d3d; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.2s; overflow: hidden;
        }
        .announcement-card:hover { border-color: rgba(5,212,199,0.3); }
        .announcement-header { padding: 20px 24px; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
        .announcement-info { flex: 1; }
        .announcement-title { color: #fff; font-size: 16px; font-weight: 600; margin-bottom: 8px; }
        .announcement-content { color: #8b95a7; font-size: 14px; line-height: 1.6; white-space: pre-wrap; max-height: 60px; overflow: hidden; transition: max-height 0.3s; }
        .announcement-content.expanded { max-height: none; }
        .announcement-meta { display: flex; gap: 16px; margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.05); }
        .meta-item { color: #5a6478; font-size: 12px; }
        .status-switch { position: relative; width: 44px; height: 24px; cursor: pointer; flex-shrink: 0; }
        .status-switch input { display: none; }
        .switch-slider { position: absolute; inset: 0; background: #353f52; border-radius: 24px; transition: 0.3s; }
        .switch-slider:before { content: ''; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: 0.3s; }
        .status-switch input:checked + .switch-slider { background: #05d4c7; }
        .status-switch input:checked + .switch-slider:before { transform: translateX(20px); }
        .card-actions { display: flex; gap: 8px; padding: 0 24px 20px; }
        .btn { padding: 6px 12px; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-expand { background: transparent; color: #8b95a7; }
        .btn-expand:hover { color: #05d4c7; }
        .btn-edit { background: rgba(5,212,199,0.1); color: #05d4c7; }
        .btn-edit:hover { background: rgba(5,212,199,0.2); }
        .btn-delete { background: rgba(239,68,68,0.1); color: #ef4444; }
        .btn-delete:hover { background: rgba(239,68,68,0.2); }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 200; align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.show { display: flex; }
        .modal { background: #252d3d; border-radius: 16px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; }
        .modal-title { color: #fff; font-size: 18px; font-weight: 600; }
        .modal-close { background: none; border: none; color: #8b95a7; font-size: 24px; cursor: pointer; }
        .modal-body { padding: 24px; }
        .modal-footer { padding: 20px 24px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: flex-end; gap: 12px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; color: #8b95a7; font-size: 13px; margin-bottom: 8px; font-weight: 500; }
        .form-input { width: 100%; padding: 12px 14px; background: #1a1f2e; border: 1px solid #353f52; border-radius: 8px; color: #fff; font-size: 14px; outline: none; }
        .form-input:focus { border-color: #05d4c7; box-shadow: 0 0 0 3px rgba(5,212,199,0.15); }
        .form-textarea { width: 100%; padding: 12px 14px; background: #1a1f2e; border: 1px solid #353f52; border-radius: 8px; color: #fff; font-size: 14px; outline: none; resize: vertical; min-height: 150px; font-family: inherit; line-height: 1.6; }
        .form-textarea:focus { border-color: #05d4c7; box-shadow: 0 0 0 3px rgba(5,212,199,0.15); }
        .btn-primary { background: linear-gradient(135deg, #05d4c7, #03a89e); color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(5,212,199,0.3); }
        .btn-secondary { background: transparent; color: #8b95a7; border: 1px solid #353f52; padding: 10px 24px; border-radius: 8px; font-size: 14px; cursor: pointer; transition: all 0.2s; }
        .btn-secondary:hover { color: #fff; border-color: #8b95a7; }
        .empty-state { text-align: center; padding: 40px; color: #5a6478; }
        .empty-icon { font-size: 48px; margin-bottom: 12px; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 16px; }
            .mobile-menu-btn { display: block; }
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
            <a href="users.php" class="nav-item"><span class="nav-icon">👥</span><span>用户管理</span></a>
            <a href="sources.php" class="nav-item"><span class="nav-icon">🎬</span><span>播放源管理</span></a>
            <a href="announcements.php" class="nav-item active"><span class="nav-icon">📢</span><span>公告管理</span></a>
            <a href="feedback.php" class="nav-item"><span class="nav-icon">💬</span><span>反馈管理</span></a>
            <a href="email.php" class="nav-item"><span class="nav-icon">📧</span><span>邮件通知</span></a>
            <a href="theme.php" class="nav-item"><span class="nav-icon">🎨</span><span>主题设置</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <div>
                <h1 class="page-title">公告管理</h1>
                <p class="page-subtitle">发布和管理系统公告，通知用户重要信息</p>
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

        <div class="action-bar">
            <div style="color:#8b95a7; font-size:14px;">共 <?php echo count($announcements); ?> 条公告</div>
            <button class="add-btn" onclick="openAddModal()">+ 发布公告</button>
        </div>

        <?php if (empty($announcements)): ?>
        <div class="empty-state"><div class="empty-icon">📢</div>暂无公告，点击上方按钮发布第一条公告</div>
        <?php else: ?>
        <div class="announcement-list">
            <?php foreach ($announcements as $ann): ?>
            <div class="announcement-card">
                <div class="announcement-header">
                    <div class="announcement-info">
                        <div class="announcement-title"><?php echo htmlspecialchars($ann['title']); ?></div>
                        <div class="announcement-content" id="content-<?php echo $ann['id']; ?>"><?php echo htmlspecialchars($ann['content']); ?></div>
                        <div class="announcement-meta">
                            <span class="meta-item">📅 <?php echo $ann['created']; ?></span>
                            <span class="meta-item">Status: <?php echo $ann['status'] === 'enabled' ? '已启用' : '已禁用'; ?></span>
                        </div>
                    </div>
                    <label class="status-switch">
                        <input type="checkbox" <?php echo $ann['status'] === 'enabled' ? 'checked' : ''; ?> onchange="toggleAnnStatus(<?php echo $ann['id']; ?>)">
                        <span class="switch-slider"></span>
                    </label>
                </div>
                <div class="card-actions">
                    <button class="btn btn-expand" onclick="toggleContent(<?php echo $ann['id']; ?>)">展开/收起</button>
                    <button class="btn btn-edit" onclick="openEditModal(<?php echo $ann['id']; ?>)">编辑</button>
                    <button class="btn btn-delete" onclick="deleteAnn(<?php echo $ann['id']; ?>, '<?php echo htmlspecialchars($ann['title']); ?>')">删除</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

    <div class="modal-overlay" id="annModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">发布公告</h3>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">公告标题</label>
                    <input type="text" class="form-input" id="annTitle" placeholder="请输入公告标题" required>
                </div>
                <div class="form-group">
                    <label class="form-label">公告内容</label>
                    <textarea class="form-textarea" id="annContent" placeholder="请输入公告内容，支持多行文本..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal()">取消</button>
                <button class="btn-primary" onclick="saveAnn()">保存发布</button>
            </div>
        </div>
    </div>

    <script>
        const annData = <?php echo json_encode($announcements); ?>;
        let editingId = null;

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }
        document.getElementById('sidebarOverlay').addEventListener('click', toggleSidebar);

        function openAddModal() {
            editingId = null;
            document.getElementById('modalTitle').textContent = '发布公告';
            document.getElementById('annTitle').value = '';
            document.getElementById('annContent').value = '';
            document.getElementById('annModal').classList.add('show');
        }
        function openEditModal(id) {
            const ann = annData.find(a => a.id === id);
            if (!ann) return;
            editingId = id;
            document.getElementById('modalTitle').textContent = '编辑公告';
            document.getElementById('annTitle').value = ann.title;
            document.getElementById('annContent').value = ann.content;
            document.getElementById('annModal').classList.add('show');
        }
        function closeModal() {
            document.getElementById('annModal').classList.remove('show');
        }
        function saveAnn() {
            const title = document.getElementById('annTitle').value.trim();
            const content = document.getElementById('annContent').value.trim();
            if (!title || !content) { alert('请填写标题和内容'); return; }
            alert(editingId ? '公告已更新' : '公告已发布');
            closeModal();
        }
        function toggleContent(id) {
            document.getElementById('content-' + id).classList.toggle('expanded');
        }
        function toggleAnnStatus(id) {
            alert('公告状态已切换，ID: ' + id);
        }
        function deleteAnn(id, title) {
            if (confirm('确认删除公告 "' + title + '"？此操作不可恢复！')) { alert('公告已删除'); }
        }
    </script>
</body>
</html>
