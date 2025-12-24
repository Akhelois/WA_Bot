<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    $from = $data['from'] ?? '';
    $body = $data['body'] ?? '';
    $bot_id = $data['bot_id'] ?? 1;
    
    if (!$from || !$body) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing from or body']);
        exit;
    }
    
    $phone_clean = preg_replace('/[^0-9]/', '', $from);
    
    $incoming = loadJSON('incoming_messages.json');
    $incoming[] = [
        'bot_id' => $bot_id,
        'id' => uniqid('msg_'),
        'from' => $phone_clean . '@c.us',
        'body' => $body,
        'type' => 'text',
        'timestamp' => time(),
        'processed' => false
    ];
    saveJSON('incoming_messages.json', $incoming);
    
    echo json_encode(['success' => true, 'message' => 'Message received']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['from']) && isset($_GET['body'])) {
    $from = $_GET['from'];
    $body = $_GET['body'];
    $bot_id = $_GET['bot_id'] ?? 1;
    
    $phone_clean = preg_replace('/[^0-9]/', '', $from);
    
    $incoming = loadJSON('incoming_messages.json');
    $incoming[] = [
        'bot_id' => $bot_id,
        'id' => uniqid('msg_'),
        'from' => $phone_clean . '@c.us',
        'body' => $body,
        'type' => 'text',
        'timestamp' => time(),
        'processed' => false
    ];
    saveJSON('incoming_messages.json', $incoming);
    
    echo json_encode(['success' => true, 'message' => 'Message received']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>