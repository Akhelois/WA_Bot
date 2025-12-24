<?php
session_start();
require_once 'config/config.php';
require_once 'includes/bot_manager.php';

$botManager = new BotManager();
$bots = getBots();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_bot'])) {
        $bot_name = sanitize($_POST['bot_name']);
        $phone = sanitize($_POST['phone']);
        
        $result = $botManager->createBot(1, $bot_name, $phone);
        
        if ($result['success']) {
            header('Location: index.php?bot_id=' . $result['bot_id']);
            exit;
        }
    }
    
    if (isset($_POST['delete_bot'])) {
        $bot_id = $_POST['bot_id'];
        $botManager->deleteBot($bot_id, 1);
        header('Location: index.php');
        exit;
    }
    
    if (isset($_POST['toggle_feature'])) {
        $bot_id = $_POST['bot_id'];
        $feature_key = $_POST['feature_key'];
        $enabled = $_POST['enabled'] == '1' ? false : true;
        $botManager->toggleFeature($bot_id, $feature_key, $enabled);
        header('Location: index.php?bot_id=' . $bot_id);
        exit;
    }
    
    if (isset($_POST['add_reply'])) {
        $bot_id = $_POST['bot_id'];
        $trigger_type = $_POST['trigger_type'];
        $trigger_text = sanitize($_POST['trigger_text']);
        $reply_text = sanitize($_POST['reply_text']);
        $botManager->addAutoReply($bot_id, $trigger_type, $trigger_text, $reply_text);
        header('Location: index.php?bot_id=' . $bot_id);
        exit;
    }
    
    if (isset($_POST['delete_reply'])) {
        $bot_id = $_POST['bot_id'];
        $reply_id = $_POST['reply_id'];
        $botManager->deleteAutoReply($reply_id, $bot_id);
        header('Location: index.php?bot_id=' . $bot_id);
        exit;
    }
    
    if (isset($_POST['add_command'])) {
        $bot_id = $_POST['bot_id'];
        $command = sanitize($_POST['command']);
        $description = sanitize($_POST['description']);
        $response_text = sanitize($_POST['response_text']);
        $botManager->addBotCommand($bot_id, $command, $description, $response_text);
        header('Location: index.php?bot_id=' . $bot_id);
        exit;
    }
    
    if (isset($_POST['delete_command'])) {
        $bot_id = $_POST['bot_id'];
        $command_id = $_POST['command_id'];
        $botManager->deleteBotCommand($command_id, $bot_id);
        header('Location: index.php?bot_id=' . $bot_id);
        exit;
    }
}

$current_bot = null;
$bot_features = [];
$auto_replies = [];
$bot_commands = [];
$recent_messages = [];

