<?php
$page_title = '首页';
$base_path = __DIR__;
require_once $base_path . '/config/header.php';
?>

<style>
.page-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 24px;
}

.hero-carousel {
    position: relative;
    width: 100%;
    height: 480px;
    overflow: hidden;
    border-radius: 16px;
    margin-bottom: 48px;
    background: var(--bg-card);
}

.carousel-track {
    display: flex;
    height: 100%;
    transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.carousel-slide {
    min-width: 100%;
    height: 100%;
    position: relative;
    flex-shrink: 0;
}

.carousel-slide-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.carousel-slide-gradient {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(11, 16, 25, 0.95) 0%, rgba(11, 16, 25, 0.7) 40%, rgba(11, 16, 25, 0.2) 100%);
}

.carousel-slide-gradient-bottom {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 120px;
    background: linear-gradient(to top, rgba(11, 16, 25, 1) 0%, rgba(11, 16, 25, 0) 100%);
}

.carousel-content {
    position: absolute;
    bottom: 80px;
    left: 48px;
    max-width: 560px;
    z-index: 2;
}

.carousel-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: var(--primary);
    color: #0b1019;
    font-size: 12px;
    font-weight: 600;
    border-radius: 20px;
    margin-bottom: 16px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.carousel-title {
    font-size: 36px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 12px;
    line-height: 1.2;
    text-shadow: 0 2px 12px rgba(0, 0, 0, 0.5);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.carousel-overview {
    font-size: 15px;
    color: rgba(255, 255, 255, 0.85);
    line-height: 1.6;
    margin-bottom: 20px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.carousel-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
    font-size: 14px;
    color: rgba(255, 255, 255, 0.8);
}

.carousel-rating {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--primary);
    font-weight: 600;
}

.carousel-rating svg {
    width: 16px;
    height: 16px;
}

.carousel-buttons {
    display: flex;
    gap: 12px;
}

.btn-watch {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    background: var(--primary);
    color: #0b1019;
    font-weight: 600;
    font-size: 15px;
    border-radius: var(--radius-sm);
    transition: all 0.2s;
}

.btn-watch:hover {
    background: var(--primary-hover);
    box-shadow: 0 4px 16px var(--primary-glow);
}

.btn-more {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    font-weight: 500;
    font-size: 15px;
    border-radius: var(--radius-sm);
    transition: all 0.2s;
    backdrop-filter: blur(8px);
}

.btn-more:hover {
    background: rgba(255, 255, 255, 0.2);
}

.carousel-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 48px;
    height: 48px;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(8px);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    z-index: 10;
    transition: all 0.2s;
}

.carousel-btn:hover {
    background: var(--primary);
    color: #0b1019;
}

.carousel-btn.prev { left: 16px; }
.carousel-btn.next { right: 16px; }

.carousel-btn svg {
    width: 24px;
    height: 24px;
}

.carousel-indicators {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
    z-index: 10;
}

.carousel-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transition: all 0.3s;
    cursor: pointer;
}

.carousel-dot.active {
    background: var(--primary);
    width: 28px;
    border-radius: 5px;
}

.category-shortcuts {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 56px;
}

.category-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 28px 20px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    transition: all 0.3s ease;
    cursor: pointer;
}

.category-card:hover {
    background: var(--bg-card-hover);
    border-color: var(--primary);
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.category-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    position: relative;
}

