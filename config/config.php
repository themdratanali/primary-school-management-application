<?php

class Database {
    private static $instance = null;
    private $conn;

    private const DB_HOST = 'localhost';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_NAME = 'mdratanali_apex';
    private function __construct() {
        $this->connect();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect(): void {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $this->conn = new mysqli(self::DB_HOST, self::DB_USER, self::DB_PASS, self::DB_NAME);
            $this->conn->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    public function getConnection(): mysqli {
        return $this->conn;
    }
}

$conn = Database::getInstance()->getConnection();


