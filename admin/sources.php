<?php
$adminActivePage = 'sources';
$adminTitle = '播放源管理';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/db.php';

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $sort = intval($_POST['sort_order'] ?? 0);
        $status = intval($_POST['status'] ?? 1);
        if ($name && $url) {
            $db->insert('play_sources', array('name' => $name, 'url' => $url, 'sort_order' => $sort, 'status' => $status));
            redirect('sources.php?msg=' . urlencode('播放源已添加') . '&t=success');
        } else {
            $msg = '请填写完整';
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $sort = intval($_POST['sort_order'] ?? 0);
        $status = intval($_POST['status'] ?? 1);
        if ($name && $url && $id) {
            $db->update('play_sources', array('name' => $name, 'url' => $url, 'sort_order' => $sort, 'status' => $status), 'id = ?', array($id));
            redirect('sources.php?msg=' . urlencode('已保存修改') . '&t=success');
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $db->delete('play_sources', 'id = ?', array($id));
        redirect('sources.php?msg=' . urlencode('已删除') . '&t=success');
    }
}

$sources = $db->fetchAll("SELECT * FROM play_sources ORDER BY sort_order ASC, id ASC");
showAlert();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect width="15" height="14" x="1" y="5" rx="2" ry="2"/></svg>
            添加/编辑播放源
        </div>
    </div>
    <form method="POST" style="max-width: 700px;">
        <input type="hidden" name="action" value="add" id="sourceFormAction">
        <input type="hidden" name="id" id="sourceId">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">播放源名称</label>
                <input type="text" class="form-input" id="sourceName" name="name" placeholder="如：主播放源、备用线路1" required>
            </div>
            <div class="form-group">
                <label class="form-label">排序（数字越小越靠前）</label>
                <input type="number" class="form-input" id="sourceSort" name="sort_order" value="0">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">播放源API地址</label>
            <input type="url" class="form-input" id="sourceUrl" name="url" placeholder="如：https://api.yyzy-tv.vip/inc/apijson.php" required>
        </div>
        <div class="form-group">
            <label class="form-label">状态</label>
            <select name="status" id="sourceStatus" class="form-select" style="max-width:200px;">
                <option value="1">启用</option>
                <option value="0">禁用</option>
            </select>
        </div>
        <div style="display:flex; gap: 12px; flex-wrap: wrap;">
            <button class="btn btn-primary" id="submitBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                添加播放源
            </button>
            <button type="button" class="btn btn-outline" id="cancelBtn" style="display:none;" onclick="resetForm()">取消编辑</button>
        </div>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            已添加的播放源
        </div>
    </div>
    <?php if (empty($sources)): ?>
        <div class="empty-state"><div class="empty-state-title">还没有播放源</div><div class="empty-state-desc">请先在上方添加</div></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>ID</th><th>名称</th><th>API地址</th><th>排序</th><th>状态</th><th style="text-align:right;">操作</th></tr></thead>
        <tbody>
            <?php foreach ($sources as $s): ?>
            <tr>
                <td>#<?php echo $s['id']; ?></td>
                <td><strong><?php echo e($s['name']); ?></strong></td>
                <td style="max-width:360px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--text-muted); font-size:12px;" title="<?php echo e($s['url']); ?>"><?php echo e($s['url']); ?></td>
                <td><?php echo intval($s['sort_order']); ?></td>
                <td><?php echo $s['status'] ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-danger">禁用</span>'; ?></td>
                <td style="text-align:right;">
                    <div class="table-actions" style="justify-content:flex-end;">
                        <button class="btn btn-sm btn-outline" onclick="editSource(<?php echo $s['id']; ?>, <?php echo json_encode($s['name']); ?>, <?php echo json_encode($s['url']); ?>, <?php echo intval($s['sort_order']); ?>, <?php echo intval($s['status']); ?>)">编辑</button>
                        <button class="btn btn-sm btn-danger" onclick="delSource(<?php echo $s['id']; ?>)">删除</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<script>
    function editSource(id, name, url, sort, status) {
        document.getElementById('sourceFormAction').value = 'update';
        document.getElementById('sourceId').value = id;
        document.getElementById('sourceName').value = name;
        document.getElementById('sourceUrl').value = url;
        document.getElementById('sourceSort').value = sort;
        document.getElementById('sourceStatus').value = status;
        document.getElementById('submitBtn').innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>保存修改';
        document.getElementById('cancelBtn').style.display = '';
        window.scrollTo({top: 0, behavior: 'smooth'});
    }
    function resetForm() {
        document.getElementById('sourceFormAction').value = 'add';
        document.getElementById('sourceId').value = '';
        document.getElementById('sourceName').value = '';
        document.getElementById('sourceUrl').value = '';
        document.getElementById('sourceSort').value = 0;
        document.getElementById('sourceStatus').value = 1;
        document.getElementById('submitBtn').innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>添加播放源';
        document.getElementById('cancelBtn').style.display = 'none';
    }
    function delSource(id) {
        confirmDialog('确认删除该播放源？').then(r=>{
            if (!r) return;
            var f = document.createElement('form'); f.method='POST';
            f.innerHTML = '<input name="action" value="delete"><input name="id" value="'+id+'">';
            document.body.appendChild(f); f.submit();
        });
    }
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
