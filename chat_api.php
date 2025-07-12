<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'database.php';

$db = new Database();
$conn = $db->getConnection();

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Function to call Hugging Face API
function callHuggingFaceAPI($faculty, $user_message) {
    $api_key = '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'; // Replace with your Hugging Face API key
    $model = 'google/flan-t5-large'; // You can change this to any Hugging Face text-generation model

    $data = [
        "inputs" => $user_message,
        "parameters" => [
            "temperature" => 0.7,
            "max_new_tokens" => 256
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api-inference.huggingface.co/models/$model");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $api_key",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);

    if (curl_errno($ch)) {
        error_log("cURL Error: $curl_error");
        return "I'm having trouble responding right now. (cURL Error: $curl_error)";
    }

    curl_close($ch);

    $result = json_decode($response, true);

    if (isset($result['error'])) {
        error_log("Hugging Face API Error: " . $result['error']);
        return "Hugging Face API Error: " . $result['error'];
    }

    if (isset($result[0]['generated_text'])) {
        return $result[0]['generated_text'];
    } else {
        return "I couldn't generate a response. Please try again. (Invalid output)";
    }
}

// Handle GET requests to load messages
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_messages') {
    $faculty = $_GET['faculty'] ?? '';

    $stmt = $conn->prepare("SELECT id FROM conversations WHERE user_id = :user_id AND faculty = :faculty");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':faculty', $faculty);
    $stmt->execute();
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($conversation) {
        $conversation_id = $conversation['id'];
        $stmt = $conn->prepare("SELECT role, content FROM messages WHERE conversation_id = :conversation_id ORDER BY timestamp ASC");
        $stmt->bindParam(':conversation_id', $conversation_id);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['messages' => $messages]);
    } else {
        echo json_encode(['messages' => []]);
    }
    exit;
}

// Handle POST requests to send messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $faculty = $input['faculty'] ?? '';
    $user_message = $input['user_message'] ?? '';

    if (empty($faculty) || empty($user_message)) {
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    try {
        // Find or create conversation
        $stmt = $conn->prepare("SELECT id FROM conversations WHERE user_id = :user_id AND faculty = :faculty");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':faculty', $faculty);
        $stmt->execute();
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$conversation) {
            $stmt = $conn->prepare("INSERT INTO conversations (user_id, faculty) VALUES (:user_id, :faculty)");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':faculty', $faculty);
            $stmt->execute();
            $conversation_id = $conn->lastInsertId();
        } else {
            $conversation_id = $conversation['id'];
        }

        // Save user message
        $stmt = $conn->prepare("INSERT INTO messages (conversation_id, role, content) VALUES (:conversation_id, 'user', :content)");
        $stmt->bindParam(':conversation_id', $conversation_id);
        $stmt->bindParam(':content', $user_message);
        $stmt->execute();

        // Get AI response
        $ai_response = callHuggingFaceAPI($faculty, $user_message);

        // Save AI response
        $stmt = $conn->prepare("INSERT INTO messages (conversation_id, role, content) VALUES (:conversation_id, 'assistant', :content)");
        $stmt->bindParam(':conversation_id', $conversation_id);
        $stmt->bindParam(':content', $ai_response);
        $stmt->execute();

        echo json_encode(['response' => $ai_response]);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
?>
