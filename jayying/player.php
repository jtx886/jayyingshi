<?php
require_once __DIR__ . '/bootstrap.php';

require_login();

$id = intval($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'movie';
$season = intval($_GET['season'] ?? 1);
$episode = intval($_GET['episode'] ?? 1);

if (!$id) {
    header('Location: search.php');
    exit;
}

$detail = null;
try {
    $endpoint = $type === 'tv' ? '/tv/' . $id : '/movie/' . $id;
    $detail = tmdb_request($endpoint, [
        'append_to_response' => 'credits,videos'
    ]);
} catch (Exception $e) {
    $detail = null;
}

if (!$detail) {
    header('Location: search.php');
    exit;
}

$title = $detail['title'] ?? ($detail['name'] ?? '');
$pageTitle = '播放: ' . $title;
$sources = get_play_sources();
$parserUrl = get_player_parser();

$sourceKeyword = $title;
if ($type === 'tv') {
    $sourceKeyword .= " 第{$season}季 第{$episode}集";
}

$epTitle = '';
if ($type === 'tv') {
    $epTitle = "第{$season}季 · 第{$episode}集";
    if (!empty($detail['seasons'])) {
        foreach ($detail['seasons'] as $s) {
            if ($s['season_number'] == $season) {
                $epTitle = "{$s['name']} · 第{$episode}集";
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo e($pageTitle); ?> - Jay影视</title>
<style>
:root {
    --primary: #05d4c7;
    --primary-dark: #04b8ad;
    --primary-light: #3de8dc;
    --secondary: #0e1929;
    --accent: #1f80d6;
    --bg: #0b1019;
    --card: #161f2e;
    --card-hover: #1c2738;
    --text: #ffffff;
    --text-secondary: #b3b3b3;
    --text-muted: #6b7a8d;
    --border: rgba(255,255,255,0.08);
    --border-light: rgba(255,255,255,0.15);
    --danger: #ef4444;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    overflow-x: hidden;
}

body::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
        radial-gradient(ellipse at top, rgba(5,212,199,0.06) 0%, transparent 50%);
    pointer-events: none;
    z-index: -1;
}

.navbar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(11,16,25,0.92);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
    padding: 0 20px;
}

.nav-inner {
    max-width: 1400px;
    margin: 0 auto;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.nav-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: var(--text);
    font-weight: 900;
    font-size: 20px;
}

.nav-brand-logo {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    border-radius: 10px;
    position: relative;
    display: flex; align-items: center; justify-content: center;
}

.nav-brand-logo::after {
    content: '';
    width: 0; height: 0;
    border-top: 7px solid transparent;
    border-bottom: 7px solid transparent;
    border-left: 12px solid #fff;
    margin-left: 2px;
}

.nav-brand-text {
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.nav-right { display: flex; align-items: center; gap: 10px; }
.nav-btn { padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; transition: all 0.2s; cursor: pointer; border: none; }
.nav-btn-outline { background: transparent; border: 1px solid var(--border-light); color: var(--text); }
.nav-btn-outline:hover { border-color: var(--primary); color: var(--primary); }
.nav-btn-primary { background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); color: #fff; }
.nav-btn-primary:hover { transform: translateY(-1px); }

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px 20px 60px;
}

.player-container {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    background: #000;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    border: 1px solid var(--border);
}

.player-container iframe {
    width: 100%; height: 100%;
    border: none;
}

.loading-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 16px;
    background: #000;
    color: #fff;
    z-index: 10;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255,255,255,0.15);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    color: var(--text-secondary);
    font-size: 14px;
}

.error-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 14px;
    padding: 40px;
    text-align: center;
    background: #000;
    z-index: 10;
}

.error-icon {
    width: 60px; height: 60px;
    border: 3px solid var(--danger);
    border-radius: 50%;
    position: relative;
}

.error-icon::after {
    content: '!';
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    color: var(--danger);
    font-weight: bold;
    font-size: 24px;
}

.error-title {
    font-size: 18px;
    font-weight: 700;
    color: #fff;
}

.error-desc {
    color: var(--text-secondary);
    max-width: 480px;
    font-size: 14px;
    line-height: 1.6;
}

.error-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
    margin-top: 10px;
}

.player-info {
    padding: 24px 0;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
}

.player-info-left h2 {
    font-size: 24px;
    font-weight: 800;
    margin-bottom: 6px;
}

.player-episode-title {
    color: var(--text-secondary);
    font-size: 14px;
}

.player-actions {
    display: flex;
    gap: 10px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--border-light);
    color: var(--text);
}

.btn-outline:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    color: #fff;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(5,212,199,0.35);
}

.section-block {
    margin: 20px 0;
}

.section-title {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-title-bar {
    width: 4px;
    height: 18px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    border-radius: 2px;
}

.seasons-tabs {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 6px;
}

.season-tab {
    padding: 8px 18px;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    white-space: nowrap;
    transition: all 0.2s;
    color: var(--text-secondary);
    font-size: 13px;
    text-decoration: none;
}

.season-tab:hover { color: var(--text); }

.season-tab.active {
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 4px 16px rgba(5,212,199,0.4);
}

.episodes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 8px;
}

