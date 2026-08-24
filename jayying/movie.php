<?php
require_once __DIR__ . '/bootstrap.php';

$id = intval($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'movie';

if (!$id) {
    header('Location: search.php');
    exit;
}

$detail = null;
try {
    $endpoint = $type === 'tv' ? '/tv/' . $id : '/movie/' . $id;
    $detail = tmdb_request($endpoint, [
        'append_to_response' => 'credits,videos,images,recommendations,similar'
    ]);
} catch (Exception $e) {
    $detail = null;
}

if (!$detail) {
    $pageTitle = '未找到';
} else {
    $pageTitle = $detail['title'] ?? ($detail['name'] ?? '详情');
}

$title = $detail['title'] ?? $detail['name'] ?? '';
$originalTitle = $detail['original_title'] ?? ($detail['original_name'] ?? '');
$poster = $detail ? tmdb_image($detail['poster_path'] ?? '', 'w500') : '';
$backdrop = $detail ? tmdb_image($detail['backdrop_path'] ?? '', 'original') : '';
$rating = $detail['vote_average'] ?? 0;
$voteCount = $detail['vote_count'] ?? 0;
$year = '';
$dateStr = $detail['release_date'] ?? ($detail['first_air_date'] ?? '');
if ($dateStr) $year = substr($dateStr, 0, 4);
$runtime = $detail['runtime'] ?? null;
if ($type === 'tv' && !empty($detail['episode_run_time'])) {
    $runtime = $detail['episode_run_time'][0];
}
$runtimeStr = '';
if ($runtime) {
    $h = floor($runtime / 60);
    $m = $runtime % 60;
    $runtimeStr = ($h ? $h . '小时' : '') . ($m ? $m . '分钟' : '');
}
$genres = [];
if (!empty($detail['genres'])) {
    foreach ($detail['genres'] as $g) $genres[] = $g['name'];
}
$overview = $detail['overview'] ?? '暂无简介';

$credits = $detail['credits'] ?? [];
$cast = array_slice($credits['cast'] ?? [], 0, 16);
$directors = [];
if (!empty($credits['crew'])) {
    foreach ($credits['crew'] as $c) {
        if ($c['job'] === 'Director') $directors[] = $c['name'];
    }
}
$directors = array_slice($directors, 0, 2);

$seasons = !empty($detail['seasons']) ? $detail['seasons'] : [];
$recommendations = array_slice(($detail['recommendations']['results'] ?? []), 0, 12);

$favorited = false;
$inHistory = false;
if (is_logged_in()) {
    $fav = $db->fetch("SELECT id FROM favorites WHERE user_id = ? AND tmdb_id = ?", [$_SESSION['user_id'], $id]);
    if ($fav) $favorited = true;
    $hist = $db->fetch("SELECT id FROM watch_history WHERE user_id = ? AND tmdb_id = ?", [$_SESSION['user_id'], $id]);
    if ($hist) $inHistory = true;
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
    --success: #10b981;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    overflow-x: hidden;
}

.navbar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(11,16,25,0.9);
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

.detail-hero {
    position: relative;
    min-height: 500px;
}

.detail-hero-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    filter: blur(60px) brightness(0.35);
    opacity: 0.7;
}

.detail-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(11,16,25,0.4) 0%, rgba(11,16,25,0.85) 50%, var(--bg) 100%);
}

.detail-wrapper {
    position: relative;
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 40px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 60px 20px 40px;
}

.detail-poster {
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(0,0,0,0.5);
    aspect-ratio: 2/3;
    position: sticky;
    top: 90px;
    border: 1px solid var(--border);
}

.detail-poster img { width: 100%; height: 100%; object-fit: cover; }

.detail-info h1 {
    font-size: 42px;
    font-weight: 900;
    margin-bottom: 14px;
    letter-spacing: -1px;
    line-height: 1.15;
}

.detail-original {
    color: var(--text-muted);
    font-size: 14px;
    margin-bottom: 14px;
}