if (isset($_GET['bot_id'])) {
    $current_bot = $botManager->getBotById($_GET['bot_id']);
    if ($current_bot) {
        $bot_features = $botManager->getBotFeatures($current_bot['id']);
        $auto_replies = $botManager->getAutoReplies($current_bot['id']);
        $bot_commands = $botManager->getBotCommands($current_bot['id']);
        $recent_messages = $botManager->getRecentMessages($current_bot['id'], 20);
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TestBot Dashboard</title>
    <link rel="stylesheet" href="./assets/css/style.css">
</head>

<body>

    <div class="header">
        <h1>TestBot Dashboard</h1>
    </div>

    <div class="container">
        <div class="sidebar">
            <h3>My Bots (<?php echo count($bots); ?>)</h3>

            <form method="POST" style="margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 3px;">
                <div class="form-group">
                    <input type="text" name="bot_name" placeholder="Bot Name" required>
                </div>
                <div class="form-group">
                    <input type="text" name="phone" placeholder="628xxx" required>
                </div>
                <button type="submit" name="create_bot" class="btn btn-success" style="width: 100%;">Create Bot</button>
            </form>

            <div style="max-height: 500px; overflow-y: auto;">
                <?php if (empty($bots)): ?>
                <div class="empty-state">
                    <p>No bots</p>
                </div>
                <?php else: ?>
                <?php foreach ($bots as $bot): ?>
                <div class="bot-item <?php echo $current_bot && $current_bot['id'] == $bot['id'] ? 'active' : ''; ?> <?php echo $bot['status'] == 'active' ? 'status-active' : ''; ?>"
                    onclick="window.location='?bot_id=<?php echo $bot['id']; ?>'">
                    <strong><?php echo $bot['name']; ?></strong><br>
                    <small><?php echo $bot['phone']; ?></small><br>
                    <span class="badge <?php echo $bot['status'] == 'active' ? 'badge-success' : 'badge-secondary'; ?>">
                        <?php echo strtoupper($bot['status']); ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="main">
            <?php if ($current_bot): ?>

            <div class="section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div>
                        <h2><?php echo $current_bot['name']; ?></h2>
                        <p><?php echo $current_bot['phone']; ?></p>
                        <span
                            class="badge <?php echo $current_bot['status'] == 'active' ? 'badge-success' : 'badge-secondary'; ?>">
                            <?php echo strtoupper($current_bot['status']); ?>
                        </span>
                    </div>
                    <div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="bot_id" value="<?php echo $current_bot['id']; ?>">
                            <button type="submit" name="delete_bot" class="btn btn-danger"
                                onclick="return confirm('Delete bot?')">Delete</button>
                        </form>
                    </div>
                </div>

                <?php if ($current_bot['qr_code']): ?>
                <div style="padding: 15px; background: #fff3cd; border-radius: 3px; text-align: center;">
                    <p>Scan QR Code</p>
                    <img src="<?php echo $current_bot['qr_code']; ?>" alt="QR"
                        style="max-width: 250px; margin-top: 10px;">
                </div>
                <?php endif; ?>
            </div>

            <div class="grid-2">
                <div class="section">
                    <h3 class="section-title">Features</h3>
                    <?php foreach ($bot_features as $feature): ?>
                    <div class="feature-item">
                        <div>
                            <strong><?php echo $feature['feature_name']; ?></strong><br>
                            <small><?php echo $feature['feature_key']; ?></small>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="bot_id" value="<?php echo $current_bot['id']; ?>">
                            <input type="hidden" name="feature_key" value="<?php echo $feature['feature_key']; ?>">
                            <input type="hidden" name="enabled" value="<?php echo $feature['enabled'] ? '1' : '0'; ?>">
                            <button type="submit" name="toggle_feature"
                                class="btn <?php echo $feature['enabled'] ? 'btn-success' : 'btn-secondary'; ?>">
                                <?php echo $feature['enabled'] ? 'ON' : 'OFF'; ?>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="section">
                    <h3 class="section-title">Auto Reply</h3>
                    <form method="POST"
                        style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 3px;">
                        <input type="hidden" name="bot_id" value="<?php echo $current_bot['id']; ?>">
                        <div class="form-group">
                            <select name="trigger_type" required>
                                <option value="exact">Exact</option>
                                <option value="contains">Contains</option>
                                <option value="keyword">Keyword</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <input type="text" name="trigger_text" placeholder="Trigger" required>
                        </div>
                        <div class="form-group">
                            <textarea name="reply_text" placeholder="Reply" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="add_reply" class="btn btn-primary" style="width: 100%;">Add
                            Reply</button>
                    </form>

                    <div style="max-height: 300px; overflow-y: auto;">
                        <?php if (empty($auto_replies)): ?>
                        <div class="empty-state">
                            <p>No replies</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($auto_replies as $reply): ?>
                        <div class="reply-item">
                            <strong>Trigger:</strong> <?php echo $reply['trigger_text']; ?>
                            (<?php echo $reply['trigger_type']; ?>)<br>
                            <strong>Reply:</strong> <?php echo $reply['reply_text']; ?>
                            <form method="POST" style="margin-top: 5px;">
                                <input type="hidden" name="bot_id" value="<?php echo $current_bot['id']; ?>">
                                <input type="hidden" name="reply_id" value="<?php echo $reply['id']; ?>">
                                <button type="submit" name="delete_reply" class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="section">
                <h3 class="section-title">Commands</h3>
                <form method="POST"
                    style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 3px; display: grid; grid-template-columns: 1fr 2fr 2fr auto; gap: 10px;">
                    <input type="hidden" name="bot_id" value="<?php echo $current_bot['id']; ?>">
                    <input type="text" name="command" placeholder="/menu" required>
                    <input type="text" name="description" placeholder="Description" required>
                    <input type="text" name="response_text" placeholder="Response" required>
                    <button type="submit" name="add_command" class="btn btn-primary">Add</button>
                </form>

                <?php if (empty($bot_commands)): ?>
                <div class="empty-state">
                    <p>No commands</p>
                </div>
                <?php else: ?>
                <?php foreach ($bot_commands as $cmd): ?>
                <div class="command-item">
                    <strong><?php echo $cmd['command']; ?></strong> - <?php echo $cmd['description']; ?><br>
                    <small><?php echo $cmd['response_text']; ?></small>
                    <form method="POST" style="margin-top: 5px;">
                        <input type="hidden" name="bot_id" value="<?php echo $current_bot['id']; ?>">
                        <input type="hidden" name="command_id" value="<?php echo $cmd['id']; ?>">
                        <button type="submit" name="delete_command" class="btn btn-danger">Delete</button>
                    </form>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="section">
                <h3 class="section-title">Recent Messages</h3>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($recent_messages)): ?>
                    <div class="empty-state">
                        <p>No messages</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($recent_messages as $msg): ?>
                    <div
                        class="message-item <?php echo $msg['direction'] == 'incoming' ? 'message-incoming' : 'message-outgoing'; ?>">
                        <strong><?php echo $msg['from_number']; ?></strong>
                        <small>(<?php echo date('d/m/Y H:i', $msg['timestamp']); ?>)</small><br>
                        <?php echo nl2br($msg['message_body']); ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>

            <div class="welcome">
                <h2>Welcome to TestBot</h2>
                <p>Create a bot to get started</p>
            </div>

            <?php endif; ?>
        </div>
    </div>

</body>

</html>