<?php

// db_test.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Verify file exists first
    if (!file_exists('database.php')) {
        throw new Exception('database.php not found!');
    }
    
    require 'database.php';
    $db = new Database();
    
    // Test query
    $conn = $db->getConnection();
    $conn->exec("SELECT 1 FROM sqlite_master LIMIT 1");
    
    echo "<h1>✅ Database Working Perfectly!</h1>";
    echo "<p>File: " . realpath('aignus.db') . "</p>";
    echo "<p>Size: " . filesize('aignus.db') . " bytes</p>";
    
} catch (Exception $e) {
    echo "<h1>❌ Error Found</h1>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p>Check:</p>";
    echo "<ol>
        <li>File permissions on aignus.db</li>
        <li>No syntax errors in database.php</li>
        <li>PDO_SQLITE is enabled in php.ini</li>
        </ol>";
}