.detail-rating {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.rating-star {
    width: 22px; height: 22px;
    background: #fbbf24;
    clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
}

.rating-value {
    color: #fbbf24;
    font-weight: 800;
    font-size: 20px;
}

.rating-count {
    color: var(--text-muted);
    font-size: 13px;
}

.detail-tags {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin: 14px 0;
}

.detail-tag {
    padding: 5px 14px;
    background: rgba(5,212,199,0.15);
    border: 1px solid rgba(5,212,199,0.35);
    border-radius: 20px;
    font-size: 12px;
    color: var(--primary-light);
    font-weight: 500;
}

.detail-meta-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
    padding: 18px;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 14px;
    margin: 18px 0;
}

.meta-label {
    font-size: 12px;
    color: var(--text-muted);
    margin-bottom: 4px;
}

.meta-value {
    font-size: 15px;
    font-weight: 600;
}

.lang-switcher {
    display: inline-flex;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 4px;
    gap: 4px;
    margin: 18px 0;
}

.lang-btn {
    padding: 7px 16px;
    border-radius: 7px;
    background: none;
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 13px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.lang-btn.active {
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    color: #fff;
}

.detail-actions {
    display: flex;
    gap: 12px;
    margin: 22px 0;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 13px 24px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 14px;
    transition: all 0.25s;
    cursor: pointer;
    text-decoration: none;
    border: none;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    color: #fff;
    box-shadow: 0 8px 24px rgba(5,212,199,0.4);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(5,212,199,0.55);
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--border-light);
    color: var(--text);
}

.btn-outline:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: rgba(5,212,199,0.08);
}

.btn-ghost {
    background: rgba(255,255,255,0.04);
    color: var(--text);
}

.btn-ghost:hover { background: rgba(255,255,255,0.1); }

.btn-icon {
    width: 16px;
    height: 16px;
    position: relative;
    display: inline-block;
}

.play-icon::before {
    content: '';
    width: 0; height: 0;
    border-top: 6px solid transparent;
    border-bottom: 6px solid transparent;
    border-left: 10px solid currentColor;
}

.heart-icon {
    width: 16px; height: 14px;
    position: relative;
}

.heart-icon::before {
    content: '';
    position: absolute;
    width: 16px; height: 14px;
    background: currentColor;
    transform: rotate(-45deg);
    border-radius: 4px 4px 0 0;
}

.heart-icon::after {
    content: '';
    position: absolute;
    top: -7px; left: 0;
    width: 10px; height: 10px;
    background: currentColor;
    border-radius: 50% 50% 0 0;
}

.history-icon {
    width: 16px; height: 16px;
    position: relative;
    border: 2px solid currentColor;
    border-radius: 50%;
}

.history-icon::after {
    content: '';
    position: absolute;
    top: 2px; left: 50%;
    width: 2px; height: 5px;
    background: currentColor;
    transform: translateX(-50%);
}

.history-icon::before {
    content: '';
    position: absolute;
    bottom: 2px; right: 2px;
    width: 4px; height: 4px;
    background: currentColor;
    border-radius: 50%;
}

.section-title {
    font-size: 20px;
    font-weight: 800;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title-icon {
    width: 28px; height: 28px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
}

.overview-text {
    color: var(--text-secondary);
    line-height: 1.8;
    font-size: 15px;
}

.seasons-tabs {
    display: flex;
    gap: 8px;
    margin: 16px 0;
    overflow-x: auto;
    padding-bottom: 6px;
}

.season-tab {
    padding: 9px 20px;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    white-space: nowrap;
    transition: all 0.2s;
    color: var(--text-secondary);
    font-size: 13px;
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
    grid-template-columns: repeat(auto-fill, minmax(85px, 1fr));
    gap: 8px;
    margin: 12px 0 20px;
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
    flex-direction: column;
    gap: 2px;
}

.episode-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: rgba(5,212,199,0.08);
    transform: translateY(-2px);
}

