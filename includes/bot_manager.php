<?php
class BotManager {
    
    public function createBot($user_id, $name, $phone) {
        try {
            $bots = getBots();
            $session_id = 'bot_' . uniqid() . '_' . time();
            
            $bot = [
                'id' => count($bots) + 1,
                'user_id' => $user_id,
                'name' => $name,
                'phone' => $phone,
                'session_id' => $session_id,
                'status' => 'inactive',
                'qr_code' => null,
                'webhook_url' => null,
                'auto_reply' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $bots[] = $bot;
            saveBots($bots);
            
            $this->initializeDefaultFeatures($bot['id']);
            $this->startBotProcess($bot['id']);
            
            return [
                'success' => true,
                'bot_id' => $bot['id'],
                'session_id' => $session_id
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function startBotProcess($bot_id) {
        $php_path = PHP_BINARY;
        $script_path = BOT_DIR . 'start_bot.php';
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B " . $php_path . " " . $script_path . " " . $bot_id, "r"));
        } else {
            exec($php_path . " " . $script_path . " " . $bot_id . " > /dev/null 2>&1 &");
        }
    }
    
    private function initializeDefaultFeatures($bot_id) {
        $default_features = [
            ['downloader', 'Downloader', true],
            ['sticker', 'Sticker Maker', true],
            ['group_management', 'Group Management', true],
            ['auto_reply', 'Auto Reply', true],
            ['welcome_message', 'Welcome Message', true],
            ['antilink', 'Anti Link', false],
            ['games', 'Games', true],
            ['ai_chat', 'AI Chatbot', true],
            ['search', 'Search Engine', true],
            ['quotes', 'Quotes Generator', true],
        ];
        
        $features = getBotFeatures();
        
        foreach ($default_features as $feature) {
            $features[] = [
                'id' => count($features) + 1,
                'bot_id' => $bot_id,
                'feature_key' => $feature[0],
                'feature_name' => $feature[1],
                'enabled' => $feature[2],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
        
        saveBotFeatures($features);
    }
    
    public function getBotById($bot_id, $user_id = null) {
        $bots = getBots();
        foreach ($bots as $bot) {
            if ($bot['id'] == $bot_id) {
                if ($user_id !== null && $bot['user_id'] != $user_id) {
                    return null;
                }
                return $bot;
            }
        }
        return null;
    }
    
    public function updateBotStatus($bot_id, $status) {
        $bots = getBots();
        foreach ($bots as $key => $bot) {
            if ($bot['id'] == $bot_id) {
                $bots[$key]['status'] = $status;
                $bots[$key]['updated_at'] = date('Y-m-d H:i:s');
                saveBots($bots);
                return true;
            }
        }
        return false;
    }
    
    public function updateQRCode($bot_id, $qr_code) {
        $bots = getBots();
        foreach ($bots as $key => $bot) {
            if ($bot['id'] == $bot_id) {
                $bots[$key]['qr_code'] = $qr_code;
                $bots[$key]['updated_at'] = date('Y-m-d H:i:s');
                saveBots($bots);
                return true;
            }
        }
        return false;
    }
    
    public function deleteBot($bot_id, $user_id) {
        $bots = getBots();
        $new_bots = [];
        foreach ($bots as $bot) {
            if (!($bot['id'] == $bot_id)) {
                $new_bots[] = $bot;
            }
        }
        saveBots($new_bots);
        
        $features = getBotFeatures();
        $new_features = [];
        foreach ($features as $feature) {
            if ($feature['bot_id'] != $bot_id) {
                $new_features[] = $feature;
            }
        }
        saveBotFeatures($new_features);
        
        return true;
    }
    
    public function getBotFeatures($bot_id) {
        $features = getBotFeatures();
        $bot_features = [];
        foreach ($features as $feature) {
            if ($feature['bot_id'] == $bot_id) {
                $bot_features[] = $feature;
            }
        }
        return $bot_features;
    }
    
    public function toggleFeature($bot_id, $feature_key, $enabled) {
        $features = getBotFeatures();
        foreach ($features as $key => $feature) {
            if ($feature['bot_id'] == $bot_id && $feature['feature_key'] == $feature_key) {
                $features[$key]['enabled'] = $enabled;
                $features[$key]['updated_at'] = date('Y-m-d H:i:s');
                saveBotFeatures($features);
                return true;
            }
        }
        return false;
    }
    
    public function logMessage($bot_id, $data) {
        $messages = getMessages();
        $messages[] = [
            'id' => count($messages) + 1,
            'bot_id' => $bot_id,
            'message_id' => $data['message_id'],
            'from_number' => $data['from'],
            'to_number' => $data['to'],
            'message_type' => $data['type'],
            'message_body' => $data['body'] ?? null,
            'media_url' => $data['media_url'] ?? null,
            'direction' => $data['direction'],
            'timestamp' => $data['timestamp'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        saveMessages($messages);
        return true;
    }
    
    public function getRecentMessages($bot_id, $limit = 50) {
        $messages = getMessages();
        $bot_messages = [];
        foreach ($messages as $message) {
            if ($message['bot_id'] == $bot_id) {
                $bot_messages[] = $message;
            }
        }
        usort($bot_messages, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        return array_slice($bot_messages, 0, $limit);
    }
    
    public function getAutoReplies($bot_id) {
        $replies = getAutoReplies();
        $bot_replies = [];
        foreach ($replies as $reply) {
            if ($reply['bot_id'] == $bot_id && $reply['enabled']) {
                $bot_replies[] = $reply;
            }
        }
        return $bot_replies;
    }
    
    public function addAutoReply($bot_id, $trigger_type, $trigger_text, $reply_text, $reply_media = null) {
        $replies = getAutoReplies();
        $replies[] = [
            'id' => count($replies) + 1,
            'bot_id' => $bot_id,
            'trigger_type' => $trigger_type,
            'trigger_text' => $trigger_text,
            'reply_text' => $reply_text,
            'reply_media' => $reply_media,
            'enabled' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        saveAutoReplies($replies);
        return true;
    }
    
    public function deleteAutoReply($reply_id, $bot_id) {
        $replies = getAutoReplies();
        $new_replies = [];
        foreach ($replies as $reply) {
            if (!($reply['id'] == $reply_id && $reply['bot_id'] == $bot_id)) {
                $new_replies[] = $reply;
            }
        }
        saveAutoReplies($new_replies);
        return true;
    }
    
    public function getBotCommands($bot_id) {
        $commands = getBotCommands();
        $bot_commands = [];
        foreach ($commands as $command) {
            if ($command['bot_id'] == $bot_id) {
                $bot_commands[] = $command;
            }
        }
        return $bot_commands;
    }
    
    public function addBotCommand($bot_id, $command, $description, $response_text) {
        $commands = getBotCommands();
        $commands[] = [
            'id' => count($commands) + 1,
            'bot_id' => $bot_id,
            'command' => $command,
            'description' => $description,
            'response_text' => $response_text,
            'response_media' => null,
            'category' => 'custom',
            'enabled' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        saveBotCommands($commands);
        return true;
    }
    
    public function deleteBotCommand($command_id, $bot_id) {
        $commands = getBotCommands();
        $new_commands = [];
        foreach ($commands as $command) {
            if (!($command['id'] == $command_id && $command['bot_id'] == $bot_id)) {
                $new_commands[] = $command;
            }
        }
        saveBotCommands($new_commands);
        return true;
    }
    
    public function updateDailyStats($bot_id, $messages_received = 0, $messages_sent = 0, $commands_used = 0) {
        $stats = getStatistics();
        $today = date('Y-m-d');
        $found = false;
        
        foreach ($stats as $key => $stat) {
            if ($stat['bot_id'] == $bot_id && $stat['stat_date'] == $today) {
                $stats[$key]['messages_received'] += $messages_received;
                $stats[$key]['messages_sent'] += $messages_sent;
                $stats[$key]['commands_used'] += $commands_used;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $stats[] = [
                'id' => count($stats) + 1,
                'bot_id' => $bot_id,
                'stat_date' => $today,
                'messages_received' => $messages_received,
                'messages_sent' => $messages_sent,
                'commands_used' => $commands_used,
                'unique_users' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        saveStatistics($stats);
        return true;
    }
}
?>