.category-icon.movies {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.category-icon.tv {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.category-icon.variety {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.category-icon.cartoon {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.category-icon svg {
    width: 28px;
    height: 28px;
    color: #fff;
}

.category-name {
    font-size: 15px;
    font-weight: 600;
    color: var(--text-primary);
}

.section {
    margin-bottom: 56px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}

.section-title {
    font-size: 22px;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title::before {
    content: '';
    display: block;
    width: 4px;
    height: 22px;
    background: var(--primary);
    border-radius: 2px;
}

.section-more {
    display: flex;
    align-items: center;
    gap: 4px;
    color: var(--primary);
    font-size: 14px;
    font-weight: 500;
    transition: gap 0.2s;
}

.section-more:hover {
    gap: 8px;
}

.section-more svg {
    width: 16px;
    height: 16px;
    transition: transform 0.2s;
}

.section-more:hover svg {
    transform: translateX(3px);
}

.card-scroll {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding-bottom: 20px;
    scrollbar-width: thin;
    scrollbar-color: var(--primary) transparent;
}

.card-scroll::-webkit-scrollbar {
    height: 6px;
}

.card-scroll::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 3px;
}

.card-scroll::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 3px;
}

.media-card {
    flex: 0 0 200px;
    background: var(--bg-card);
    border-radius: var(--radius);
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.media-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4);
    border-color: var(--primary);
}

.card-poster {
    width: 100%;
    height: 280px;
    position: relative;
    overflow: hidden;
    background: var(--bg-secondary);
}

.card-poster img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.media-card:hover .card-poster img {
    transform: scale(1.08);
}

.card-play {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    width: 48px;
    height: 48px;
    background: rgba(5, 212, 199, 0.95);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0b1019;
    transition: transform 0.3s ease;
    box-shadow: 0 4px 20px rgba(5, 212, 199, 0.4);
}

.media-card:hover .card-play {
    transform: translate(-50%, -50%) scale(1);
}

.card-play svg {
    width: 20px;
    height: 20px;
    margin-left: 2px;
}

.card-rating {
    position: absolute;
    top: 12px;
    right: 12px;
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(8px);
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: var(--primary);
}

.card-rating svg {
    width: 12px;
    height: 12px;
}

.card-badge-top {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 4px 10px;
    background: linear-gradient(135deg, var(--primary), #0ea5e9);
    color: #0b1019;
    font-size: 11px;
    font-weight: 700;
    border-radius: 6px;
    text-transform: uppercase;
}

.card-info {
    padding: 14px 14px 16px;
}

.card-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-sub {
    font-size: 12px;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 8px;
}

.card-sub .dot {
    width: 3px;
    height: 3px;
    background: var(--text-muted);
    border-radius: 50%;
}

.trending-section {
    margin-bottom: 56px;
}

.trending-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.trending-item {
    display: flex;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
}

.trending-item:hover {
    background: var(--bg-card-hover);
    transform: translateY(-2px);
    border-color: var(--primary);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.trending-rank {
    width: 56px;
    min-height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: 800;
    color: var(--primary);
    opacity: 0.3;
    line-height: 1;
    letter-spacing: -2px;
}

.trending-poster {
    width: 100px;
    height: 140px;
    flex-shrink: 0;
    background: var(--bg-secondary);
}

.trending-poster img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.trending-info {
    flex: 1;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-width: 0;
}

.trending-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--text-primary);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 6px;
}

.trending-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12px;
    color: var(--text-muted);
    flex-wrap: wrap;
}

.trending-meta .type-tag {
    padding: 2px 8px;
    background: rgba(5, 212, 199, 0.15);
    color: var(--primary);
    border-radius: 4px;
    font-weight: 500;
}

.trending-rating {
    display: flex;
    align-items: center;
    gap: 3px;
    color: var(--primary);
    font-weight: 600;
}

.trending-rating svg {
    width: 12px;
    height: 12px;
}

.loading-skeleton {
    display: flex;
    gap: 16px;
    overflow: hidden;
}

.skeleton-card {
    flex: 0 0 200px;
    background: var(--bg-card);
    border-radius: var(--radius);
    overflow: hidden;
}

.skeleton-poster {
    width: 100%;
    height: 280px;
    background: linear-gradient(90deg, var(--bg-card) 0%, var(--bg-secondary) 50%, var(--bg-card) 100%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
}

.skeleton-info {
    padding: 14px;
}

.skeleton-line {
    height: 12px;
    background: linear-gradient(90deg, var(--bg-card) 0%, var(--bg-secondary) 50%, var(--bg-card) 100%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 4px;
    margin-bottom: 8px;
}

.skeleton-line.short {
    width: 60%;
}

@keyframes skeleton-loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.search-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(8px);
    z-index: 1500;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding-top: 120px;
    opacity: 0;
    visibility: hidden;
    transition: all 0.25s ease;
}

.search-modal.active {
    opacity: 1;
    visibility: visible;
}

.search-modal-content {
    width: 90%;
    max-width: 720px;
    transform: translateY(-20px);
    transition: transform 0.25s ease;
}

.search-modal.active .search-modal-content {
    transform: translateY(0);
}

.search-modal-box {
    display: flex;
    align-items: center;
    background: var(--bg-card);
    border: 2px solid var(--border-color);
    border-radius: 16px;
    padding: 0 20px;
    transition: border-color 0.2s;
}

.search-modal-box:focus-within {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px var(--primary-glow);
}

.search-modal-box svg {
    width: 24px;
    height: 24px;
    color: var(--text-muted);
    flex-shrink: 0;
}

.search-modal-box input {
    flex: 1;
    height: 64px;
    background: transparent;
    border: none;
    outline: none;
    color: var(--text-primary);
    font-size: 18px;
    padding: 0 16px;
}

.search-modal-box input::placeholder {
    color: var(--text-muted);
}

.search-modal-close {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    transition: all 0.2s;
    flex-shrink: 0;
}

.search-modal-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
}

.search-hot {
    margin-top: 24px;
    color: var(--text-muted);
    font-size: 13px;
}

.search-hot-title {
    margin-bottom: 12px;
}

.search-hot-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.search-hot-tag {
    padding: 6px 14px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    font-size: 13px;
    color: var(--text-secondary);
    transition: all 0.2s;
    cursor: pointer;
}

.search-hot-tag:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: var(--text-muted);
}

