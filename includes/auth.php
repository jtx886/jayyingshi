<?php
require_once dirname(__FILE__) . '/db.php';

class Auth {
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            $_SESSION['login_message'] = '需要登录才可以观看哦，如没有账号请注册！';
            header('Location: login.php');
            exit();
        }
        // 检查是否被封禁
        $user = self::getCurrentUser();
        if ($user && $user['status'] == 0) {
            $banEnd = $user['ban_end_time'];
            if ($banEnd && strtotime($banEnd) < time()) {
                // 自动解封
                $db = Database::getInstance();
                $db->update('users', array('status' => 1, 'ban_time' => null, 'ban_end_time' => null, 'ban_reason' => null), 'id = ?', array($user['id']));
                $_SESSION['status'] = 1;
            } else {
                session_destroy();
                header('Location: login.php?banned=1');
                exit();
            }
        }
    }
    
    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: index.php');
            exit();
        }
    }
    
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) return null;
        $db = Database::getInstance();
        return $db->fetchOne("SELECT * FROM users WHERE id = ?", array($_SESSION['user_id']));
    }
    
    public static function login($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['avatar'] = $user['avatar'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['status'] = $user['status'];
    }
    
    public static function logout() {
        session_destroy();
        header('Location: index.php');
        exit();
    }
    
    public static function generatePassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
?>
