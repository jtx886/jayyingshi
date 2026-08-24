<?php
// 数据库连接和操作类 - 使用PDO兼容所有PHP版本

class Database {
    private static $instance = null;
    private $pdo;

    public function __construct() {
        try {
            $dsn = 'sqlite:' . DB_PATH;
            $this->pdo = new PDO($dsn);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->query('PRAGMA journal_mode=WAL');
            $this->pdo->query('PRAGMA foreign_keys = ON');
        } catch (PDOException $e) {
            die('数据库连接失败: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return false;
        }
    }

    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return null;
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    public function insert($table, $data) {
        $fields = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach (array_keys($data) as $key) {
            $set[] = "{$key} = ?";
        }
        $setClause = implode(', ', $set);
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        return $this->query($sql, array_merge(array_values($data), $whereParams));
    }

    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params);
    }

    private function logError($message, $sql = '') {
        $logFile = __DIR__ . '/../data/error.log';
        $time = date('Y-m-d H:i:s');
        $log = "[{$time}] {$message} | SQL: {$sql}\n";
        @file_put_contents($logFile, $log, FILE_APPEND);
    }

    // 初始化数据库表结构
    public function initTables() {
        $tables = [];

        // 用户表
        $tables[] = "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            avatar TEXT DEFAULT '',
            status INTEGER DEFAULT 1,
            ban_until TEXT DEFAULT '',
            ban_reason TEXT DEFAULT '',
            created_at TEXT DEFAULT (datetime('now', 'localtime'))
        )";

        // 验证码表
        $tables[] = "CREATE TABLE IF NOT EXISTS verification_codes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL,
            code TEXT NOT NULL,
            type TEXT NOT NULL,
            expires_at TEXT NOT NULL,
            created_at TEXT DEFAULT (datetime('now', 'localtime'))
        )";

        // 播放源表
        $tables[] = "CREATE TABLE IF NOT EXISTS sources (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            url TEXT NOT NULL,
            is_default INTEGER DEFAULT 0,
            status INTEGER DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now', 'localtime'))
        )";

        // 收藏表
        $tables[] = "CREATE TABLE IF NOT EXISTS favorites (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            tmdb_id TEXT NOT NULL,
            title TEXT NOT NULL,
            poster TEXT DEFAULT '',
            media_type TEXT DEFAULT 'movie',
            created_at TEXT DEFAULT (datetime('now', 'localtime')),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";

        // 观看历史表
        $tables[] = "CREATE TABLE IF NOT EXISTS watch_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            tmdb_id TEXT NOT NULL,
            title TEXT NOT NULL,
            poster TEXT DEFAULT '',
            media_type TEXT DEFAULT 'movie',
            season INTEGER DEFAULT 0,
            episode INTEGER DEFAULT 0,
            progress INTEGER DEFAULT 0,
            updated_at TEXT DEFAULT (datetime('now', 'localtime')),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";

        // 反馈表
        $tables[] = "CREATE TABLE IF NOT EXISTS feedback (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            content TEXT NOT NULL,
            status INTEGER DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now', 'localtime')),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";

        // 反馈回复表
        $tables[] = "CREATE TABLE IF NOT EXISTS feedback_replies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            feedback_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            content TEXT NOT NULL,
            is_admin INTEGER DEFAULT 0,
            created_at TEXT DEFAULT (datetime('now', 'localtime')),
            FOREIGN KEY (feedback_id) REFERENCES feedback(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";

        // 反馈点赞表
        $tables[] = "CREATE TABLE IF NOT EXISTS feedback_likes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            feedback_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            created_at TEXT DEFAULT (datetime('now', 'localtime')),
            FOREIGN KEY (feedback_id) REFERENCES feedback(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";

        // 公告表
        $tables[] = "CREATE TABLE IF NOT EXISTS announcements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            status INTEGER DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now', 'localtime'))
        )";

        // 公告查看记录表
        $tables[] = "CREATE TABLE IF NOT EXISTS announcement_views (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            announcement_id INTEGER NOT NULL,
            dismissed INTEGER DEFAULT 0,
            created_at TEXT DEFAULT (datetime('now', 'localtime')),
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (announcement_id) REFERENCES announcements(id)
        )";

        // 主题设置表
        $tables[] = "CREATE TABLE IF NOT EXISTS theme_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            primary_color TEXT DEFAULT '#05d4c7',
            secondary_color TEXT DEFAULT '#0e1929',
            accent_color TEXT DEFAULT '#1f80d6',
            bg_color TEXT DEFAULT '#0b1019',
            card_color TEXT DEFAULT '#161f2e',
            text_color TEXT DEFAULT '#ffffff',
            text_secondary TEXT DEFAULT '#b3b3b3',
            updated_at TEXT DEFAULT (datetime('now', 'localtime'))
        )";

        foreach ($tables as $sql) {
            $this->pdo->exec($sql);
        }

        // 插入默认播放源
        $defaultSource = $this->fetch("SELECT id FROM sources WHERE is_default = 1");
        if (!$defaultSource) {
            $this->insert('sources', [
                'name' => '默认源',
                'url' => DEFAULT_SOURCE_URL,
                'is_default' => 1,
                'status' => 1
            ]);
        }

        // 插入默认主题
        $defaultTheme = $this->fetch("SELECT id FROM theme_settings");
        if (!$defaultTheme) {
            $this->insert('theme_settings', [
                'primary_color' => THEME_PRIMARY,
                'secondary_color' => THEME_SECONDARY,
                'accent_color' => THEME_ACCENT,
                'bg_color' => THEME_BG,
                'card_color' => THEME_CARD,
                'text_color' => THEME_TEXT,
                'text_secondary' => THEME_TEXT_SECONDARY
            ]);
        }
    }
}
