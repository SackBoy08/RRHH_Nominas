<?php
namespace Config;

use PDO;
use PDOException;

class Database {
    private static ?Database $instance = null;
    private PDO $conn;

    private string $host    = '127.0.0.1';  // usar IP para TCP/IP
    private string $port    = '3306';       // puerto de MySQL en XAMPP
    private string $db      = 'nominas_guate';
    private string $user    = 'root';
    private string $pass    = '';
    private string $charset = 'utf8mb4';

    private function __construct() {
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset={$this->charset}";
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass, $opts);
        } catch (PDOException $e) {
            exit("Error DB: " . $e->getMessage());
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->conn;
    }
}
