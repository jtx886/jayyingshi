-- ============================================
-- Jay影视网站 数据库初始化脚本 (SQLite)
-- ============================================

-- 1. users 表：用户表
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    avatar TEXT DEFAULT '',
    is_admin INTEGER DEFAULT 0,
    is_banned INTEGER DEFAULT 0,
    ban_start_time INTEGER DEFAULT 0,
    ban_end_time INTEGER DEFAULT 0,
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL
);

-- 2. verification_codes 表：邮箱验证码表
CREATE TABLE IF NOT EXISTS verification_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    code TEXT NOT NULL,
    type TEXT NOT NULL,
    expire_time INTEGER NOT NULL,
    used INTEGER DEFAULT 0,
    created_at INTEGER NOT NULL
);

-- 3. play_sources 表：播放源表
CREATE TABLE IF NOT EXISTS play_sources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    url TEXT NOT NULL,
    api_type TEXT DEFAULT 'yyzy',
    is_default INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL
);

-- 4. announcements 表：公告表
CREATE TABLE IF NOT EXISTS announcements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL
);

-- 5. announcement_dismissed 表：公告关闭记录表
CREATE TABLE IF NOT EXISTS announcement_dismissed (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    announcement_id INTEGER NOT NULL,
    created_at INTEGER NOT NULL,
    UNIQUE(user_id, announcement_id)
);

-- 6. watch_history 表：观看历史表
CREATE TABLE IF NOT EXISTS watch_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    media_id INTEGER NOT NULL,
    media_type TEXT NOT NULL,
    media_title TEXT NOT NULL,
    media_poster TEXT DEFAULT '',
    season_number INTEGER DEFAULT 1,
    episode_number INTEGER DEFAULT 0,
    play_url TEXT DEFAULT '',
    watch_seconds INTEGER DEFAULT 0,
    last_watch_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL,
    UNIQUE(user_id, media_id, media_type, season_number, episode_number)
);

-- 7. favorites 表：收藏表
CREATE TABLE IF NOT EXISTS favorites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    media_id INTEGER NOT NULL,
    media_type TEXT NOT NULL,
    media_title TEXT NOT NULL,
    media_poster TEXT DEFAULT '',
    created_at INTEGER NOT NULL,
    UNIQUE(user_id, media_id, media_type)
);

-- 8. feedbacks 表：反馈表
CREATE TABLE IF NOT EXISTS feedbacks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    status INTEGER DEFAULT 0,
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL
);

-- 9. feedback_replies 表：反馈回复表
CREATE TABLE IF NOT EXISTS feedback_replies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    feedback_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    content TEXT NOT NULL,
    is_admin INTEGER DEFAULT 0,
    created_at INTEGER NOT NULL
);

-- 10. feedback_likes 表：反馈点赞表
CREATE TABLE IF NOT EXISTS feedback_likes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    feedback_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    created_at INTEGER NOT NULL,
    UNIQUE(feedback_id, user_id)
);

-- 11. site_settings 表：网站设置表
CREATE TABLE IF NOT EXISTS site_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT UNIQUE NOT NULL,
    setting_value TEXT DEFAULT '',
    updated_at INTEGER NOT NULL
);

-- ============================================
-- 索引（提高查询性能）
-- ============================================

-- users 表索引
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_is_admin ON users(is_admin);
CREATE INDEX IF NOT EXISTS idx_users_is_banned ON users(is_banned);

-- verification_codes 表索引
CREATE INDEX IF NOT EXISTS idx_verification_codes_email ON verification_codes(email);
CREATE INDEX IF NOT EXISTS idx_verification_codes_type ON verification_codes(type);
CREATE INDEX IF NOT EXISTS idx_verification_codes_expire ON verification_codes(expire_time);

-- play_sources 表索引
CREATE INDEX IF NOT EXISTS idx_play_sources_status ON play_sources(status);
CREATE INDEX IF NOT EXISTS idx_play_sources_sort ON play_sources(sort_order);
CREATE INDEX IF NOT EXISTS idx_play_sources_default ON play_sources(is_default);

-- watch_history 表索引
CREATE INDEX IF NOT EXISTS idx_watch_history_user ON watch_history(user_id);
CREATE INDEX IF NOT EXISTS idx_watch_history_user_media ON watch_history(user_id, media_id, media_type);
CREATE INDEX IF NOT EXISTS idx_watch_history_last_watch ON watch_history(last_watch_at);

-- favorites 表索引
CREATE INDEX IF NOT EXISTS idx_favorites_user ON favorites(user_id);
CREATE INDEX IF NOT EXISTS idx_favorites_user_media ON favorites(user_id, media_id, media_type);
CREATE INDEX IF NOT EXISTS idx_favorites_created ON favorites(created_at);

-- feedbacks 表索引
CREATE INDEX IF NOT EXISTS idx_feedbacks_user ON feedbacks(user_id);
CREATE INDEX IF NOT EXISTS idx_feedbacks_status ON feedbacks(status);
CREATE INDEX IF NOT EXISTS idx_feedbacks_created ON feedbacks(created_at);

-- feedback_replies 表索引
CREATE INDEX IF NOT EXISTS idx_feedback_replies_feedback ON feedback_replies(feedback_id);
CREATE INDEX IF NOT EXISTS idx_feedback_replies_user ON feedback_replies(user_id);
CREATE INDEX IF NOT EXISTS idx_feedback_replies_created ON feedback_replies(created_at);

-- announcements 表索引
CREATE INDEX IF NOT EXISTS idx_announcements_created ON announcements(created_at);

-- announcement_dismissed 表索引
CREATE INDEX IF NOT EXISTS idx_announcement_dismissed_user ON announcement_dismissed(user_id);

-- site_settings 表索引
CREATE INDEX IF NOT EXISTS idx_site_settings_key ON site_settings(setting_key);
