<?php
$adminActivePage = 'theme';
$adminTitle = '主题设置';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/db.php';

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'theme') {
        $color = $_POST['theme_color'] ?? '';
        if (preg_match('/^#([0-9a-f]{6}|[0-9a-f]{3})$/i', $color)) {
            SiteSetting::set('theme_color', $color);
            redirect('theme.php?msg=' . urlencode('主题颜色已更新') . '&t=success');
        }
    } elseif ($action === 'site') {
        $siteName = trim($_POST['site_name'] ?? '');
        $parser = trim($_POST['player_parser'] ?? '');
        $tmdbKey = trim($_POST['tmdb_api_key'] ?? '');
        $tmdbToken = trim($_POST['tmdb_read_token'] ?? '');
        if ($siteName) SiteSetting::set('site_name', $siteName);
        if ($parser) SiteSetting::set('player_parser', $parser);
        if ($tmdbKey) SiteSetting::set('tmdb_api_key', $tmdbKey);
        if ($tmdbToken) SiteSetting::set('tmdb_read_token', $tmdbToken);
        redirect('theme.php?msg=' . urlencode('设置已保存') . '&t=success');
    }
}

$presetColors = array(
    '#7c3aed', '#2563eb', '#ef4444', '#f59e0b', '#10b981',
    '#06b6d4', '#ec4899', '#8b5cf6', '#3b82f6', '#14b8a6',
    '#f97316', '#84cc16', '#a855f7', '#e11d48', '#0ea5e9',
    '#6366f1', '#d946ef', '#0891b2', '#65a30d', '#be123c',
);

$currentColor = SiteSetting::get('theme_color', '#7c3aed');
$siteName = SiteSetting::get('site_name', 'Jay影视');
$parser = SiteSetting::get('player_parser', 'https://svip.ffzyplay.com/?url=');
$tmdbKey = SiteSetting::get('tmdb_api_key', 'cb44223c5dee5676ed3a839f42ed27e3');
$tmdbToken = SiteSetting::get('tmdb_read_token', '');

showAlert();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
            自定义网站主题颜色
        </div>
    </div>
    <p style="color:var(--text-secondary); font-size:14px; margin-bottom: 16px;">选择一个预设颜色，或自定义颜色。实时预览效果会在页面上呈现。</p>
    <form method="POST">
        <input type="hidden" name="action" value="theme">
        <div class="theme-colors" id="colorPresets">
            <?php foreach ($presetColors as $c): ?>
                <div class="theme-color-item <?php echo strtolower($c) === strtolower($currentColor) ? 'active' : ''; ?>"
                     style="background: linear-gradient(135deg, <?php echo $c; ?>, <?php echo darkenColor($c, -20); ?>);"
                     data-color="<?php echo $c; ?>"
                     onclick="selectColor('<?php echo $c; ?>')"></div>
            <?php endforeach; ?>
        </div>
        <div class="color-input-wrap">
            <label class="form-label" style="margin:0; min-width: 120px;">自定义颜色:</label>
            <input type="color" id="customColorPicker" value="<?php echo e($currentColor); ?>" onchange="selectColor(this.value)">
            <input type="text" class="form-input" id="colorTextInput" name="theme_color" value="<?php echo e($currentColor); ?>" style="max-width:180px;" onchange="selectColor(this.value)">
            <button class="btn btn-primary">保存主题颜色</button>
        </div>
    </form>

    <div style="margin-top: 32px; padding: 24px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px;">
        <div style="font-weight: 800; margin-bottom: 16px;">主题预览</div>
        <div style="display:flex; gap: 14px; flex-wrap: wrap;">
            <button class="btn btn-primary">主要按钮</button>
            <button class="btn btn-outline">次要按钮</button>
            <button class="btn btn-danger">危险操作</button>
            <button class="btn btn-success">成功</button>
            <span class="badge badge-success">状态徽章</span>
            <span class="admin-badge" style="margin-left:0;">开发者标识</span>
        </div>
        <div style="margin-top: 16px;">
            <div style="width:100%; height:10px; background: rgba(255,255,255,0.1); border-radius:5px; overflow:hidden;">
                <div style="width:65%; height:100%; background: var(--theme-gradient); border-radius:5px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
            系统参数设置
        </div>
    </div>
    <form method="POST" style="max-width: 720px;">
        <input type="hidden" name="action" value="site">
        <div class="form-group">
            <label class="form-label">网站名称</label>
            <input type="text" name="site_name" class="form-input" value="<?php echo e($siteName); ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">视频解析播放器地址（拼接在播放链接之前）</label>
            <input type="url" name="player_parser" class="form-input" value="<?php echo e($parser); ?>">
            <div style="color:var(--text-muted); font-size:12px; margin-top:6px;">当前默认：https://svip.ffzyplay.com/?url= </div>
        </div>
        <div class="form-group">
            <label class="form-label">TMDB API Key</label>
            <input type="text" name="tmdb_api_key" class="form-input" value="<?php echo e($tmdbKey); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">TMDB Read Access Token（可选）</label>
            <textarea name="tmdb_read_token" class="form-input" style="min-height: 60px; font-family:monospace; font-size:12px;"><?php echo e($tmdbToken); ?></textarea>
        </div>
        <button class="btn btn-primary">保存设置</button>
    </form>
</div>

<?php
function darkenColor($hex, $percent) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = max(0, min(255, hexdec(substr($hex,0,2)) + $percent));
    $g = max(0, min(255, hexdec(substr($hex,2,2)) + $percent));
    $b = max(0, min(255, hexdec(substr($hex,4,2)) + $percent));
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}
?>
<script>
    function selectColor(color) {
        if (!/^#([0-9a-f]{6}|[0-9a-f]{3})$/i.test(color)) return;
        document.documentElement.style.setProperty('--theme-color', color);
        document.body.setAttribute('data-theme', color);
        document.getElementById('colorTextInput').value = color;
        document.getElementById('customColorPicker').value = color.length === 4 ? (color[1]+color[1]+color[2]+color[2]+color[3]+color[3]) : color;
        document.querySelectorAll('#colorPresets .theme-color-item').forEach(function(el){
            var c = el.getAttribute('data-color').toLowerCase();
            el.classList.toggle('active', c === color.toLowerCase());
        });
        // 应用渐变主题
        var hex = color.replace('#','');
        if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        var r = parseInt(hex.substring(0,2),16);
        var g = parseInt(hex.substring(2,4),16);
        var b = parseInt(hex.substring(4,6),16);
        function adj(v, delta) { return Math.max(0, Math.min(255, v + delta)); }
        function rgba(a) { return 'rgba('+r+','+g+','+b+','+a+')'; }
        var light = '#' + [adj(r,60), adj(g,60), adj(b,60)].map(x => x.toString(16).padStart(2,'0')).join('');
        var dark = '#' + [adj(r,-40), adj(g,-40), adj(b,-40)].map(x => x.toString(16).padStart(2,'0')).join('');
        document.documentElement.style.setProperty('--theme-light', light);
        document.documentElement.style.setProperty('--theme-dark', dark);
        document.documentElement.style.setProperty('--theme-gradient', `linear-gradient(135deg, ${color} 0%, ${rgba(0.9)} 50%, ${dark} 100%)`);
        document.documentElement.style.setProperty('--theme-gradient-2', `linear-gradient(135deg, ${light} 0%, ${color} 100%)`);
        document.documentElement.style.setProperty('--shadow-glow', `0 0 30px ${rgba(0.3)}`);
    }
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
