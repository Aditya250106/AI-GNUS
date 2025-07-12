<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Database {
    private $db_file = 'aignus.db';  // Using relative path
    private $conn;

    public function __construct() {
        try {
            // Create database file if it doesn't exist
            if (!file_exists($this->db_file)) {
                touch($this->db_file); // Creates empty file
                chmod($this->db_file, 0666); // Set permissions
            }

            $this->conn = new PDO('sqlite:' . $this->db_file);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Initialize database settings and tables
            $this->conn->exec("PRAGMA journal_mode=WAL");
            $this->conn->exec("PRAGMA foreign_keys = ON");
            $this->initializeDatabase();
            
        } catch(PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            header('Content-Type: application/json');
            die(json_encode(['error' => 'Database connection error: ' . $e->getMessage()]));
        }
    }

    private function initializeDatabase() {
        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME
            )",
            
            "CREATE TABLE IF NOT EXISTS conversations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                faculty TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                conversation_id INTEGER NOT NULL,
                role TEXT NOT NULL,
                content TEXT NOT NULL,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
            )"
        ];

        foreach ($tables as $table) {
            $this->conn->exec($table);
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}