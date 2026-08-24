<?php
require_once __DIR__ . '/api/common.php';

$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$pageTitle = $q ? ('搜索: ' . $q) : '搜索';

$result = null;
$error = null;
if ($q) {
    $apiUrl = TMDB_API_URL . '/search/multi';
    $params = [
        'query' => $q,
        'api_key' => TMDB_API_KEY,
        'language' => 'zh-CN',
        'page' => $page,
        'include_adult' => 'false'
    ];
    if ($type === 'movie') {
        $apiUrl = TMDB_API_URL . '/search/movie';
    } elseif ($type === 'tv') {
        $apiUrl = TMDB_API_URL . '/search/tv';
    }

    $url = $apiUrl . '?' . http_build_query($params);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Jay影视/1.0'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURL_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $result = json_decode($response, true);
    } else {
        $error = '搜索服务暂时不可用，请稍后重试';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle; ?> - Jay影视</title>
<style>
:root {
    --theme-color: #05d4c7;
    --theme-light: #3de8db;
    --theme-dark: #03a398;
    --theme-gradient: linear-gradient(135deg, #05d4c7 0%, #1f80d6 100%);
    --bg-primary: #0b1019;
    --bg-secondary: #111827;
    --bg-tertiary: #1a2236;
    --bg-card: rgba(26, 34, 54, 0.6);
    --bg-input: rgba(255,255,255,0.04);
    --text-primary: #ffffff;
    --text-secondary: #9ca3af;
    --text-muted: #6b7280;
    --border-color: rgba(255,255,255,0.07);
    --border-light: rgba(255,255,255,0.14);
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --info: #3b82f6;
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 24px;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
    --shadow-md: 0 4px 20px rgba(0,0,0,0.4);
    --shadow-lg: 0 10px 40px rgba(0,0,0,0.5);
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
    background: var(--bg-primary);
    color: var(--text-primary);
    line-height:1.6;
    min-height:100vh;
    overflow-x:hidden;
}
body::before {
    content:'';
    position:fixed;
    inset:0;
    background:
        radial-gradient(ellipse at top left, rgba(5,212,199,0.12) 0%, transparent 50%),
        radial-gradient(ellipse at bottom right, rgba(31,128,214,0.1) 0%, transparent 50%);
    pointer-events:none;
    z-index:-1;
}
.container { max-width:1400px; margin:0 auto; padding:20px; }

.icon-svg {
    display:inline-block;
    background: currentColor;
    -webkit-mask-size: contain;
    mask-size: contain;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
    -webkit-mask-position: center;
    mask-position: center;
}
.icon-search {
    width:20px; height:20px;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.3-4.3'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.3-4.3'/%3E%3C/svg%3E");
}
.icon-star {
    width:12px; height:12px;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpolygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black'%3E%3Cpolygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/%3E%3C/svg%3E");
}
.icon-filter {
    width:16px; height:16px;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolygon points='22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolygon points='22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3'/%3E%3C/svg%3E");
}
.icon-home {
    width:16px; height:16px;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'/%3E%3Cpolyline points='9 22 9 12 15 12 15 22'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'/%3E%3Cpolyline points='9 22 9 12 15 12 15 22'/%3E%3C/svg%3E");
}
.icon-chevron-left {
    width:18px; height:18px;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='15 18 9 12 15 6'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='15 18 9 12 15 6'/%3E%3C/svg%3E");
}
.icon-chevron-right {
    width:18px; height:18px;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='9 18 15 12 9 6'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='9 18 15 12 9 6'/%3E%3C/svg%3E");
}
.icon-loader {
    width:24px; height:24px;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 12a9 9 0 1 1-6.219-8.56'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 12a9 9 0 1 1-6.219-8.56'/%3E%3C/svg%3E");
}

.search-card {
    background: var(--bg-card);
    border:1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding:28px;
    margin:24px 0;
}
.search-title {
    font-size:22px;
    font-weight:800;
    margin-bottom:6px;
}
.search-sub { font-size:13px; color: var(--text-muted); margin-bottom:16px; }
.search-input-wrap {
    position:relative;
    display:flex;
    gap:10px;
    align-items:center;
}
.search-input-wrap .search-icon {
    position:absolute;
    left:16px;
    top:50%;
    transform:translateY(-50%);
    color: var(--text-muted);
    z-index:2;
}
.search-input {
    flex:1;
    padding:14px 16px 14px 48px;
    background: var(--bg-input);
    border:1px solid var(--border-color);
    border-radius:12px;
    color: var(--text-primary);
    font-size:15px;
    transition: all 0.2s;
}
.search-input::placeholder { color: var(--text-muted); }
.search-input:focus { border-color: var(--theme-color); box-shadow: 0 0 0 4px rgba(5,212,199,0.12); background: rgba(5,212,199,0.05); }
.search-submit {
    padding:14px 28px;
    background: var(--theme-gradient);
    color:#fff;
    border:none;
    border-radius:12px;
    font-weight:700;
    font-size:15px;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:8px;
    box-shadow: 0 4px 15px rgba(5,212,199,0.3);
    transition: all 0.25s;
}
.search-submit:hover { transform:translateY(-2px); box-shadow: 0 8px 25px rgba(5,212,199,0.5); }

.filter-tabs {
    display:flex;
    gap:10px;
    margin:20px 0 24px;
    flex-wrap:wrap;
    align-items:center;
}
.filter-label {
    display:flex;
    align-items:center;
    gap:6px;
    color: var(--text-muted);
    font-size:13px;
    font-weight:600;
    margin-right:4px;
}
.filter-tab {
    padding:8px 20px;
    background: rgba(255,255,255,0.04);
    border:1px solid var(--border-color);
    border-radius:20px;
    color: var(--text-secondary);
    font-size:13px;
    font-weight:600;
    cursor:pointer;
    transition: all 0.2s;
    text-decoration:none;
}
.filter-tab:hover { color: var(--text-primary); border-color: var(--border-light); }
.filter-tab.active {
    background: var(--theme-gradient);
    color:#fff;
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(5,212,199,0.4);
}

.result-meta {
    font-size:13px;
    color: var(--text-muted);
    margin-bottom:18px;
}
.result-meta strong { color: var(--text-primary); }
.result-meta .keyword { color: var(--theme-light); }

.media-grid {
    display:grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap:20px;
}
.media-card {
    background: var(--bg-card);
    border:1px solid var(--border-color);
    border-radius: var(--radius-md);
    overflow:hidden;
    transition: all 0.3s;
    cursor:pointer;
    position:relative;
}
.media-card:hover {
    transform: translateY(-6px);
    border-color: var(--theme-color);
    box-shadow: 0 20px 50px rgba(0,0,0,0.5), 0 0 30px rgba(5,212,199,0.15);
}
.media-poster {
    position:relative;
    aspect-ratio: 2/3;
    overflow:hidden;
    background: var(--bg-tertiary);
}
.media-poster img {
    width:100%; height:100%;
    object-fit:cover;
    transition: transform 0.5s;
}
.media-card:hover .media-poster img { transform: scale(1.06); }
.media-poster::after {
    content:'';
    position:absolute;
    bottom:0; left:0; right:0;
    height:60%;
    background: linear-gradient(to top, rgba(0,0,0,0.85), transparent);
    pointer-events:none;
}
.media-rating {
    position:absolute;
    top:10px; left:10px;
    background: rgba(0,0,0,0.75);
    backdrop-filter: blur(8px);
    padding:4px 10px;
    border-radius:8px;
    font-size:12px;
    font-weight:700;
    color: #fbbf24;
    display:flex;
    align-items:center;
    gap:3px;
    z-index:2;
}
.media-type-badge {
    position:absolute;
    top:10px; right:10px;
    background: var(--theme-gradient);
    padding:3px 10px;
    border-radius:6px;
    font-size:11px;
    font-weight:700;
    color:#fff;
    z-index:2;
}
.media-play-overlay {
    position:absolute;
    inset:0;
    display:flex;
    align-items:center;
    justify-content:center;
    background: rgba(5,212,199,0.25);
    opacity:0;
    transition: opacity 0.3s;
    z-index:3;
}
.media-card:hover .media-play-overlay { opacity:1; }
.media-play-btn {
    width:58px; height:58px;
    background: var(--theme-gradient);
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    transform: scale(0.8);
    transition: transform 0.3s;
    box-shadow: 0 6px 20px rgba(5,212,199,0.6);
}
.media-card:hover .media-play-btn { transform: scale(1); }
.media-play-btn::before {
    content:'';
    width:0; height:0;
    border-top:10px solid transparent;
    border-bottom:10px solid transparent;
    border-left:16px solid #fff;
    margin-left:3px;
}
.media-info {
    padding:14px;
}
.media-title {
    font-size:14px;
    font-weight:700;
    margin-bottom:6px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.media-meta {
    font-size:12px;
    color: var(--text-muted);
    display:flex;
    align-items:center;
    gap:6px;
    flex-wrap:wrap;
}
.media-meta-dot { width:3px; height:3px; background: var(--text-muted); border-radius:50%; }

.empty-state { text-align:center; padding:60px 20px; color: var(--text-muted); }
.empty-icon {
    width:100px; height:100px;
    margin:0 auto 20px;
    background: var(--bg-card);
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
}
.empty-icon::before {
    content:'';
    width:44px; height:44px;
    background: var(--text-muted);
    -webkit-mask-size: contain;
    mask-size: contain;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
    -webkit-mask-position: center;
    mask-position: center;
}
.empty-icon-search::before {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.3-4.3'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.3-4.3'/%3E%3C/svg%3E");
}
.empty-title { font-size:20px; font-weight:700; color: var(--text-primary); margin-bottom:10px; }
.empty-desc { margin-bottom:24px; }
.btn {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:12px 24px;
    border-radius:12px;
    font-weight:700;
    font-size:14px;
    transition: all 0.25s;
    cursor:pointer;
    border:none;
    text-decoration:none;
    white-space:nowrap;
}
.btn-primary { background: var(--theme-gradient); color:#fff; box-shadow: 0 4px 15px rgba(5,212,199,0.3); }
.btn-primary:hover { transform:translateY(-2px); box-shadow: 0 8px 25px rgba(5,212,199,0.5); }
.btn-ghost { background: rgba(255,255,255,0.05); color: var(--text-primary); }
.btn-ghost:hover { background: rgba(255,255,255,0.1); }

.pagination {
    display:flex;
    justify-content:center;
    align-items:center;
    gap:8px;
    margin:40px 0;
}
.page-btn {
    min-width:42px; height:42px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:10px;
    background: var(--bg-card);
    border:1px solid var(--border-color);
    color: var(--text-secondary);
    font-weight:600;
    font-size:14px;
    padding:0 12px;
    transition: all 0.2s;
    text-decoration:none;
}
.page-btn:hover { background: var(--theme-color); color:#fff; border-color: var(--theme-color); transform:translateY(-2px); }
.page-btn.active {
    background: var(--theme-gradient);
    color:#fff;
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(5,212,199,0.4);
}
.page-btn:disabled { opacity:0.4; cursor:not-allowed; }

.error-alert {
    background: rgba(239,68,68,0.12);
    border:1px solid rgba(239,68,68,0.4);
    color: #fca5a5;
    padding:14px 18px;
    border-radius:12px;
    margin:20px 0;
    text-align:center;
}

.suggested-searches {
    margin-top:16px;
}
.suggested-label {
    font-size:13px;
    color: var(--text-muted);
    margin-bottom:10px;
}
.suggested-tags {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.suggested-tag {
    padding:6px 14px;
    background: rgba(5,212,199,0.1);
    border:1px solid rgba(5,212,199,0.25);
    border-radius:20px;
    color: var(--theme-light);
    font-size:12px;
    font-weight:600;
    cursor:pointer;
    transition: all 0.2s;
    text-decoration:none;
}
.suggested-tag:hover { background: var(--theme-color); color:#fff; }

@media (max-width: 1200px) {
    .media-grid { grid-template-columns: repeat(5, 1fr); }
}
@media (max-width: 992px) {
    .media-grid { grid-template-columns: repeat(4, 1fr); }
}
@media (max-width: 768px) {
    .container { padding:14px; }
    .search-card { padding:22px 18px; }
    .search-title { font-size:18px; }
    .search-submit { padding:12px 20px; font-size:14px; }
    .media-grid { grid-template-columns: repeat(3, 1fr); gap:14px; }
    .filter-tabs { gap:6px; }
    .filter-tab { padding:7px 14px; font-size:12px; }
    .pagination { gap:6px; }
    .page-btn { min-width:36px; height:36px; font-size:13px; }
}
@media (max-width: 480px) {
    .media-grid { grid-template-columns: repeat(2, 1fr); gap:12px; }
    .search-input-wrap { flex-direction:column; }
    .search-submit { width:100%; justify-content:center; }
}
</style>
</head>
<body>

<div class="container">
    <div class="search-card">
        <div class="search-title">🔍 搜索影视</div>
        <div class="search-sub">搜索电影、电视剧、动漫等精彩内容</div>
        <form method="GET" action="search.php" id="searchForm">
            <div class="search-input-wrap">
                <span class="icon-svg icon-search search-icon" style="color:var(--text-muted);"></span>
                <input type="text" name="q" class="search-input" id="searchInput" value="<?php echo htmlspecialchars($q); ?>" placeholder="输入关键词，如：速度与激情、复仇者联盟..." autofocus>
                <button type="submit" class="search-submit">
                    <span class="icon-svg icon-search" style="color:#fff;"></span>
                    搜索
                </button>
            </div>
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>" id="typeInput">
        </form>

        <div class="suggested-searches">
            <div class="suggested-label">热门搜索：</div>
            <div class="suggested-tags">
                <a href="search.php?q=流浪地球" class="suggested-tag">流浪地球</a>
                <a href="search.php?q=复仇者联盟" class="suggested-tag">复仇者联盟</a>
                <a href="search.php?q=速度与激情" class="suggested-tag">速度与激情</a>
                <a href="search.php?q=蜘蛛侠" class="suggested-tag">蜘蛛侠</a>
                <a href="search.php?q=哈利波特" class="suggested-tag">哈利波特</a>
            </div>
        </div>
    </div>

    <?php if ($q): ?>
        <div class="filter-tabs">
            <span class="filter-label">
                <span class="icon-svg icon-filter"></span>
                筛选
            </span>
            <a class="filter-tab <?php echo $type === 'all' ? 'active' : ''; ?>" href="?q=<?php echo urlencode($q); ?>&type=all">全部</a>
            <a class="filter-tab <?php echo $type === 'movie' ? 'active' : ''; ?>" href="?q=<?php echo urlencode($q); ?>&type=movie">电影</a>
            <a class="filter-tab <?php echo $type === 'tv' ? 'active' : ''; ?>" href="?q=<?php echo urlencode($q); ?>&type=tv">电视剧</a>
        </div>

        <?php if ($error): ?>
            <div class="error-alert"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($result && !empty($result['results'])): ?>
            <div class="result-meta">
                找到 <strong><?php echo number_format($result['total_results'] ?? 0); ?></strong> 个结果
                关键词 "<span class="keyword"><?php echo htmlspecialchars($q); ?></span>"
            </div>
            <div class="media-grid">
                <?php foreach ($result['results'] as $m):
                    $mediaType = $m['media_type'] ?? ($m['first_air_date'] ?? '' ? 'tv' : 'movie');
                    if ($mediaType === 'person') continue;
                    $id = $m['id'];
                    $title = $m['title'] ?? $m['name'] ?? '';
                    $poster = !empty($m['poster_path']) ? 'https://image.tmdb.org/t/p/w342' . $m['poster_path'] : '';
                    $rating = $m['vote_average'] ?? 0;
                    $dateStr = $m['release_date'] ?? ($m['first_air_date'] ?? '');
                    $year = $dateStr ? substr($dateStr, 0, 4) : '';
                    $typeLabel = $mediaType === 'movie' ? '电影' : ($mediaType === 'tv' ? '电视剧' : '影视');
                ?>
                <div class="media-card" onclick="window.location='detail.php?id=<?php echo $id; ?>&type=<?php echo $mediaType; ?>'">
                    <div class="media-poster">
                        <?php if ($poster): ?>
                            <img src="<?php echo htmlspecialchars($poster); ?>" alt="<?php echo htmlspecialchars($title); ?>" loading="lazy">
                        <?php else: ?>
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:13px;">无海报</div>
                        <?php endif; ?>
                        <?php if ($rating > 0): ?>
                            <div class="media-rating">
                                <span class="icon-svg icon-star" style="color:#fbbf24;"></span>
                                <?php echo number_format($rating, 1); ?>
                            </div>
                        <?php endif; ?>
                        <div class="media-type-badge"><?php echo $typeLabel; ?></div>
                        <div class="media-play-overlay"><div class="media-play-btn"></div></div>
                    </div>
                    <div class="media-info">
                        <div class="media-title"><?php echo htmlspecialchars($title); ?></div>
                        <div class="media-meta">
                            <?php if ($year): ?><span><?php echo $year; ?></span><span class="media-meta-dot"></span><?php endif; ?>
                            <span><?php echo $typeLabel; ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php
                $totalPages = min(500, intval($result['total_pages'] ?? 1));
                if ($totalPages > 1):
            ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a class="page-btn" href="?q=<?php echo urlencode($q); ?>&type=<?php echo urlencode($type); ?>&page=<?php echo $page - 1; ?>">
                        <span class="icon-svg icon-chevron-left"></span>
                    </a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a class="page-btn <?php echo $i === $page ? 'active' : ''; ?>" href="?q=<?php echo urlencode($q); ?>&type=<?php echo urlencode($type); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a class="page-btn" href="?q=<?php echo urlencode($q); ?>&type=<?php echo urlencode($type); ?>&page=<?php echo $page + 1; ?>">
                        <span class="icon-svg icon-chevron-right"></span>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon empty-icon-search"></div>
                <div class="empty-title">没有找到相关结果</div>
                <div class="empty-desc">试试换个关键词重新搜索吧~</div>
                <a href="search.php" class="btn btn-primary">清空搜索</a>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon empty-icon-search"></div>
            <div class="empty-title">输入关键词开始搜索</div>
            <div class="empty-desc">搜索电影、电视剧、动漫、综艺等</div>
        </div>
    <?php endif; ?>
</div>

<script>
(function(){
    var form = document.getElementById('searchForm');
    var input = document.getElementById('searchInput');
    var typeInput = document.getElementById('typeInput');

    form.addEventListener('submit', function(e){
        e.preventDefault();
        var q = input.value.trim();
        if (!q) {
            input.focus();
            return;
        }
        var params = new URLSearchParams();
        params.set('q', q);
        var type = typeInput.value;
        if (type && type !== 'all') {
            params.set('type', type);
        }
        window.location.href = 'search.php?' + params.toString();
    });

    var tabs = document.querySelectorAll('.filter-tab');
    for (var i = 0; i < tabs.length; i++) {
        tabs[i].addEventListener('click', function(e){
            e.preventDefault();
            var url = this.getAttribute('href');
            var urlObj = new URL(url, window.location.origin);
            var params = urlObj.searchParams;
            var q = params.get('q') || input.value.trim();
            var type = params.get('type') || 'all';
            window.location.href = 'search.php?q=' + encodeURIComponent(q) + '&type=' + type;
        });
    }
})();
</script>
</body>
</html>