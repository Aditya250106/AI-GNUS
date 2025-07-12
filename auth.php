<?php

// Add these lines right at the top
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// auth.php
session_start();
header('Content-Type: application/json');
require_once 'database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable in production

$db = new Database();
$conn = $db->getConnection();

$action = $_POST['action'] ?? '';

// Input validation function
function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($action === 'login') {
    $email = validateInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password are required']);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['last_activity'] = time();
            
            echo json_encode([
                'success' => true, 
                'email' => $user['email'],
                'name' => $user['name']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid email or password']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }

} elseif ($action === 'signup') {
    $name = validateInput($_POST['name'] ?? '');
    $email = validateInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields are required']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }

    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 8 characters']);
        exit;
    }

    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->execute();

        $_SESSION['user_id'] = $conn->lastInsertId();
        $_SESSION['user_email'] = $email;
        $_SESSION['last_activity'] = time();
        
        echo json_encode([
            'success' => true, 
            'email' => $email,
            'name' => $name
        ]);
    } catch (PDOException $e) {
        error_log('Signup Error: ' . $e->getMessage());
        if ($e->getCode() == 23000) { // SQLite constraint violation
            http_response_code(409);
            echo json_encode(['error' => 'Email already exists']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Registration failed'. $e->getMessage()]);
        }
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
?>