.episode-sub {
    font-size: 10px;
    opacity: 0.6;
    font-weight: 400;
}

.credits-scroll {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding: 6px 0 14px;
    margin: 0 -20px;
    padding-left: 20px;
    padding-right: 20px;
}

.cast-card {
    flex-shrink: 0;
    width: 130px;
    text-align: center;
}

.cast-photo {
    width: 90px; height: 90px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto 8px;
    border: 3px solid var(--border);
    transition: all 0.2s;
}

.cast-card:hover .cast-photo { border-color: var(--primary); }

.cast-photo img { width: 100%; height: 100%; object-fit: cover; }

.cast-placeholder {
    width: 100%; height: 100%;
    background: var(--card);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    font-weight: 700;
    font-size: 28px;
}

.cast-name { font-weight: 700; font-size: 13px; margin-bottom: 2px; }
.cast-role { font-size: 12px; color: var(--text-muted); }

.media-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 18px;
}

.mini-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    color: var(--text);
    display: block;
}

.mini-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 36px rgba(0,0,0,0.4);
    border-color: rgba(5,212,199,0.3);
}

.mini-poster {
    position: relative;
    aspect-ratio: 2/3;
    overflow: hidden;
    background: #1a2535;
}

.mini-poster img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s; }
.mini-card:hover .mini-poster img { transform: scale(1.05); }

.mini-poster::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 50%;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
}

.mini-rating {
    position: absolute;
    top: 8px; left: 8px;
    background: rgba(0,0,0,0.75);
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    color: #fbbf24;
}

.mini-info { padding: 10px 12px 12px; }
.mini-title { font-size: 13px; font-weight: 700; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mini-meta { font-size: 11px; color: var(--text-muted); }

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px 60px;
}

.section { margin: 30px 0; }

.footer-bar {
    text-align: center;
    padding: 30px 20px;
    color: var(--text-muted);
    font-size: 13px;
    border-top: 1px solid var(--border);
    margin-top: 40px;
}

@media (max-width: 1200px) {
    .media-grid { grid-template-columns: repeat(5, 1fr); }
}

@media (max-width: 992px) {
    .detail-wrapper { grid-template-columns: 240px 1fr; gap: 30px; }
    .detail-poster { top: 80px; }
    .detail-info h1 { font-size: 34px; }
    .media-grid { grid-template-columns: repeat(4, 1fr); }
}

