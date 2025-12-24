<?php
header('Content-Type: application/json');
require_once '../config/config.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

try {
    $bot_id = $data['bot_id'] ?? null;
    $to = $data['to'] ?? null;
    $message = $data['message'] ?? null;
    
    if (!$bot_id || !$to || !$message) {
        throw new Exception('Missing required fields');
    }
    
    $bots = getBots();
    $bot = null;
    foreach ($bots as $b) {
        if ($b['id'] == $bot_id) {
            $bot = $b;
            break;
        }
    }
    
    if (!$bot) {
        throw new Exception('Bot not found');
    }
    
    $phone_clean = preg_replace('/[^0-9]/', '', $to);
    
    if (substr($phone_clean, 0, 1) === '0') {
        $phone_clean = '62' . substr($phone_clean, 1);
    }
    
    $messages = getMessages();
    $messages[] = [
        'id' => count($messages) + 1,
        'bot_id' => $bot['id'],
        'message_id' => uniqid('msg_'),
        'from_number' => $bot['phone'],
        'to_number' => $phone_clean,
        'message_type' => 'text',
        'message_body' => $message,
        'media_url' => null,
        'direction' => 'outgoing',
        'timestamp' => time(),
        'created_at' => date('Y-m-d H:i:s')
    ];
    saveJSON('messages.json', $messages);
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $ahk_content = "Run, whatsapp://send?phone=$phone_clean`&text=" . str_replace(["\r", "\n", '"'], ['', '`n', '`"'], $message) . "\r\n";
        $ahk_content .= "Sleep, 2500\r\n";
        $ahk_content .= "Send, {Enter}\r\n";
        
        $ahk_file = DATA_DIR . 'wa_send.ahk';
        file_put_contents($ahk_file, $ahk_content);
        
        $ahk_exe = "C:\\Program Files\\AutoHotkey\\AutoHotkey.exe";
        if (file_exists($ahk_exe)) {
            pclose(popen("start /B \"\" \"$ahk_exe\" \"$ahk_file\"", "r"));
        } else {
            $url = "whatsapp://send?phone=$phone_clean&text=" . urlencode($message);
            pclose(popen("start \"\" \"$url\"", "r"));
        }
        
        register_shutdown_function(function() use ($ahk_file) {
            sleep(4);
            @unlink($ahk_file);
        });
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Sent',
        'phone' => $phone_clean
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>