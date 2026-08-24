<?php
require_once dirname(__FILE__) . '/db.php';

class SiteSetting {
    private static $settings = null;
    
    public static function load() {
        if (self::$settings === null) {
            $db = Database::getInstance();
            $rows = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings");
            self::$settings = array();
            foreach ($rows as $row) {
                self::$settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        return self::$settings;
    }
    
    public static function get($key, $default = null) {
        self::load();
        return isset(self::$settings[$key]) ? self::$settings[$key] : $default;
    }
    
    public static function set($key, $value) {
        $db = Database::getInstance();
        $exists = $db->fetchOne("SELECT id FROM site_settings WHERE setting_key = ?", array($key));
        if ($exists) {
            $db->update('site_settings', array('setting_value' => $value), 'setting_key = ?', array($key));
        } else {
            $db->insert('site_settings', array('setting_key' => $key, 'setting_value' => $value));
        }
        self::$settings = null;
    }
}
?>
