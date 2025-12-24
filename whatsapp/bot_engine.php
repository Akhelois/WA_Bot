<?php
error_reporting(E_ALL);
set_time_limit(0);

define('DATA_DIR', __DIR__ . '/../data/');
define('SESSION_DIR', __DIR__ . '/sessions/');
define('API_BASE', 'http://localhost:8000/api/');

if (!file_exists(SESSION_DIR)) {
    mkdir(SESSION_DIR, 0777, true);
}

function loadJSON($file) {
    $filepath = DATA_DIR . $file;
    if (!file_exists($filepath)) {
        return [];
    }
    $content = file_get_contents($filepath);
    return json_decode($content, true) ?: [];
}

function saveJSON($file, $data) {
    $filepath = DATA_DIR . $file;
    file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK));
}

function getBots() {
    return loadJSON('bots.json');
}

function processMessage($bot, $message) {
    $auto_replies = loadJSON('auto_replies.json');
    $commands = loadJSON('bot_commands.json');
    
    $body = strtolower(trim($message['body'] ?? ''));
        
    foreach ($auto_replies as $reply) {
        if ($reply['bot_id'] != $bot['id'] || !$reply['enabled']) {
            continue;
        }
        
        $matched = false;
        $trigger = strtolower(trim($reply['trigger_text']));
                
        switch ($reply['trigger_type']) {
            case 'exact':
                $matched = ($body === $trigger);
                break;
            case 'contains':
                $matched = (strpos($body, $trigger) !== false);
                break;
            case 'keyword':
                $matched = (strpos($body, $trigger) !== false);
                break;
        }
        
        if ($matched) {
            sendMessageToWhatsApp($bot, $message['from'], $reply['reply_text']);
            return true;
        }
    }
    
    if (substr($body, 0, 1) === '/') {
        $command = strtok($body, ' ');
        
        foreach ($commands as $cmd) {
            if ($cmd['bot_id'] == $bot['id'] && $cmd['command'] === $command && $cmd['enabled']) {
                sendMessageToWhatsApp($bot, $message['from'], $cmd['response_text']);
                return true;
            }
        }
    }
    
    return false;
}

function sendMessageToWhatsApp($bot, $to, $text) {
    $phone = str_replace(['@c.us', '@s.whatsapp.net'], '', $to);
        
    $messages = loadJSON('messages.json');
    $messages[] = [
        'id' => count($messages) + 1,
        'bot_id' => $bot['id'],
        'message_id' => uniqid('msg_'),
        'from_number' => $bot['phone'],
        'to_number' => $phone,
        'message_type' => 'text',
        'message_body' => $text,
        'media_url' => null,
        'direction' => 'outgoing',
        'timestamp' => time(),
        'created_at' => date('Y-m-d H:i:s')
    ];
    saveJSON('messages.json', $messages);
    
    $payload = [
        'bot_id' => $bot['id'],
        'to' => $phone,
        'message' => $text
    ];
    
    $ch = curl_init(API_BASE . 'send_message.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    }

function runBot($bot_id) {
    $bots = getBots();
    $bot = null;
    foreach ($bots as $b) {
        if ($b['id'] == $bot_id) {
            $bot = $b;
            break;
        }
    }
    
    if (!$bot) {
        die("Bot not found");
    }
    
    
    $auto_replies = loadJSON('auto_replies.json');
    foreach ($auto_replies as $reply) {
        if ($reply['bot_id'] == $bot['id']) {
            echo "'{$reply['trigger_text']}' ({$reply['trigger_type']}) => '{$reply['reply_text']}'\n";
        }
    }

        
    while (true) {
        clearstatcache();
        $incoming_messages = loadJSON('incoming_messages.json');
        
        $has_new = false;
        foreach ($incoming_messages as $key => $message) {
            $is_unprocessed = (!isset($message['processed']) || $message['processed'] === false);
            $is_for_this_bot = ($message['bot_id'] == $bot_id);
            
            if ($is_for_this_bot && $is_unprocessed) {
                $has_new = true;
                echo "From: {$message['from']} | Body: '{$message['body']}'\n";
                
                $matched = processMessage($bot, $message);
                
                $incoming_messages[$key]['processed'] = true;
                $incoming_messages[$key]['processed_at'] = time();
                saveJSON('incoming_messages.json', $incoming_messages);
                
                $messages = loadJSON('messages.json');
                $messages[] = [
                    'id' => count($messages) + 1,
                    'bot_id' => $bot['id'],
                    'message_id' => $message['id'],
                    'from_number' => $message['from'],
                    'to_number' => $bot['phone'],
                    'message_type' => 'text',
                    'message_body' => $message['body'],
                    'media_url' => null,
                    'direction' => 'incoming',
                    'timestamp' => $message['timestamp'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                saveJSON('messages.json', $messages);
            }
        }
        
        sleep(1);
    }
}

if (isset($argv[1])) {
    runBot(intval($argv[1]));
}
?>