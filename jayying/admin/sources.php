<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
$sources = [
    ['id' => 1, 'name' => '腾讯视频', 'url' => 'https://v.qq.com', 'enabled' => true, 'isDefault' => true],
    ['id' => 2, 'name' => '爱奇艺', 'url' => 'https://www.iqiyi.com', 'enabled' => true, 'isDefault' => false],
    ['id' => 3, 'name' => '优酷', 'url' => 'https://www.youku.com', 'enabled' => true, 'isDefault' => false],
    ['id' => 4, 'name' => '芒果TV', 'url' => 'https://www.mgtv.com', 'enabled' => false, 'isDefault' => false],
    ['id' => 5, 'name' => '哔哩哔哩', 'url' => 'https://www.bilibili.com', 'enabled' => true, 'isDefault' => false],
    ['id' => 6, 'name' => '搜狐视频', 'url' => 'https://tv.sohu.com', 'enabled' => false, 'isDefault' => false],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>播放源管理 - Jay影视后台</title>
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
        .card { background: #252d3d; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); overflow: hidden; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 16px 20px; color: #8b95a7; font-size: 13px; font-weight: 500; background: rgba(255,255,255,0.02); }
        .data-table td { padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover { background: rgba(255,255,255,0.02); }
        .source-name { color: #fff; font-weight: 500; }
        .source-url { color: #8b95a7; font-size: 13px; margin-top: 4px; word-break: break-all; }
        .status-switch { position: relative; width: 44px; height: 24px; cursor: pointer; }
        .status-switch input { display: none; }
        .switch-slider { position: absolute; inset: 0; background: #353f52; border-radius: 24px; transition: 0.3s; }
        .switch-slider:before { content: ''; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: 0.3s; }
        .status-switch input:checked + .switch-slider { background: #05d4c7; }
        .status-switch input:checked + .switch-slider:before { transform: translateX(20px); }
        .action-buttons { display: flex; gap: 8px; }
        .btn { padding: 6px 12px; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-edit { background: rgba(5,212,199,0.1); color: #05d4c7; }
        .btn-edit:hover { background: rgba(5,212,199,0.2); }
        .btn-default { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .btn-default:hover { background: rgba(245,158,11,0.2); }
        .btn-delete { background: rgba(239,68,68,0.1); color: #ef4444; }
        .btn-delete:hover { background: rgba(239,68,68,0.2); }
        .default-badge { display: inline-flex; align-items: center; gap: 4px; background: rgba(245,158,11,0.1); color: #f59e0b; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 8px; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 200; align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.show { display: flex; }
        .modal { background: #252d3d; border-radius: 16px; width: 100%; max-width: 500px; }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; }
        .modal-title { color: #fff; font-size: 18px; font-weight: 600; }
        .modal-close { background: none; border: none; color: #8b95a7; font-size: 24px; cursor: pointer; }
        .modal-body { padding: 24px; }
        .modal-footer { padding: 20px 24px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: flex-end; gap: 12px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; color: #8b95a7; font-size: 13px; margin-bottom: 8px; font-weight: 500; }
        .form-input { width: 100%; padding: 12px 14px; background: #1a1f2e; border: 1px solid #353f52; border-radius: 8px; color: #fff; font-size: 14px; outline: none; }
        .form-input:focus { border-color: #05d4c7; box-shadow: 0 0 0 3px rgba(5,212,199,0.15); }
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
            <a href="users.php" class="nav-item"><span class="nav-icon">👥</span><span>用户管理</span></a>
            <a href="sources.php" class="nav-item active"><span class="nav-icon">🎬</span><span>播放源管理</span></a>
            <a href="announcements.php" class="nav-item"><span class="nav-icon">📢</span><span>公告管理</span></a>
            <a href="feedback.php" class="nav-item"><span class="nav-icon">💬</span><span>反馈管理</span></a>
            <a href="email.php" class="nav-item"><span class="nav-icon">📧</span><span>邮件通知</span></a>
            <a href="theme.php" class="nav-item"><span class="nav-icon">🎨</span><span>主题设置</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <div>
                <h1 class="page-title">播放源管理</h1>
                <p class="page-subtitle">管理影视播放源，设置默认播放源</p>
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
            <div style="color:#8b95a7; font-size:14px;">共 <?php echo count($sources); ?> 个播放源</div>
            <button class="add-btn" onclick="openAddModal()">+ 添加播放源</button>
        </div>

        <div class="card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>名称</th>
                        <th>URL地址</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sources as $source): ?>
                    <tr>
                        <td>
                            <div class="source-name">
                                <?php echo htmlspecialchars($source['name']); ?>
                                <?php if ($source['isDefault']): ?>
                                <span class="default-badge">默认</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><div class="source-url"><?php echo htmlspecialchars($source['url']); ?></div></td>
                        <td>
                            <label class="status-switch">
                                <input type="checkbox" <?php echo $source['enabled'] ? 'checked' : ''; ?> onchange="toggleStatus(<?php echo $source['id']; ?>)">
                                <span class="switch-slider"></span>
                            </label>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-edit" onclick="openEditModal(<?php echo $source['id']; ?>)">编辑</button>
                                <?php if (!$source['isDefault']): ?>
                                <button class="btn btn-default" onclick="setDefault(<?php echo $source['id']; ?>)">设为默认</button>
                                <?php endif; ?>
                                <button class="btn btn-delete" onclick="deleteSource(<?php echo $source['id']; ?>, '<?php echo htmlspecialchars($source['name']); ?>')">删除</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div class="modal-overlay" id="sourceModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">添加播放源</h3>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">名称</label>
                    <input type="text" class="form-input" id="sourceName" placeholder="请输入播放源名称" required>
                </div>
                <div class="form-group">
                    <label class="form-label">URL地址</label>
                    <input type="url" class="form-input" id="sourceUrl" placeholder="https://example.com" required>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal()">取消</button>
                <button class="btn-primary" onclick="saveSource()">保存</button>
            </div>
        </div>
    </div>

    <script>
        const sourceData = <?php echo json_encode($sources); ?>;
        let editingId = null;

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }
        document.getElementById('sidebarOverlay').addEventListener('click', toggleSidebar);

        function openAddModal() {
            editingId = null;
            document.getElementById('modalTitle').textContent = '添加播放源';
            document.getElementById('sourceName').value = '';
            document.getElementById('sourceUrl').value = '';
            document.getElementById('sourceModal').classList.add('show');
        }
        function openEditModal(id) {
            const source = sourceData.find(s => s.id === id);
            if (!source) return;
            editingId = id;
            document.getElementById('modalTitle').textContent = '编辑播放源';
            document.getElementById('sourceName').value = source.name;
            document.getElementById('sourceUrl').value = source.url;
            document.getElementById('sourceModal').classList.add('show');
        }
        function closeModal() {
            document.getElementById('sourceModal').classList.remove('show');
        }
        function saveSource() {
            const name = document.getElementById('sourceName').value.trim();
            const url = document.getElementById('sourceUrl').value.trim();
            if (!name || !url) { alert('请填写完整信息'); return; }
            alert(editingId ? '播放源已更新' : '播放源已添加');
            closeModal();
        }
        function toggleStatus(id) {
            alert('状态已切换，ID: ' + id);
        }
        function setDefault(id) {
            if (confirm('确认将此播放源设为默认？')) { alert('默认播放源已更新'); }
        }
        function deleteSource(id, name) {
            if (confirm('确认删除播放源 "' + name + '"？此操作不可恢复！')) { alert('播放源已删除'); }
        }
    </script>
</body>
</html>