.episode-btn {
    aspect-ratio: 16/10;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    color: var(--text-secondary);
    text-decoration: none;
    text-align: center;
}

.episode-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: rgba(5,212,199,0.08);
    transform: translateY(-2px);
}

.episode-btn.active {
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 4px 16px rgba(5,212,199,0.4);
}

.source-tabs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.source-tab {
    padding: 9px 18px;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
    color: var(--text-secondary);
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.source-tab:hover { color: var(--text); }

.source-tab.active {
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 4px 16px rgba(5,212,199,0.4);
}

.source-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: currentColor;
}

.footer-bar {
    text-align: center;
    padding: 30px 20px;
    color: var(--text-muted);
    font-size: 13px;
    border-top: 1px solid var(--border);
    margin-top: 20px;
}

@media (max-width: 768px) {
    .container { padding: 16px 12px 40px; }
    .player-info { flex-direction: column; gap: 14px; }
    .player-info-left h2 { font-size: 20px; }
    .episodes-grid { grid-template-columns: repeat(auto-fill, minmax(65px, 1fr)); }
    .source-tabs { gap: 8px; }
    .source-tab { padding: 7px 14px; font-size: 12px; }
}

@media (max-width: 480px) {
    .player-actions { flex-wrap: wrap; }
    .btn { padding: 8px 14px; font-size: 12px; }
    .episodes-grid { grid-template-columns: repeat(auto-fill, minmax(58px, 1fr)); }
}
</style>
</head>
<body>

