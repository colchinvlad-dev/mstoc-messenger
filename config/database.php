<?php

class Database {
    private $host = 'mysql-8.4';
    private $db_name = 'mtsoc_messenger';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Устанавливаем кодировку отдельным запросом
            $this->conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            
        } catch (PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }
        return $this->conn;
    }
}
?>