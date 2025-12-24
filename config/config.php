<?php
define('SITE_NAME', 'TestBot');
define('DATA_DIR', __DIR__ . '/../data/');
define('BOT_DIR', __DIR__ . '/../whatsapp/');
define('SESSION_DIR', __DIR__ . '/../whatsapp/sessions/');

date_default_timezone_set('Asia/Jakarta');

if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0777, true);
}

if (!file_exists(SESSION_DIR)) {
    mkdir(SESSION_DIR, 0777, true);
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
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
    file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
}

function getBots() {
    return loadJSON('bots.json');
}

function saveBots($bots) {
    saveJSON('bots.json', $bots);
}

function getBotFeatures() {
    return loadJSON('bot_features.json');
}

function saveBotFeatures($features) {
    saveJSON('bot_features.json', $features);
}

function getAutoReplies() {
    return loadJSON('auto_replies.json');
}

function saveAutoReplies($replies) {
    saveJSON('auto_replies.json', $replies);
}

function getBotCommands() {
    return loadJSON('bot_commands.json');
}

function saveBotCommands($commands) {
    saveJSON('bot_commands.json', $commands);
}

function getMessages() {
    return loadJSON('messages.json');
}

function saveMessages($messages) {
    saveJSON('messages.json', $messages);
}

function getStatistics() {
    return loadJSON('statistics.json');
}

function saveStatistics($stats) {
    saveJSON('statistics.json', $stats);
}
?>