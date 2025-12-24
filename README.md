# TestBot

### Flow Kerja (Contoh):
1. User mengirim pesan "halo" di WhatsApp Desktop
2. Trigger webhook untuk memberitahu bot ada pesan masuk
3. Bot membaca pesan dari `incoming_messages.json`
4. Bot mencocokkan dengan trigger di `auto_replies.json`
5. Jika cocok, bot kirim balasan melalui WhatsApp Desktop
6. WhatsApp Desktop terbuka otomatis dengan pesan siap kirim

## Installation

```bash
git clone https://github.com/yourusername/testbot.git
cd testbot
```
## Structure
```bash
TestBot/
├── api/
│   ├── send_message.php
│   └── webhook.php
├── assets/
│   └── css/
│       └── style.css
├── config/
│   └── config.php
├── data/
│   ├── bots.json
│   ├── bot_features.json
│   ├── auto_replies.json
│   ├── bot_commands.json
│   ├── messages.json
│   ├── incoming_messages.json
│   └── statistics.json
├── includes/
│   └── bot_manager.php
├── whatsapp/
│   ├── bot_engine.php
│   ├── start_bot.php
│   └── sessions/
├── index.php
├── test.php
└── README.md
```
