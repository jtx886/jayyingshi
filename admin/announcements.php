<?php
$adminActivePage = 'announcements';
$adminTitle = '公告管理';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        if ($title && $content) {
            $db->insert('announcements', array(
                'title' => $title,
                'content' => $content,
                'created_by' => $_SESSION['user_id']
            ));
            // 清除所有用户的已读记录（新公告）
            $newId = $db->getConnection()->lastInsertId();
            $db->delete('announcement_dismissed', 'announcement_id = ?', array($newId));
            // 重置游客已读
            unset($_SESSION['guest_seen_ann']);
            redirect('announcements.php?msg=' . urlencode('公告已发布') . '&t=success');
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        if ($id && $title && $content) {
            $db->update('announcements', array('title' => $title, 'content' => $content), 'id = ?', array($id));
            $db->delete('announcement_dismissed', 'announcement_id = ?', array($id));
            redirect('announcements.php?msg=' . urlencode('公告已更新，所有用户将重新看到') . '&t=success');
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $db->delete('announcements', 'id = ?', array($id));
        redirect('announcements.php?msg=' . urlencode('已删除') . '&t=success');
    }
}

$list = $db->fetchAll("SELECT a.*, u.username FROM announcements a LEFT JOIN users u ON u.id=a.created_by ORDER BY a.id DESC");
showAlert();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title" id="formTitle">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            发布新公告
        </div>
    </div>
    <div style="margin-bottom: 14px; color: var(--text-secondary); font-size: 14px;">💡 提示：公告会在用户每次进入首页时弹窗显示，除非用户勾选「不再提示」。修改或新增公告都会重置所有用户的「不再提示」状态。</div>
    <form method="POST">
        <input type="hidden" name="action" id="annAction" value="add">
        <input type="hidden" name="id" id="annId">
        <div class="form-group">
            <label class="form-label">公告标题</label>
            <input type="text" name="title" id="annTitle" class="form-input" placeholder="如：系统升级通知、新片上线通知" required>
        </div>
        <div class="form-group">
            <label class="form-label">公告内容</label>
            <textarea name="content" id="annContent" class="form-textarea" placeholder="填写公告正文内容..." style="min-height: 140px;" required></textarea>
        </div>
        <div style="display:flex; gap: 12px; flex-wrap: wrap;">
            <button class="btn btn-primary" id="annBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                发布公告
            </button>
            <button type="button" class="btn btn-outline" id="cancelEditBtn" style="display:none;" onclick="resetForm()">取消编辑</button>
        </div>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            已发布公告 (共 <?php echo count($list); ?> 条)
        </div>
    </div>
    <?php if (empty($list)): ?>
        <div class="empty-state"><div class="empty-state-title">还没有公告</div><div class="empty-state-desc">发布第一个公告吧！</div></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>ID</th><th>标题</th><th>内容预览</th><th>发布人</th><th>发布时间</th><th style="text-align:right;">操作</th></tr></thead>
        <tbody>
            <?php foreach ($list as $a): ?>
            <tr>
                <td>#<?php echo $a['id']; ?></td>
                <td><strong><?php echo e($a['title']); ?></strong></td>
                <td style="max-width:360px; color:var(--text-muted);"><?php echo e(mb_substr($a['content'], 0, 60)); ?><?php echo mb_strlen($a['content']) > 60 ? '...' : ''; ?></td>
                <td><?php echo e($a['username']); ?></td>
                <td style="color:var(--text-muted); font-size:13px;"><?php echo e(date('Y-m-d H:i', strtotime($a['created_at']))); ?></td>
                <td style="text-align:right;">
                    <div class="table-actions" style="justify-content:flex-end;">
                        <button class="btn btn-sm btn-outline" onclick="editAnn(<?php echo $a['id']; ?>, <?php echo json_encode($a['title']); ?>, <?php echo json_encode($a['content']); ?>)">编辑</button>
                        <button class="btn btn-sm btn-danger" onclick="delAnn(<?php echo $a['id']; ?>)">删除</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<script>
    function editAnn(id, title, content) {
        document.getElementById('annAction').value = 'update';
        document.getElementById('annId').value = id;
        document.getElementById('annTitle').value = title;
        document.getElementById('annContent').value = content;
        document.getElementById('formTitle').innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>编辑公告 #' + id;
        document.getElementById('annBtn').innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>保存修改';
        document.getElementById('cancelEditBtn').style.display = '';
        window.scrollTo({top: 0, behavior: 'smooth'});
    }
    function resetForm() {
        document.getElementById('annAction').value = 'add';
        document.getElementById('annId').value = '';
        document.getElementById('annTitle').value = '';
        document.getElementById('annContent').value = '';
        document.getElementById('formTitle').innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>发布新公告';
        document.getElementById('annBtn').innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>发布公告';
        document.getElementById('cancelEditBtn').style.display = 'none';
    }
    function delAnn(id) {
        confirmDialog('确认删除该公告？').then(r=>{
            if (!r) return;
            var f = document.createElement('form'); f.method='POST';
            f.innerHTML = '<input name="action" value="delete"><input name="id" value="'+id+'">';
            document.body.appendChild(f); f.submit();
        });
    }
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
