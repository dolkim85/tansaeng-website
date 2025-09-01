<?php
// Database Configuration
class DatabaseConfig {
    private static $host;
    private static $dbname;
    private static $username;
    private static $password;
    
    private static function loadConfig() {
        // Vercel 환경변수 우선, 로컬 환경은 기본값 사용
        self::$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        self::$dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'tangsaeng_db';
        self::$username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
        self::$password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
    }
    
    public static function getConnection() {
        self::loadConfig();
        
        try {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8mb4";
            $pdo = new PDO($dsn, self::$username, self::$password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("데이터베이스 연결 실패: " . $e->getMessage());
        }
    }
}
?>