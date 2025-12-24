<?php
require_once 'bot_engine.php';

if (isset($argv[1])) {
    $bot_id = intval($argv[1]);
    runBot($bot_id);
} else {
    echo "Usage: php start_bot.php <bot_id>";
}
?>