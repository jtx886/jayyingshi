<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
$feedbacks = [
    ['id' => 1, 'user' => '张小明', 'userEmail' => 'zhangxm@example.com', 'content' => '播放源加载速度有点慢，希望能优化一下。特别是在播放高清电影时，缓冲时间过长。建议增加CDN节点或优化视频压缩算法。', 'time' => '2026-08-24 10:30', 'status' => 'pending', 'reply' => ''],
    ['id' => 2, 'user' => '李婷婷', 'userEmail' => 'litt@example.com', 'content' => '电影资源很全，界面也很美观，点赞！期待更多精彩内容。', 'time' => '2026-08-23 14:20', 'status' => 'replied', 'reply' => '感谢您的支持！我们将继续努力为您提供更好的服务。'],
    ['id' => 3, 'user' => '王大壮', 'userEmail' => 'wangdz@example.com', 'content' => '建议增加夜间模式自动切换功能，或者提供定时切换选项。另外，希望能增加播放列表功能，可以连续播放多部影片。', 'time' => '2026-08-22 16:45', 'status' => 'pending', 'reply' => ''],
    ['id' => 4, 'user' => '赵雪琪', 'userEmail' => 'zhaoxq@example.com', 'content' => '部分老电影画质不太清楚，能否提供高清版本？或者增加画质升级选项。', 'time' => '2026-08-21 09:15', 'status' => 'replied', 'reply' => '感谢反馈，我们正在逐步更新老电影的画质，敬请期待！'],
    ['id' => 5, 'user' => '陈思远', 'userEmail' => 'chensy@example.com', 'content' => '搜索功能不太好用，建议增加多条件筛选和排序功能。', 'time' => '2026-08-20 20:30', 'status' => 'pending', 'reply' => ''],
    ['id' => 6, 'user' => '刘芳', 'userEmail' => 'liuf@example.com', 'content' => 'App的推送通知有时会延迟，希望能优化推送服务的实时性。', 'time' => '2026-08-19 11:00', 'status' => 'pending', 'reply' => ''],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>反馈管理 - Jay影视后台</title>
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
        .filter-bar { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; align-items: center; }
        .filter-tabs { display: flex; gap: 8px; }
        .filter-tab { padding: 8px 16px; background: #252d3d; border: 1px solid #353f52; border-radius: 8px; color: #8b95a7; font-size: 13px; cursor: pointer; transition: all 0.2s; }
        .filter-tab.active { background: rgba(5,212,199,0.1); border-color: #05d4c7; color: #05d4c7; }
        .filter-count { color: #5a6478; font-size: 12px; margin-left: 4px; }
        .feedback-list { display: flex; flex-direction: column; gap: 16px; }
        .feedback-card {
            background: #252d3d; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.2s;
        }
        .feedback-card:hover { border-color: rgba(5,212,199,0.3); }
        .feedback-header { padding: 20px 24px; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
        .feedback-user { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: #353f52; display: flex; align-items: center; justify-content: center; color: #8b95a7; font-weight: 600; font-size: 14px; }
        .user-info { }
        .user-name { color: #fff; font-size: 14px; font-weight: 500; }
        .user-email { color: #5a6478; font-size: 12px; }
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .status-pending { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .status-replied { background: rgba(16,185,129,0.1); color: #10b981; }
        .status-dot { width: 6px; height: 6px; border-radius: 50%; }
        .status-pending .status-dot { background: #f59e0b; }
        .status-replied .status-dot { background: #10b981; }
        .feedback-body { padding: 0 24px 20px; }
        .feedback-content { color: #8b95a7; font-size: 14px; line-height: 1.7; }
        .feedback-reply { margin-top: 16px; padding: 16px; background: rgba(5,212,199,0.05); border-radius: 8px; border-left: 3px solid #05d4c7; }
        .reply-label { color: #05d4c7; font-size: 12px; font-weight: 600; margin-bottom: 8px; }
        .reply-content { color: #e2e8f0; font-size: 14px; line-height: 1.6; }
        .feedback-footer { padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; }
        .feedback-time { color: #5a6478; font-size: 12px; }
        .card-actions { display: flex; gap: 8px; }
        .btn { padding: 6px 12px; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-reply { background: rgba(5,212,199,0.1); color: #05d4c7; }
        .btn-reply:hover { background: rgba(5,212,199,0.2); }
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
        .form-textarea { width: 100%; padding: 12px 14px; background: #1a1f2e; border: 1px solid #353f52; border-radius: 8px; color: #fff; font-size: 14px; outline: none; resize: vertical; min-height: 120px; font-family: inherit; line-height: 1.6; }
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
            <a href="announcements.php" class="nav-item"><span class="nav-icon">📢</span><span>公告管理</span></a>
            <a href="feedback.php" class="nav-item active"><span class="nav-icon">💬</span><span>反馈管理</span></a>
            <a href="email.php" class="nav-item"><span class="nav-icon">📧</span><span>邮件通知</span></a>
            <a href="theme.php" class="nav-item"><span class="nav-icon">🎨</span><span>主题设置</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <div>
                <h1 class="page-title">反馈管理</h1>
                <p class="page-subtitle">查看和回复用户反馈</p>
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

        <div class="filter-bar">
            <div class="filter-tabs">
                <div class="filter-tab active" data-filter="all" onclick="setFilter('all', this)">全部 <span class="filter-count">(<?php echo count($feedbacks); ?>)</span></div>
                <div class="filter-tab" data-filter="pending" onclick="setFilter('pending', this)">待处理 <span class="filter-count">(<?php echo count(array_filter($feedbacks, fn($f) => $f['status'] === 'pending')); ?>)</span></div>
                <div class="filter-tab" data-filter="replied" onclick="setFilter('replied', this)">已回复 <span class="filter-count">(<?php echo count(array_filter($feedbacks, fn($f) => $f['status'] === 'replied')); ?>)</span></div>
            </div>
        </div>

        <div class="feedback-list" id="feedbackList">
            <?php foreach ($feedbacks as $fb): ?>
            <div class="feedback-card" data-status="<?php echo $fb['status']; ?>">
                <div class="feedback-header">
                    <div class="feedback-user">
                        <div class="user-avatar"><?php echo mb_substr($fb['user'], 0, 1); ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($fb['user']); ?></div>
                            <div class="user-email"><?php echo htmlspecialchars($fb['userEmail']); ?></div>
                        </div>
                    </div>
                    <span class="status-badge <?php echo $fb['status'] === 'pending' ? 'status-pending' : 'status-replied'; ?>">
                        <span class="status-dot"></span>
                        <?php echo $fb['status'] === 'pending' ? '待处理' : '已回复'; ?>
                    </span>
                </div>
                <div class="feedback-body">
                    <div class="feedback-content"><?php echo nl2br(htmlspecialchars($fb['content'])); ?></div>
                    <?php if (!empty($fb['reply'])): ?>
                    <div class="feedback-reply">
                        <div class="reply-label">管理员回复</div>
                        <div class="reply-content"><?php echo nl2br(htmlspecialchars($fb['reply'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="feedback-footer">
                    <span class="feedback-time"><?php echo $fb['time']; ?></span>
                    <div class="card-actions">
                        <button class="btn btn-reply" onclick="openReplyModal(<?php echo $fb['id']; ?>)">
                            <?php echo $fb['status'] === 'replied' ? '编辑回复' : '回复'; ?>
                        </button>
                        <button class="btn btn-delete" onclick="deleteFeedback(<?php echo $fb['id']; ?>)">删除</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="empty-state" id="emptyState" style="display:none;">
            <div class="empty-icon">📭</div>暂无反馈
        </div>
    </main>

    <div class="modal-overlay" id="replyModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">回复反馈</h3>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">用户反馈内容</label>
                    <div id="replyContext" style="background:#1a1f2e; padding:12px; border-radius:8px; color:#8b95a7; font-size:14px; line-height:1.6;"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">管理员回复</label>
                    <textarea class="form-textarea" id="replyContent" placeholder="请输入回复内容..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal()">取消</button>
                <button class="btn-primary" onclick="submitReply()">发送回复</button>
            </div>
        </div>
    </div>

    <script>
        const fbData = <?php echo json_encode($feedbacks); ?>;
        let replyingId = null;
        let currentFilter = 'all';

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }
        document.getElementById('sidebarOverlay').addEventListener('click', toggleSidebar);

        function setFilter(filter, el) {
            currentFilter = filter;
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
            document.querySelectorAll('.feedback-card').forEach(card => {
                if (filter === 'all' || card.dataset.status === filter) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
            const visible = document.querySelectorAll('.feedback-card[style*="display: block"], .feedback-card:not([style*="display: none"])');
            document.getElementById('emptyState').style.display = visible.length === 0 ? 'block' : 'none';
        }

        function openReplyModal(id) {
            const fb = fbData.find(f => f.id === id);
            if (!fb) return;
            replyingId = id;
            document.getElementById('replyContext').textContent = fb.content;
            document.getElementById('replyContent').value = fb.reply || '';
            document.getElementById('replyModal').classList.add('show');
        }
        function closeModal() {
            document.getElementById('replyModal').classList.remove('show');
        }
        function submitReply() {
            const reply = document.getElementById('replyContent').value.trim();
            if (!reply) { alert('请输入回复内容'); return; }
            alert('回复已发送，ID: ' + replyingId);
            closeModal();
        }
        function deleteFeedback(id) {
            if (confirm('确认删除此反馈？此操作不可恢复！')) { alert('反馈已删除'); }
        }
    </script>
</body>
</html>
