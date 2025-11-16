<?php
/** By sourceid */

include('config.php');
// Check if chat directory exists (needed for file_exists check)
if (!is_dir($chat_dir)) {
    mkdir($chat_dir, 0777, true);
    file_put_contents($chat_dir . 'index.php', 'protected by sourceid');
}

$ip_addr = str_replace('.', '_', $_SERVER['REMOTE_ADDR']);
$session_file = 'chat/' . $ip_addr . '.txt';

// Load history for initial display
$chat_data = file_exists($session_file) ? json_decode(file_get_contents($session_file), true) : ['chat' => []];
$chat_history = $chat_data['chat'] ?? [];
$is_new_session = empty($chat_history);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $botName ?> Virtual Chat</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f9; display: flex; flex-direction: column; align-items: center; min-height: 100vh; margin: 0; }
        .chat-wrapper { width: 450px; margin-top: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; }
        .chat-container {
            width: 100%;
            padding: 10px;
            height: 400px;
            overflow-y: auto;
            background-color: white;
        }
        .message-box {
            padding: 8px 12px; 
            margin-bottom: 8px; 
            border-radius: 15px; 
            max-width: 80%; 
            clear: both;
            line-height: 1.4;
            font-size: 0.95em;
        }
        
        /* Bot (Left aligned) */
        .bot-message { 
            background-color: #e0f7fa; 
            float: left; 
        }
        
        /* User (Right aligned) */
        .user-message { 
            background-color: #c9e6ce; 
            float: right; 
        }

        .input-area {
            display: flex;
            border-top: 1px solid #ddd;
            padding: 10px;
            background-color: #fff;
        }
        #userInput { flex-grow: 1; padding: 10px; border: 1px solid #ccc; border-radius: 20px; margin-right: 10px; }
        .send-btn { padding: 10px 15px; border: none; background-color: #007bff; color: white; border-radius: 20px; cursor: pointer; transition: background-color 0.3s; }
        .send-btn:hover { background-color: #0056b3; }
        .typing-indicator { font-style: italic; color: #6c757d; }
        .time-stamp { font-size: 0.6em; opacity: 0.7; margin-left: 10px; display: block; text-align: right; }
    </style>
</head>
<body>

<div class="chat-wrapper">
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 15px; background: #f8f9fa; border-bottom: 1px solid #e0e0e0;">
        <center style="flex: 1; margin: 0 15px; font-weight: bold; font-size: 16px;"><?= $botName ?> Virtual Chat</center>
    </div>
    <div class="chat-container" id="chatContainer">
        <?php foreach ($chat_history as $msg): ?>
            <div class="message-box <?= ($msg['sender'] == 'u') ? 'user-message' : 'bot-message' ?>">
                <?= strip_tags($msg['message'], '<a>') // htmlspecialchars($msg['message']) ?>
                <span class="time-stamp"><?= $msg['time'] ?></span>
            </div>
            <div style="clear: both;"></div>
        <?php endforeach; ?>
    </div>

    <div class="input-area">
        <input type="text" id="userInput" placeholder="Ask <?= $botName ?>...">
        <button class="send-btn" onclick="sendMessage()">Send</button>
    </div>
</div>

<script>
    const chatContainer = document.getElementById('chatContainer');
    const userInput = document.getElementById('userInput');
    let isTyping = false;
    const isNewSession = <?= $is_new_session ? 'true' : 'false' ?>;

    // ?? CONSTANT: Welcome messages moved from backend
    const welcomeMessages = [
        "Hello! How can I help you today?",
        "Hi there! What would you like to discuss?",
        "Welcome! I'm here to chat with you.",
        "Hello! How are you feeling today?",
        "Hi! I'm <?= $botName ?>, your virtual assistant.",
        "Hello! How can I assist you?"
    ];

    // Scroll to the bottom on load
    chatContainer.scrollTop = chatContainer.scrollHeight;

    document.addEventListener('DOMContentLoaded', () => {
        if (isNewSession) {
            const welcomeMessage = welcomeMessages[Math.floor(Math.random() * welcomeMessages.length)];
            sendBotMessage(welcomeMessage); 
        }
    });

    // Allows 'Enter' key to send message
    userInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    function appendMessage(sender, message, time) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `message-box ${sender === 'u' ? 'user-message' : 'bot-message'}`;
        
        let content = message;
        if (sender === 'u') {
            const tempDiv = document.createElement('div');
            tempDiv.textContent = message; // Amankan pesan pengguna
            content = tempDiv.innerHTML;
          }
        
        if (time) {
            content += `<span class="time-stamp">${time}</span>`;
        }

        msgDiv.innerHTML = content;
        chatContainer.appendChild(msgDiv);
        chatContainer.scrollTop = chatContainer.scrollHeight;
        return msgDiv;
    }
    
    function sendBotMessage(message) {
        if (!message) return;
        
        isTyping = true;
        
        const typingIndicator = appendMessage('b', '<span class="typing-indicator"><?= $botName ?> is thinking...</span>', null);

        // Calculate delay (Random 1s to 3s, proportional to response length)
        const baseDelay = 1000;
        const lengthDelay = message.length * 30; 
        const maxDelay = 3000;
        const randomDelay = Math.floor(Math.random() * 500); 
        const finalDelay = Math.min(maxDelay, baseDelay + lengthDelay + randomDelay);
        
        setTimeout(() => {
            typingIndicator.remove();
            const botTime = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            appendMessage('b', message, botTime);
            isTyping = false;
            userInput.focus();
        }, finalDelay);
    }

    function sendMessage() {
        const message = userInput.value.trim();
        if (!message || isTyping) return;

        isTyping = true;
        
        // 1. Display User Message immediately
        const userTime = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        appendMessage('u', message, userTime);
        userInput.value = '';

        // 2. Get Bot Response (AJAX)
        getBotResponse(message, 'send');
    }

    function getBotResponse(userMessage, action) {
        // This function is now ONLY called for 'send' action.
        const typingIndicator = appendMessage('b', '<span class="typing-indicator"><?= $botName ?> is thinking...</span>', null);
        
        const formData = new URLSearchParams();
        formData.append('action', action);
        formData.append('message', userMessage); // Always sends message for 'send' action

        fetch('cs_bot.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.response && data.response.includes('Invalid API action')) {
                throw new Error('API reported invalid action.');
            }

            // Calculate delay (using the same logic as sendBotMessage)
            const baseDelay = 1000;
            const lengthDelay = data.response.length * 44; 
            const maxDelay = 3000;
            const randomDelay = Math.floor(Math.random() * 500); 
            const finalDelay = Math.min(maxDelay, baseDelay + lengthDelay + randomDelay);
            
            setTimeout(() => {
                typingIndicator.remove();
                appendMessage('b', data.response, data.time);
                
                isTyping = false;
                userInput.focus();
            }, finalDelay);
        })
        .catch(error => {
            console.error('<?= $botName ?> connection Error:', error);
            typingIndicator.remove();
            appendMessage('b', 'SORRY, I FAILED TO CONNECT TO MY BRAIN. TRY AGAIN LATER.', new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }));
            isTyping = false;
            userInput.focus();
        });
    }
</script>
</body>
<!-- by sourceid - pls not remove credid -->

</html>