@media (max-width: 768px) {
    .detail-wrapper { grid-template-columns: 1fr; gap: 20px; padding: 40px 16px 30px; }
    .detail-poster { position: relative; top: 0; width: 180px; margin: 0 auto; }
    .detail-info h1 { font-size: 26px; }
    .detail-hero { min-height: auto; }
    .detail-hero-bg { filter: blur(40px) brightness(0.3); }
    .media-grid { grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .credits-scroll { margin: 0 -16px; padding-left: 16px; padding-right: 16px; }
    .cast-card { width: 110px; }
    .cast-photo { width: 72px; height: 72px; }
}

@media (max-width: 480px) {
    .media-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .episodes-grid { grid-template-columns: repeat(auto-fill, minmax(65px, 1fr)); }
    .detail-actions { flex-direction: column; }
    .detail-actions .btn { width: 100%; }
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
            <?php if (is_logged_in()): ?>
                <span style="font-size:13px;color:var(--text-secondary);">你好，<?php echo e($_SESSION['username']); ?></span>
            <?php else: ?>
                <a href="login.php" class="nav-btn nav-btn-outline">登录</a>
                <a href="register.php" class="nav-btn nav-btn-primary">注册</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<?php if (!$detail): ?>
<div class="container" style="padding:80px 20px;text-align:center;color:var(--text-muted);">
    <div style="font-size:48px;margin-bottom:16px;">&#128566;</div>
    <h2 style="font-size:20px;color:var(--text);margin-bottom:8px;">未找到该影视</h2>
    <p style="margin-bottom:24px;">它可能已经被移除了</p>
    <a href="search.php" class="btn btn-primary">返回搜索</a>
</div>
<?php else: ?>

<div class="detail-hero">
    <?php if ($backdrop): ?>
    <div class="detail-hero-bg" style="background-image: url('<?php echo e($backdrop); ?>');"></div>
    <?php endif; ?>
    <div class="detail-hero-overlay"></div>

    <div class="detail-wrapper">
        <div class="detail-poster">
            <?php if ($poster): ?>
                <img src="<?php echo e($poster); ?>" alt="<?php echo e($title); ?>">
            <?php else: ?>
                <div style="width:100%;height:100%;background:#1a2535;display:flex;align-items:center;justify-content:center;color:var(--text-muted);">无海报</div>
            <?php endif; ?>
        </div>

        <div class="detail-info">
            <h1><?php echo e($title); ?></h1>
            <?php if ($originalTitle && $originalTitle !== $title): ?>
                <div class="detail-original">原名：<?php echo e($originalTitle); ?></div>
            <?php endif; ?>

            <?php if ($rating > 0): ?>
            <div class="detail-rating">
                <span class="rating-star"></span>
                <span class="rating-value"><?php echo number_format($rating, 1); ?></span>
                <span class="rating-count">(<?php echo number_format($voteCount); ?>人评分)</span>
            </div>
            <?php endif; ?>

            <div class="detail-tags">
                <?php foreach ($genres as $g): ?>
                    <span class="detail-tag"><?php echo e($g); ?></span>
                <?php endforeach; ?>
            </div>

            <div class="detail-meta-grid">
                <?php if ($year): ?>
                <div>
                    <div class="meta-label">年份</div>
                    <div class="meta-value"><?php echo e($year); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($runtimeStr): ?>
                <div>
                    <div class="meta-label">片长</div>
                    <div class="meta-value"><?php echo e($runtimeStr); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($type === 'tv' && !empty($detail['number_of_seasons'])): ?>
                <div>
                    <div class="meta-label">季数</div>
                    <div class="meta-value"><?php echo intval($detail['number_of_seasons']); ?>季</div>
                </div>
                <?php endif; ?>
                <?php if ($type === 'tv' && !empty($detail['number_of_episodes'])): ?>
                <div>
                    <div class="meta-label">集数</div>
                    <div class="meta-value"><?php echo intval($detail['number_of_episodes']); ?>集</div>
                </div>
                <?php endif; ?>
                <?php if (!empty($detail['status'])): ?>
                <div>
                    <div class="meta-label">状态</div>
                    <div class="meta-value"><?php echo e($detail['status']); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <span style="font-weight:600;color:var(--text-secondary);font-size:14px;">播放语言:</span>
                <div class="lang-switcher">
                    <button class="lang-btn active" data-lang="zh">普通话</button>
                    <button class="lang-btn" data-lang="orig">原音</button>
                </div>
            </div>

            <div class="detail-actions">
                <a href="player.php?id=<?php echo $id; ?>&type=<?php echo e($type); ?>" class="btn btn-primary">
                    <span class="btn-icon play-icon"></span>
                    <span>立即播放</span>
                </a>
                <button class="btn <?php echo $favorited ? 'btn-primary' : 'btn-outline'; ?>" id="favBtn" data-id="<?php echo $id; ?>" data-type="<?php echo e($type); ?>">
                    <span class="btn-icon heart-icon" style="color:<?php echo $favorited ? '#fff' : 'var(--text-secondary)'; ?>;"></span>
                    <span><?php echo $favorited ? '已收藏' : '收藏'; ?></span>
                </button>
                <?php if (is_logged_in()): ?>
                <button class="btn <?php echo $inHistory ? 'btn-primary' : 'btn-ghost'; ?>" id="histBtn" data-id="<?php echo $id; ?>" data-type="<?php echo e($type); ?>">
                    <span class="btn-icon history-icon"></span>
                    <span><?php echo $inHistory ? '已观看' : '加入历史'; ?></span>
                </button>
                <?php endif; ?>
            </div>

            <h2 class="section-title">
                <span class="section-title-icon" style="border-radius:8px;"></span>
                剧情简介
            </h2>
            <p class="overview-text"><?php echo e($overview); ?></p>

            <?php if ($type === 'tv' && !empty($seasons)): ?>
            <h2 class="section-title" style="margin-top:28px;">选择季</h2>
            <div class="seasons-tabs" id="seasonTabs">
                <?php foreach ($seasons as $idx => $s): ?>
                    <button class="season-tab <?php echo $idx === 0 ? 'active' : ''; ?>" data-season="s<?php echo $s['season_number']; ?>">
                        <?php echo e($s['name']); ?>
                        <?php if (!empty($s['episode_count'])): ?>
                            <span style="opacity:0.6;font-weight:400;margin-left:4px;">(<?php echo intval($s['episode_count']); ?>集)</span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php foreach ($seasons as $idx => $s): ?>
                <div data-season-panel="s<?php echo $s['season_number']; ?>" style="display: <?php echo $idx === 0 ? '' : 'none'; ?>; margin-bottom: 20px;">
                    <h4 style="font-size:15px;font-weight:700;margin-bottom:10px;"><?php echo e($s['name']); ?></h4>
                    <div class="episodes-grid">
                        <?php
                        $numEp = intval($s['episode_count'] ?? 0);
                        $maxEp = max(1, min($numEp ?: 24, 50));
                        for ($ep = 1; $ep <= $maxEp; $ep++):
                        ?>
                            <a href="player.php?id=<?php echo $id; ?>&type=<?php echo e($type); ?>&season=<?php echo $s['season_number']; ?>&episode=<?php echo $ep; ?>" class="episode-btn">
                                <div>第<?php echo $ep; ?>集</div>
                                <div class="episode-sub">S<?php echo $s['season_number']; ?>E<?php echo $ep; ?></div>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container">

    <?php if (!empty($directors) || !empty($cast)): ?>
    <section class="section">
        <h2 class="section-title">
            <span class="section-title-icon"></span>
            演职员
        </h2>
        <?php if (!empty($directors)): ?>
        <div style="padding:14px 18px;background:var(--card);border:1px solid var(--border);border-radius:12px;margin-bottom:16px;">
            <div style="color:var(--text-muted);font-size:12px;margin-bottom:4px;">导演</div>
            <div style="font-weight:700;"><?php echo e(implode(' / ', $directors)); ?></div>
        </div>
        <?php endif; ?>

        <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;">主演</h3>
        <div class="credits-scroll">
            <?php foreach ($cast as $c):
                $photo = tmdb_image($c['profile_path'] ?? '', 'w185');
            ?>
                <div class="cast-card">
                    <div class="cast-photo">
                        <?php if ($photo): ?>
                            <img src="<?php echo e($photo); ?>" alt="<?php echo e($c['name']); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="cast-placeholder"><?php echo e(mb_substr($c['name'], 0, 1)); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="cast-name"><?php echo e($c['name']); ?></div>
                    <div class="cast-role"><?php echo e($c['character'] ?? ''); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($recommendations)): ?>
    <section class="section">
        <h2 class="section-title">
            <span class="section-title-icon"></span>
            猜你喜欢
        </h2>
        <div class="media-grid">
            <?php foreach ($recommendations as $rec):
                $rType = $rec['media_type'] ?? ($type === 'movie' ? 'movie' : 'tv');
                $rTitle = $rec['title'] ?? $rec['name'] ?? '';
                $rPoster = tmdb_image($rec['poster_path'] ?? '', 'w342');
                $rRating = $rec['vote_average'] ?? 0;
                $rYear = '';
                $rDate = $rec['release_date'] ?? ($rec['first_air_date'] ?? '');
                if ($rDate) $rYear = substr($rDate, 0, 4);
            ?>
            <a href="movie.php?id=<?php echo $rec['id']; ?>&type=<?php echo e($rType); ?>" class="mini-card">
                <div class="mini-poster">
                    <?php if ($rPoster): ?>
                        <img src="<?php echo e($rPoster); ?>" alt="<?php echo e($rTitle); ?>" loading="lazy">
                    <?php else: ?>
                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#1a2535;color:var(--text-muted);font-size:13px;">无海报</div>
                    <?php endif; ?>
                    <?php if ($rRating > 0): ?>
                    <div class="mini-rating"><?php echo number_format($rRating, 1); ?></div>
                    <?php endif; ?>
                </div>
                <div class="mini-info">
                    <div class="mini-title"><?php echo e($rTitle); ?></div>
                    <div class="mini-meta">
                        <?php if ($rYear): ?><?php echo e($rYear); ?><?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php endif; ?>

<div class="footer-bar">
    <p>&copy; <?php echo date('Y'); ?> Jay影视 - 精彩影视在线观看</p>
</div>

<script>
document.querySelectorAll('.lang-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.lang-btn').forEach(function(b) { b.classList.remove('active'); });
        this.classList.add('active');
    });
});

