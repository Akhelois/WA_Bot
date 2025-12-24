-- Create Database
CREATE DATABASE IF NOT EXISTS wibubot_db;
USE wibubot_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    api_key VARCHAR(64) UNIQUE NOT NULL,
    plan ENUM('free', 'basic', 'premium') DEFAULT 'free',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    expire_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_api_key (api_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bots Table
CREATE TABLE IF NOT EXISTS bots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    session_id VARCHAR(100) UNIQUE NOT NULL,
    status ENUM('active', 'inactive', 'connecting') DEFAULT 'inactive',
    qr_code TEXT NULL,
    webhook_url VARCHAR(255) NULL,
    auto_reply BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bot Features Table (untuk custom features per bot)
CREATE TABLE IF NOT EXISTS bot_features (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bot_id INT NOT NULL,
    feature_key VARCHAR(50) NOT NULL,
    feature_name VARCHAR(100) NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    config JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE,
    INDEX idx_bot_id (bot_id),
    UNIQUE KEY unique_bot_feature (bot_id, feature_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages Log Table
CREATE TABLE IF NOT EXISTS messages_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    bot_id INT NOT NULL,
    message_id VARCHAR(100) NOT NULL,
    from_number VARCHAR(20) NOT NULL,
    to_number VARCHAR(20) NOT NULL,
    message_type ENUM('text', 'image', 'video', 'audio', 'document', 'sticker') DEFAULT 'text',
    message_body TEXT NULL,
    media_url VARCHAR(255) NULL,
    direction ENUM('incoming', 'outgoing') DEFAULT 'incoming',
    timestamp BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE,
    INDEX idx_bot_id (bot_id),
    INDEX idx_from_number (from_number),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto Reply Table
CREATE TABLE IF NOT EXISTS auto_replies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bot_id INT NOT NULL,
    trigger_type ENUM('keyword', 'exact', 'contains', 'regex') DEFAULT 'keyword',
    trigger_text VARCHAR(255) NOT NULL,
    reply_text TEXT NOT NULL,
    reply_media VARCHAR(255) NULL,
    enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE,
    INDEX idx_bot_id (bot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bot Commands Table
CREATE TABLE IF NOT EXISTS bot_commands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bot_id INT NOT NULL,
    command VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL,
    response_text TEXT NULL,
    response_media VARCHAR(255) NULL,
    category VARCHAR(50) DEFAULT 'general',
    enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE,
    INDEX idx_bot_id (bot_id),
    INDEX idx_command (command)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Group Settings Table
CREATE TABLE IF NOT EXISTS group_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bot_id INT NOT NULL,
    group_id VARCHAR(100) NOT NULL,
    group_name VARCHAR(100) NULL,
    welcome_enabled BOOLEAN DEFAULT TRUE,
    welcome_message TEXT NULL,
    antilink_enabled BOOLEAN DEFAULT FALSE,
    antilink_action ENUM('warn', 'kick', 'delete') DEFAULT 'warn',
    auto_sticker BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE,
    UNIQUE KEY unique_bot_group (bot_id, group_id),
    INDEX idx_bot_id (bot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Premium Users Table (untuk fitur premium per user dalam bot)
CREATE TABLE IF NOT EXISTS premium_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bot_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    expire_date DATETIME NOT NULL,
    status ENUM('active', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE,
    UNIQUE KEY unique_bot_user (bot_id, phone_number),
    INDEX idx_bot_id (bot_id),
    INDEX idx_expire_date (expire_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bot Statistics Table
CREATE TABLE IF NOT EXISTS bot_statistics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    bot_id INT NOT NULL,
    stat_date DATE NOT NULL,
    messages_received INT DEFAULT 0,
    messages_sent INT DEFAULT 0,
    commands_used INT DEFAULT 0,
    unique_users INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE,
    UNIQUE KEY unique_bot_date (bot_id, stat_date),
    INDEX idx_bot_id (bot_id),
    INDEX idx_stat_date (stat_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Usage Log Table
CREATE TABLE IF NOT EXISTS api_usage_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    endpoint VARCHAR(100) NOT NULL,
    method VARCHAR(10) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    response_code INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Admin User (password: admin123)
INSERT INTO users (username, email, phone, password, api_key, plan, status) VALUES 
('admin', 'admin@wibubot.com', '628123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin_api_key_default_change_this', 'premium', 'active')
ON DUPLICATE KEY UPDATE id=id;