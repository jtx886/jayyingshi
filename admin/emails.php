<?php
$adminActivePage = 'emails';
$adminTitle = '邮件通知';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/email.php';

$db = Database::getInstance();

$msg = '';
$msgType = 'info';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'send_single') {
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $userId = intval($_POST['user_id'] ?? 0);
        if (!$email || !$subject || !$content) {
            $msg = '请填写完整信息'; $msgType = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = '邮箱格式不正确'; $msgType = 'error';
        } else {
            $html = Email::getEmailTemplate($subject, '
                <div class="title">' . htmlspecialchars($subject) . '</div>
                <div style="margin-top:10px; line-height:1.9;">' . nl2br(htmlspecialchars($content)) . '</div>
            ');
            $sent = @Email::send($email, '【Jay影视】' . $subject, $html, 'admin_notice', $userId ?: null);
            $msg = $sent ? '邮件已发送成功' : '邮件发送失败，请检查SMTP配置';
            $msgType = $sent ? 'success' : 'error';
        }
    } elseif ($action === 'send_all') {
        $subject = trim($_POST['all_subject'] ?? '');
        $content = trim($_POST['all_content'] ?? '');
        if (!$subject || !$content) {
            $msg = '请填写完整'; $msgType = 'error';
        } else {
            $users = $db->fetchAll("SELECT id, email, username FROM users WHERE email IS NOT NULL AND email != ''");
            $total = 0; $ok = 0;
            foreach ($users as $u) {
                $total++;
                $html = Email::getEmailTemplate($subject, '
                    <div style="margin-bottom:12px;">亲爱的 <strong>' . htmlspecialchars($u['username']) . '</strong>，您好！</div>
                    <div class="title">' . htmlspecialchars($subject) . '</div>
                    <div style="margin-top:10px; line-height:1.9;">' . nl2br(htmlspecialchars($content)) . '</div>
                ');
                if (@Email::send($u['email'], '【Jay影视】' . $subject, $html, 'admin_broadcast', $u['id'])) $ok++;
            }
            $msg = "批量发送完成：成功 {$ok} / 总 {$total}";
            $msgType = $ok > 0 ? 'success' : 'warning';
        }
    }
}

$users = $db->fetchAll("SELECT id, username, email FROM users WHERE is_admin = 0 ORDER BY id DESC LIMIT 200");
$logs = $db->fetchAll("SELECT * FROM email_logs ORDER BY id DESC LIMIT 50");

if ($msg) {
    $tmap = array('success'=>'success','error'=>'error','warning'=>'warning','info'=>'info');
    redirect('emails.php?msg=' . urlencode($msg) . '&t=' . ($tmap[$msgType] ?? 'info'));
}
showAlert();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            给指定用户发送邮件通知
        </div>
    </div>
    <form method="POST" style="max-width: 720px;">
        <input type="hidden" name="action" value="send_single">
        <div class="form-group">
            <label class="form-label">选择用户 / 或填写邮箱</label>
            <select class="form-select" id="userSelect" style="margin-bottom: 10px;" onchange="document.getElementById('emailInput').value = this.value ? this.options[this.selectedIndex].getAttribute('data-email') : ''; document.getElementById('userIdInput').value = this.value || 0;">
                <option value="">-- 选择用户（自动填充邮箱）--</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?php echo $u['id']; ?>" data-email="<?php echo e($u['email']); ?>"><?php echo e($u['username']); ?> (<?php echo e($u['email']); ?>)</option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="user_id" id="userIdInput" value="0">
            <input type="email" name="email" id="emailInput" class="form-input" placeholder="或直接输入邮箱地址" required>
        </div>
        <div class="form-group">
            <label class="form-label">邮件主题</label>
            <input type="text" name="subject" class="form-input" placeholder="邮件标题" required>
        </div>
        <div class="form-group">
            <label class="form-label">邮件内容（支持换行）</label>
            <textarea name="content" class="form-textarea" placeholder="输入邮件内容..." style="min-height: 160px;" required></textarea>
        </div>
        <button class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
            立即发送
        </button>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            全站群发邮件
        </div>
    </div>
    <div style="color: #fcd34d; background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3); border-radius:10px; padding: 14px 18px; margin-bottom: 20px; font-size:14px;">
        ⚠️ 请谨慎使用此功能，会给所有已注册的用户发送邮件。发送前请先对单个用户测试一次。
    </div>
    <form method="POST" onsubmit="return confirm('确定要给所有用户发送邮件吗？这可能需要较长时间。');" style="max-width: 720px;">
        <input type="hidden" name="action" value="send_all">
        <div class="form-group">
            <label class="form-label">群发邮件主题</label>
            <input type="text" name="all_subject" class="form-input" placeholder="主题" required>
        </div>
        <div class="form-group">
            <label class="form-label">群发邮件内容</label>
            <textarea name="all_content" class="form-textarea" placeholder="内容..." style="min-height: 160px;" required></textarea>
        </div>
        <button class="btn btn-danger">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>
            给所有用户发送
        </button>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            最近发送记录 (最近 <?php echo count($logs); ?> 条)
        </div>
    </div>
    <?php if (empty($logs)): ?>
        <div class="empty-state" style="padding: 30px;"><div class="empty-state-title" style="font-size:16px;">暂无记录</div></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>时间</th><th>类型</th><th>收件人</th><th>主题</th></tr></thead>
        <tbody>
            <?php foreach ($logs as $l): ?>
            <tr>
                <td style="color:var(--text-muted); font-size:13px;"><?php echo e(date('m-d H:i:s', strtotime($l['sent_at']))); ?></td>
                <td><span class="badge badge-info"><?php echo e($l['type'] ?: '普通'); ?></span></td>
                <td><?php echo e($l['to_email']); ?></td>
                <td style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo e($l['subject']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
