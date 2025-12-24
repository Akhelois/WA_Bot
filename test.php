<?php
require_once './config/config.php';

if ($argc < 3) {
    exit;
}

$bot_id = intval($argv[1]);
$message_text = $argv[2];

$incoming = loadJSON('incoming_messages.json');
$incoming[] = [
    'bot_id' => $bot_id,
    'id' => uniqid('msg_'),
    'from' => '085210991638@c.us',
    'body' => $message_text,
    'type' => 'text',
    'timestamp' => time(),
    'processed' => false
];
saveJSON('incoming_messages.json', $incoming);

echo "OK\n";
?>