<nav class="navbar">
    <div class="nav-inner">
        <a href="search.php" class="nav-brand">
            <div class="nav-brand-logo"></div>
            <span class="nav-brand-text">Jay影视</span>
        </a>
        <div class="nav-right">
            <span style="font-size:13px;color:var(--text-secondary);"><?php echo e($_SESSION['username']); ?></span>
            <a href="movie.php?id=<?php echo $id; ?>&type=<?php echo e($type); ?>" class="nav-btn nav-btn-outline">返回详情</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="player-container" id="playerContainer">
        <div class="loading-overlay" id="loadingOverlay">
            <div class="loading-spinner"></div>
            <div class="loading-text">正在解析播放源，耐心等待...</div>
        </div>
        <iframe id="playerFrame" style="display:none;" allowfullscreen allow="autoplay; encrypted-media; fullscreen; picture-in-picture"></iframe>
    </div>

    <div class="player-info">
        <div class="player-info-left">
            <h2><?php echo e($title); ?></h2>
            <?php if ($epTitle): ?>
            <div class="player-episode-title"><?php echo e($epTitle); ?></div>
            <?php endif; ?>
        </div>
        <div class="player-actions">
            <button class="btn btn-outline" id="switchLineBtn" onclick="switchToNextSource()">
                <span style="width:14px;height:14px;border:2px solid currentColor;border-radius:50%;position:relative;display:inline-block;">
                    <span style="position:absolute;bottom:-2px;right:-2px;width:0;height:0;border-left:4px solid transparent;border-right:4px solid transparent;border-top:6px solid currentColor;"></span>
                </span>
                换线路
            </button>
            <a href="movie.php?id=<?php echo $id; ?>&type=<?php echo e($type); ?>" class="btn btn-outline">
                <span style="width:0;height:0;border-top:5px solid transparent;border-bottom:5px solid transparent;border-right:7px solid currentColor;"></span>
                返回详情
            </a>
        </div>
    </div>

    <?php if ($type === 'tv' && !empty($detail['seasons'])): ?>
    <div class="section-block">
        <div class="section-title"><span class="section-title-bar"></span>选择季</div>
        <div class="seasons-tabs">
            <?php foreach ($detail['seasons'] as $s):
                $active = $s['season_number'] == $season;
            ?>
                <a href="player.php?id=<?php echo $id; ?>&type=<?php echo e($type); ?>&season=<?php echo $s['season_number']; ?>&episode=1" class="season-tab <?php echo $active ? 'active' : ''; ?>">
                    <?php echo e($s['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="section-block">
        <div class="section-title"><span class="section-title-bar"></span>选择集</div>
        <div class="episodes-grid">
            <?php
            $totalEp = 0;
            foreach ($detail['seasons'] as $s) {
                if ($s['season_number'] == $season) {
                    $totalEp = intval($s['episode_count'] ?? 0);
                    break;
                }
            }
            if (!$totalEp) $totalEp = 12;
            for ($ep = 1; $ep <= $totalEp; $ep++):
                $active = $ep == $episode;
            ?>
                <a href="player.php?id=<?php echo $id; ?>&type=<?php echo e($type); ?>&season=<?php echo $season; ?>&episode=<?php echo $ep; ?>" class="episode-btn <?php echo $active ? 'active' : ''; ?>">
                    第<?php echo $ep; ?>集
                </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="section-block">
        <div class="section-title"><span class="section-title-bar"></span>选择播放源</div>
        <div class="source-tabs" id="sourceTabs">
            <?php foreach ($sources as $idx => $src): ?>
                <button class="source-tab <?php echo $idx === 0 ? 'active' : ''; ?>"
                        data-source-id="<?php echo $src['id']; ?>"
                        data-source-url="<?php echo e($src['url']); ?>"
                        data-source-name="<?php echo e($src['name']); ?>">
                    <span class="source-dot"></span>
                    <?php echo e($src['name']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="footer-bar">
    <p>&copy; <?php echo date('Y'); ?> Jay影视 - 精彩影视在线观看</p>
</div>

<script>
var parserUrl = <?php echo json_encode($parserUrl); ?>;
var currentKeyword = <?php echo json_encode($sourceKeyword); ?>;
var currentEpisode = <?php echo $episode; ?>;
var currentSourceIndex = 0;
var totalSources = <?php echo count($sources); ?>;

function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function showError(message) {
    var container = document.getElementById('playerContainer');
    container.innerHTML = `
        <div class="error-overlay">
            <div class="error-icon"></div>
            <div class="error-title">播放加载失败</div>
            <div class="error-desc">${escapeHtml(message)}</div>
            <div class="error-actions">
                <button class="btn btn-primary" onclick="playFromSource(document.querySelectorAll('.source-tab')[0])">尝试第一线路</button>
                <a href="movie.php?id=<?php echo $id; ?>&type=<?php echo $type; ?>" class="btn btn-outline">返回详情页</a>
            </div>
        </div>
    `;
}

function showLoading() {
    var container = document.getElementById('playerContainer');
    container.innerHTML = `
        <div class="loading-overlay">
            <div class="loading-spinner"></div>
            <div class="loading-text">正在解析播放源，耐心等待...</div>
        </div>
        <iframe id="playerFrame" style="display:none;" allowfullscreen allow="autoplay; encrypted-media; fullscreen; picture-in-picture"></iframe>
    `;
}

function playFromSource(btn) {
    showLoading();

    document.querySelectorAll('.source-tab').forEach(function(t) { t.classList.remove('active'); });
    btn.classList.add('active');

    var sourceUrl = btn.getAttribute('data-source-url');
    var keyword = currentKeyword;
    var isMovie = <?php echo $type === 'movie' ? 'true' : 'false'; ?>;

    var container = document.getElementById('playerContainer');
    var iframe = document.getElementById('playerFrame');

    var apiUrl = sourceUrl + (sourceUrl.indexOf('?') > -1 ? '&' : '?') + 'wd=' + encodeURIComponent(keyword);

    var timeoutId = setTimeout(function() {
        showError('加载超时，请尝试切换其他播放源');
    }, 20000);

    fetch(apiUrl)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            clearTimeout(timeoutId);
            var direct = '';

            if (data && data.list && data.list.length > 0) {
                var item = data.list[0];
                if (item.vod_play_url) {
                    var parts = item.vod_play_url.split('$$$').filter(Boolean);
                    if (parts.length > 0) {
                        var lines = parts[0].split('#').filter(Boolean);
                        if (isMovie) {
                            var first = lines[0];
                            var sp = first.split('$');
                            if (sp.length >= 2) direct = sp[1];
                        } else {
                            var targetEp = currentEpisode;
                            var found = false;
                            for (var i = 0; i < lines.length; i++) {
                                var line = lines[i];
                                var epSplit = line.split('$');
                                var label = epSplit[0] || '';
                                var url = epSplit[1] || '';
                                if (!url) continue;
                                var match = label.match(/(\d+)/);
                                if (match && parseInt(match[1]) === targetEp) {
                                    direct = url;
                                    found = true;
                                    break;
                                }
                            }
                            if (!found) {
                                var fallback = lines[Math.min(targetEp - 1, lines.length - 1)] || lines[0];
                                var fbSplit = fallback.split('$');
                                if (fbSplit.length >= 2) direct = fbSplit[1];
                            }
                        }
                    }
                }
            }

            if (direct) {
                var target = parserUrl + encodeURIComponent(direct);
                setTimeout(function() {
                    iframe = document.getElementById('playerFrame');
                    var loadingEl = document.getElementById('loadingOverlay');
                    if (loadingEl) loadingEl.style.display = 'none';
                    iframe.style.display = 'block';
                    iframe.src = target;
                }, 400);
            } else {
                throw new Error('未找到匹配的播放链接');
            }
        })
        .catch(function(err) {
            clearTimeout(timeoutId);
            showError(err.message || '解析播放源失败，请稍后再试或切换线路');
        });
}

function switchToNextSource() {
    var tabs = document.querySelectorAll('.source-tab');
    if (tabs.length < 2) {
        alert('暂无其他线路');
        return;
    }
    var activeIdx = 0;
    tabs.forEach(function(t, i) { if (t.classList.contains('active')) activeIdx = i; });
    var nextIdx = (activeIdx + 1) % tabs.length;
    playFromSource(tabs[nextIdx]);
}

document.querySelectorAll('.source-tab').forEach(function(btn) {
    btn.addEventListener('click', function() {
        playFromSource(btn);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    var first = document.querySelector('.source-tab');
    if (first) playFromSource(first);
});
</script>

</body>
</html>