document.querySelectorAll('.season-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        var s = this.getAttribute('data-season');
        document.querySelectorAll('.season-tab').forEach(function(t) { t.classList.remove('active'); });
        this.classList.add('active');
        document.querySelectorAll('[data-season-panel]').forEach(function(p) {
            p.style.display = p.getAttribute('data-season-panel') === s ? '' : 'none';
        });
    });
});

var favBtn = document.getElementById('favBtn');
if (favBtn) {
    favBtn.addEventListener('click', function() {
        <?php if (!is_logged_in()): ?>
        alert('需要登录才能收藏');
        window.location.href = 'login.php';
        return;
        <?php endif; ?>
        var id = this.getAttribute('data-id');
        var mtype = this.getAttribute('data-type');
        fetch('api/data.php?action=favorites&sub_action=check&tmdb_id=' + id, {
            method: 'GET'
        }).then(function(r) { return r.json(); }).then(function(res) {
            var isFav = res.is_favorite;
            if (isFav) {
                fetch('api/data.php?action=favorites&sub_action=remove&tmdb_id=' + id, { method: 'POST' });
                favBtn.classList.remove('btn-primary');
                favBtn.classList.add('btn-outline');
                favBtn.querySelectorAll('span')[1].textContent = '收藏';
                favBtn.querySelectorAll('.heart-icon').style.color = 'var(--text-secondary)';
            } else {
                var title = '<?php echo e($title); ?>';
                var poster = '<?php echo e($poster); ?>';
                fetch('api/data.php?action=favorites&sub_action=add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'tmdb_id=' + encodeURIComponent(id) + '&title=' + encodeURIComponent(title) + '&poster=' + encodeURIComponent(poster) + '&media_type=' + encodeURIComponent(mtype)
                });
                favBtn.classList.remove('btn-outline');
                favBtn.classList.add('btn-primary');
                favBtn.querySelectorAll('span')[1].textContent = '已收藏';
                favBtn.querySelectorAll('.heart-icon').style.color = '#fff';
            }
        });
    });
}

var histBtn = document.getElementById('histBtn');
if (histBtn) {
    histBtn.addEventListener('click', function() {
        var id = this.getAttribute('data-id');
        var mtype = this.getAttribute('data-type');
        var title = '<?php echo e($title); ?>';
        var poster = '<?php echo e($poster); ?>';
        fetch('api/data.php?action=watch_history&sub_action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'tmdb_id=' + encodeURIComponent(id) + '&title=' + encodeURIComponent(title) + '&poster=' + encodeURIComponent(poster) + '&media_type=' + encodeURIComponent(mtype)
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (res.code === 200) {
                histBtn.classList.remove('btn-ghost');
                histBtn.classList.add('btn-primary');
                histBtn.querySelectorAll('span')[1].textContent = '已观看';
            }
        });
    });
}
</script>

</body>
</html>