.empty-state-icon {
    width: 64px;
    height: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

@media (max-width: 1024px) {
    .hero-carousel {
        height: 400px;
    }
    .carousel-content {
        bottom: 60px;
        left: 32px;
        max-width: 480px;
    }
    .carousel-title {
        font-size: 28px;
    }
    .category-shortcuts {
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
}

@media (max-width: 768px) {
    .page-wrapper {
        padding: 0 16px;
    }
    .hero-carousel {
        height: 340px;
        border-radius: 12px;
        margin-bottom: 32px;
    }
    .carousel-content {
        bottom: 50px;
        left: 20px;
        right: 20px;
        max-width: none;
    }
    .carousel-title {
        font-size: 22px;
    }
    .carousel-overview {
        font-size: 13px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .carousel-meta {
        font-size: 12px;
        gap: 10px;
    }
    .btn-watch, .btn-more {
        padding: 10px 20px;
        font-size: 14px;
    }
    .carousel-btn {
        width: 36px;
        height: 36px;
    }
    .carousel-btn svg {
        width: 18px;
        height: 18px;
    }
    .category-shortcuts {
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 40px;
    }
    .category-card {
        padding: 20px 12px;
    }
    .category-icon {
        width: 44px;
        height: 44px;
        margin-bottom: 8px;
    }
    .category-icon svg {
        width: 22px;
        height: 22px;
    }
    .category-name {
        font-size: 13px;
    }
    .section {
        margin-bottom: 40px;
    }
    .section-title {
        font-size: 18px;
    }
    .media-card {
        flex: 0 0 150px;
    }
    .card-poster {
        height: 210px;
    }
    .card-title {
        font-size: 13px;
    }
    .trending-grid {
        grid-template-columns: 1fr;
    }
    .search-modal {
        padding-top: 80px;
    }
    .search-modal-box input {
        height: 56px;
        font-size: 16px;
    }
}
</style>

<div class="main-content">
    <div class="page-wrapper">
        <div class="hero-carousel" id="heroCarousel">
            <div class="carousel-track" id="carouselTrack">
                <div class="carousel-slide" data-slide="0">
                    <div class="carousel-slide-bg" style="background-image: url('https://image.tmdb.org/t/p/original/7RyHsO4yDXtBv1zUU3mTpHeQ0d5.jpg')"></div>
                    <div class="carousel-slide-gradient"></div>
                    <div class="carousel-slide-gradient-bottom"></div>
                    <div class="carousel-content">
                        <span class="carousel-badge">🔥 Trending</span>
                        <h2 class="carousel-title">沙丘 第二章</h2>
                        <div class="carousel-meta">
                            <span class="carousel-rating">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                8.6
                            </span>
                            <span>2024</span>
                            <span>动作 / 冒险 / 科幻</span>
                        </div>
                        <p class="carousel-overview">保罗·厄崔迪与弗瑞曼人联手，踏上复仇之旅，同时面临着改变宇宙命运的抉择。</p>
                        <div class="carousel-buttons">
                            <a href="/movie/12345" class="btn-watch">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M8 5v14l11-7z"/></svg>
                                立即播放
                            </a>
                            <a href="/movie/12345" class="btn-more">
                                更多信息
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="carousel-slide" data-slide="1">
                    <div class="carousel-slide-bg" style="background-image: url('https://image.tmdb.org/t/p/original/xOMo8BRK7PfcJv9JCnx7s5hj0PX.jpg')"></div>
                    <div class="carousel-slide-gradient"></div>
                    <div class="carousel-slide-gradient-bottom"></div>
                    <div class="carousel-content">
                        <span class="carousel-badge">🎬 热门</span>
                        <h2 class="carousel-title">奥本海默</h2>
                        <div class="carousel-meta">
                            <span class="carousel-rating">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                8.3
                            </span>
                            <span>2023</span>
                            <span>传记 / 剧情 / 历史</span>
                        </div>
                        <p class="carousel-overview">讲述了美国"原子弹之父"罗伯特·奥本海默的传奇一生。</p>
                        <div class="carousel-buttons">
                            <a href="/movie/12346" class="btn-watch">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M8 5v14l11-7z"/></svg>
                                立即播放
                            </a>
                            <a href="/movie/12346" class="btn-more">
                                更多信息
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="carousel-slide" data-slide="2">
                    <div class="carousel-slide-bg" style="background-image: url('https://image.tmdb.org/t/p/original/z17QQMQy4hH0fhpYc3V9YS6LUarc.jpg')"></div>
                    <div class="carousel-slide-gradient"></div>
                    <div class="carousel-slide-gradient-bottom"></div>
                    <div class="carousel-content">
                        <span class="carousel-badge">⭐ 精选</span>
                        <h2 class="carousel-title">蝙蝠侠</h2>
                        <div class="carousel-meta">
                            <span class="carousel-rating">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                7.8
                            </span>
                            <span>2022</span>
                            <span>动作 / 犯罪 / 悬疑</span>
                        </div>
                        <p class="carousel-overview">年轻的布鲁斯·韦恩化身蝙蝠侠，调查哥谭市黑暗中的连环杀手。</p>
                        <div class="carousel-buttons">
                            <a href="/movie/12347" class="btn-watch">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M8 5v14l11-7z"/></svg>
                                立即播放
                            </a>
                            <a href="/movie/12347" class="btn-more">
                                更多信息
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <button class="carousel-btn prev" id="carouselPrev" aria-label="上一张">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <button class="carousel-btn next" id="carouselNext" aria-label="下一张">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
            <div class="carousel-indicators" id="carouselIndicators">
                <span class="carousel-dot active" data-index="0"></span>
                <span class="carousel-dot" data-index="1"></span>
                <span class="carousel-dot" data-index="2"></span>
            </div>
        </div>

        <div class="category-shortcuts">
            <a href="/movies" class="category-card">
                <div class="category-icon movies">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/>
                        <line x1="7" y1="2" x2="7" y2="22"/>
                        <line x1="17" y1="2" x2="17" y2="22"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <line x1="2" y1="7" x2="7" y2="7"/>
                        <line x1="2" y1="17" x2="7" y2="17"/>
                        <line x1="17" y1="17" x2="22" y2="17"/>
                        <line x1="17" y1="7" x2="22" y2="7"/>
                    </svg>
                </div>
                <span class="category-name">电影</span>
            </a>
            <a href="/tv" class="category-card">
                <div class="category-icon tv">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="15" rx="2" ry="2"/>
                        <polyline points="17 2 12 7 7 2"/>
                    </svg>
                </div>
                <span class="category-name">电视剧</span>
            </a>
            <a href="/variety" class="category-card">
                <div class="category-icon variety">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                </div>
                <span class="category-name">综艺</span>
            </a>
            <a href="/cartoon" class="category-card">
                <div class="category-icon cartoon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <circle cx="12" cy="12" r="4"/>
                        <line x1="4.93" y1="4.93" x2="9.17" y2="9.17"/>
                        <line x1="14.83" y1="14.83" x2="19.07" y2="19.07"/>
                    </svg>
                </div>
                <span class="category-name">动漫</span>
            </a>
        </div>

        <section class="section">
            <div class="section-header">
                <h2 class="section-title">热门电影</h2>
                <a href="/movies?sort=popular" class="section-more">
                    查看更多
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
            </div>
            <div class="card-scroll" id="popularMovies">
                <div class="loading-skeleton">
                    <div class="skeleton-card"><div class="skeleton-poster"></div><div class="skeleton-info"><div class="skeleton-line"></div><div class="skeleton-line short"></div></div></div>
                    <div class="skeleton-card"><div class="skeleton-poster"></div><div class="skeleton-info"><div class="skeleton-line"></div><div class="skeleton-line short"></div></div></div>
                    <div class="skeleton-card"><div class="skeleton-poster"></div><div class="skeleton-info"><div class="skeleton-line"></div><div class="skeleton-line short"></div></div></div>
                    <div class="skeleton-card"><div class="skeleton-poster"></div><div class="skeleton-info"><div class="skeleton-line"></div><div class="skeleton-line short"></div></div></div>
                    <div class="skeleton-card"><div class="skeleton-poster"></div><div class="skeleton-info"><div class="skeleton-line"></div><div class="skeleton-line short"></div></div></div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-header">
                <h2 class="section-title">热门电视剧</h2>
                <a href="/tv?sort=popular" class="section-more">
                    查看更多
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
            </div>
            <div class="card-scroll" id="popularTV">
                <div class="loading-skeleton">
                    <div class="skeleton-card"><div class="skeleton-poster"></div><div class="skeleton-info"><div class="skeleton-line"></div><div class="skeleton-line short"></div></div></div>
                    <div class="skeleton-card"><div class="skeleton-poster"></div><div class="skeleton-info"><div class="skeleton-line"></div><div class="skeleton-line short"></div></div></div>
                    <div class="skeleton-card"><div class="skeleton-poster"></div><div class="skeleton-info"><div class="skeleton-line"></div><div class="skeleton-line short"></div></div></div>
                    <div class="skeleton-card"><div class="skeleton-poster"></div><div class="skeleton-info"><div class="skeleton-line"></div><div class="skeleton-line short"></div></div></div>
                    <div class="skeleton-card"><div class="skeleton-poster"></div><div class="skeleton-info"><div class="skeleton-line"></div><div class="skeleton-line short"></div></div></div>
                </div>
            </div>
        </section>

        <section class="trending-section">
            <div class="section-header">
                <h2 class="section-title">今日趋势</h2>
                <a href="/trending" class="section-more">
                    查看更多
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
            </div>
            <div class="trending-grid" id="trendingList">
                <div class="empty-state">
                    <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    <p>加载中...</p>
                </div>
            </div>
        </section>
    </div>
</div>

<div class="search-modal" id="searchModal">
    <div class="search-modal-content">
        <div class="search-modal-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="searchInput" placeholder="搜索电影、电视剧、综艺...">
            <button class="search-modal-close" id="searchClose" aria-label="关闭">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="search-hot">
            <p class="search-hot-title">🔥 热门搜索</p>
            <div class="search-hot-tags" id="hotTags">
                <span class="search-hot-tag">沙丘</span>
                <span class="search-hot-tag">奥本海默</span>
                <span class="search-hot-tag">三体</span>
                <span class="search-hot-tag">繁花</span>
                <span class="search-hot-tag">狂飙</span>
                <span class="search-hot-tag">满江红</span>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var currentSlide = 0;
    var totalSlides = document.querySelectorAll('.carousel-slide').length;
    var track = document.getElementById('carouselTrack');
    var dots = document.querySelectorAll('.carousel-dot');
    var autoplayInterval;

    function goToSlide(index) {
        currentSlide = index;
        if (currentSlide >= totalSlides) currentSlide = 0;
        if (currentSlide < 0) currentSlide = totalSlides - 1;
        track.style.transform = 'translateX(-' + (currentSlide * 100) + '%)';
        dots.forEach(function(dot, i) {
            dot.classList.toggle('active', i === currentSlide);
        });
    }

    function nextSlide() { goToSlide(currentSlide + 1); }
    function prevSlide() { goToSlide(currentSlide - 1); }

    function startAutoplay() {
        stopAutoplay();
        autoplayInterval = setInterval(nextSlide, 5000);
    }

    function stopAutoplay() {
        if (autoplayInterval) clearInterval(autoplayInterval);
    }

    document.getElementById('carouselPrev').addEventListener('click', function() {
        prevSlide();
        startAutoplay();
    });

    document.getElementById('carouselNext').addEventListener('click', function() {
        nextSlide();
        startAutoplay();
    });

    dots.forEach(function(dot) {
        dot.addEventListener('click', function() {
            goToSlide(parseInt(dot.dataset.index));
            startAutoplay();
        });
    });

    var carousel = document.getElementById('heroCarousel');
    carousel.addEventListener('mouseenter', stopAutoplay);
    carousel.addEventListener('mouseleave', startAutoplay);

    var touchStartX = 0;
    var touchEndX = 0;
    carousel.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
        stopAutoplay();
    });
    carousel.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        var diff = touchStartX - touchEndX;
        if (Math.abs(diff) > 50) {
            if (diff > 0) nextSlide();
            else prevSlide();
        }
        startAutoplay();
    });

    startAutoplay();

    var searchModal = document.getElementById('searchModal');
    var searchInput = document.getElementById('searchInput');
    var searchClose = document.getElementById('searchClose');

    function openSearch() {
        searchModal.classList.add('active');
        setTimeout(function() { searchInput.focus(); }, 100);
    }

    function closeSearch() {
        searchModal.classList.remove('active');
    }

    document.querySelectorAll('.btn-search-trigger, [data-search]').forEach(function(el) {
        el.addEventListener('click', openSearch);
    });

    searchClose.addEventListener('click', closeSearch);
    searchModal.addEventListener('click', function(e) {
        if (e.target === searchModal) closeSearch();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && searchModal.classList.contains('active')) {
            closeSearch();
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            openSearch();
        }
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && searchInput.value.trim()) {
            window.location.href = '/search?q=' + encodeURIComponent(searchInput.value.trim());
        }
    });

    document.querySelectorAll('.search-hot-tag').forEach(function(tag) {
        tag.addEventListener('click', function() {
            searchInput.value = tag.textContent;
            window.location.href = '/search?q=' + encodeURIComponent(tag.textContent);
        });
    });

    function createCard(item, type) {
        var posterPath = item.poster_path ? (item.poster_path.startsWith('http') ? item.poster_path : 'https://image.tmdb.org/t/p/w500' + item.poster_path) : '';
        var title = item.title || item.name || '';
        var year = '';
        if (item.release_date) year = item.release_date.substring(0, 4);
        else if (item.first_air_date) year = item.first_air_date.substring(0, 4);
        var rating = item.vote_average ? item.vote_average.toFixed(1) : '0.0';
        var itemId = item.id || '';

        return '<a href="/' + type + '/' + itemId + '" class="media-card">' +
            '<div class="card-poster">' +
                (posterPath ? '<img src="' + posterPath + '" alt="' + title + '" loading="lazy" onerror="this.style.display=\'none\';this.parentNode.style.background=\'linear-gradient(135deg,#161f2e,#1c2738)\'">' : '') +
                '<div class="card-play"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></div>' +
                '<div class="card-rating"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>' + rating + '</div>' +
            '</div>' +
            '<div class="card-info">' +
                '<div class="card-title">' + title + '</div>' +
                '<div class="card-sub">' +
                    (year ? '<span>' + year + '</span><span class="dot"></span>' : '') +
                    '<span>' + (type === 'movie' ? '电影' : '电视剧') + '</span>' +
                '</div>' +
            '</div>' +
        '</a>';
    }

    function createTrendingItem(item, index) {
        var posterPath = item.poster_path ? (item.poster_path.startsWith('http') ? item.poster_path : 'https://image.tmdb.org/t/p/w185' + item.poster_path) : '';
        var title = item.title || item.name || '';
        var rating = item.vote_average ? item.vote_average.toFixed(1) : '0.0';
        var typeLabel = item.media_type === 'tv' ? '电视剧' : '电影';
        var itemId = item.id || '';

        return '<a href="/' + (item.media_type === 'tv' ? 'tv' : 'movie') + '/' + itemId + '" class="trending-item">' +
            '<div class="trending-rank">' + (index + 1) + '</div>' +
            '<div class="trending-poster">' +
                (posterPath ? '<img src="' + posterPath + '" alt="' + title + '" loading="lazy" onerror="this.style.display=\'none\';this.parentNode.style.background=\'linear-gradient(135deg,#161f2e,#1c2738)\'">' : '') +
            '</div>' +
            '<div class="trending-info">' +
                '<div>' +
                    '<div class="trending-title">' + title + '</div>' +
                    '<div class="trending-meta">' +
                        '<span class="type-tag">' + typeLabel + '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="trending-rating"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>' + rating + '</div>' +
            '</div>' +
        '</a>';
    }

    function loadPopularMovies() {
        fetch('/api/movies.php?sort=popular')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                var container = document.getElementById('popularMovies');
                if (data && data.data && data.data.length > 0) {
                    container.innerHTML = data.data.map(function(item) {
                        return createCard(item, 'movie');
                    }).join('');
                } else {
                    container.innerHTML = '<div class="empty-state"><p>暂无数据</p></div>';
                }
            })
            .catch(function() {
                document.getElementById('popularMovies').innerHTML = '<div class="empty-state"><p>加载失败</p></div>';
            });
    }

    function loadPopularTV() {
        fetch('/api/tv.php?sort=popular')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                var container = document.getElementById('popularTV');
                if (data && data.data && data.data.length > 0) {
                    container.innerHTML = data.data.map(function(item) {
                        return createCard(item, 'tv');
                    }).join('');
                } else {
                    container.innerHTML = '<div class="empty-state"><p>暂无数据</p></div>';
                }
            })
            .catch(function() {
                document.getElementById('popularTV').innerHTML = '<div class="empty-state"><p>加载失败</p></div>';
            });
    }

    function loadTrending() {
        fetch('/api/trending.php?type=all&time_window=day')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                var container = document.getElementById('trendingList');
                if (data && data.data && data.data.length > 0) {
                    container.innerHTML = data.data.slice(0, 10).map(function(item, index) {
                        return createTrendingItem(item, index);
                    }).join('');
                } else {
                    container.innerHTML = '<div class="empty-state"><p>暂无数据</p></div>';
                }
            })
            .catch(function() {
                document.getElementById('trendingList').innerHTML = '<div class="empty-state"><p>加载失败</p></div>';
            });
    }

    loadPopularMovies();
    loadPopularTV();
    loadTrending();
})();
</script>

<?php require_once $base_path . '/config/footer.php'; ?>
