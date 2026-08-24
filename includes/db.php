<?php
require_once dirname(__FILE__) . '/config.php';

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                )
            );
        } catch(PDOException $e) {
            die("数据库连接失败: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql, $params = array()) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetchAll($sql, $params = array()) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = array()) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        $sql = "INSERT INTO $table (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
        $this->query($sql, array_values($data));
        return $this->conn->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = array()) {
        $set = array();
        foreach (array_keys($data) as $field) {
            $set[] = "$field = ?";
        }
        $sql = "UPDATE $table SET " . implode(',', $set) . " WHERE $where";
        $params = array_merge(array_values($data), $whereParams);
        $this->query($sql, $params);
    }
    
    public function delete($table, $where, $whereParams = array()) {
        $sql = "DELETE FROM $table WHERE $where";
        $this->query($sql, $whereParams);
    }
}
?>
