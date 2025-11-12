<?php
/**
 * Reusable Chatbot Widget
 * Include this file in any page to add the chatbot functionality
 * 
 * Usage: <?php include 'includes/chatbot_widget.php'; ?>
 */
?>

<!-- Chatbot Toggle Button -->
<div id="chatbot-toggle" class="chatbot-toggle">
    <i class="fas fa-comments"></i>
    <span class="chatbot-badge">1</span>
</div>

<!-- Chatbot Container -->
<div id="chatbot-container" class="chatbot-container">
    <div class="chatbot-header">
        <div class="chatbot-header-content">
            <div class="chatbot-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="chatbot-title">
                <h5><?php echo __('mjengo_assistant'); ?></h5>
                <span class="chatbot-status">
                    <span class="status-dot"></span> <?php echo __('online'); ?>
                </span>
            </div>
        </div>
        <button id="chatbot-minimize" class="chatbot-btn-minimize">
            <i class="fas fa-minus"></i>
        </button>
    </div>
    
    <div class="chatbot-messages" id="chatbot-messages">
        <div class="chatbot-message bot-message">
            <div class="message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="message-content">
                <p><?php echo __('chatbot_welcome'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="chatbot-suggestions" id="chatbot-suggestions">
        <!-- Dynamic suggestions will be inserted here -->
    </div>
    
    <div class="chatbot-input-container">
        <div class="chatbot-input-wrapper">
            <button class="chatbot-btn-voice" id="voice-input-btn" title="Voice Input">
                <i class="fas fa-microphone"></i>
            </button>
            <input type="text" 
                   id="chatbot-input" 
                   class="chatbot-input" 
                   placeholder="<?php echo __('type_message'); ?>..." 
                   autocomplete="off">
            <button class="chatbot-btn-send" id="chatbot-send">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
        <div class="chatbot-typing-indicator" id="typing-indicator" style="display: none;">
            <span></span><span></span><span></span>
        </div>
    </div>
</div>

<!-- Chatbot Styles -->
<style>
/* Chatbot Toggle Button */
.chatbot-toggle {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #1a5276, #2980b9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(26, 82, 118, 0.4);
    z-index: 9998;
    transition: all 0.3s ease;
    animation: pulse 2s infinite;
}

.chatbot-toggle:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(26, 82, 118, 0.6);
}

.chatbot-toggle i {
    font-size: 28px;
    color: white;
}

.chatbot-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    border: 2px solid white;
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 4px 20px rgba(26, 82, 118, 0.4), 0 0 0 0 rgba(26, 82, 118, 0.4);
    }
    50% {
        box-shadow: 0 4px 20px rgba(26, 82, 118, 0.4), 0 0 0 10px rgba(26, 82, 118, 0);
    }
}

/* Chatbot Container */
.chatbot-container {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 380px;
    max-width: calc(100vw - 40px);
    height: 600px;
    max-height: calc(100vh - 120px);
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    display: none;
    flex-direction: column;
    z-index: 9999;
    overflow: hidden;
    animation: slideUp 0.3s ease;
}

.chatbot-container.active {
    display: flex;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Chatbot Header */
.chatbot-header {
    background: linear-gradient(135deg, #1a5276, #2980b9);
    color: white;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-radius: 20px 20px 0 0;
}

.chatbot-header-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.chatbot-avatar {
    width: 45px;
    height: 45px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.chatbot-title h5 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.chatbot-status {
    font-size: 12px;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 5px;
}

.status-dot {
    width: 8px;
    height: 8px;
    background: #2ecc71;
    border-radius: 50%;
    animation: blink 1.5s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.chatbot-btn-minimize {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.chatbot-btn-minimize:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

/* Chatbot Messages */
.chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f8f9fa;
    scroll-behavior: smooth;
}

.chatbot-message {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    animation: messageSlide 0.3s ease;
}

@keyframes messageSlide {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 16px;
}

.bot-message .message-avatar {
    background: linear-gradient(135deg, #1a5276, #2980b9);
    color: white;
}

.user-message {
    flex-direction: row-reverse;
}

.user-message .message-avatar {
    background: linear-gradient(135deg, #f39c12, #e67e22);
    color: white;
}

.message-content {
    max-width: 75%;
    padding: 12px 16px;
    border-radius: 18px;
    word-wrap: break-word;
    line-height: 1.5;
}

.bot-message .message-content {
    background: white;
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.user-message .message-content {
    background: linear-gradient(135deg, #1a5276, #2980b9);
    color: white;
    border-bottom-right-radius: 4px;
    box-shadow: 0 2px 8px rgba(26, 82, 118, 0.3);
}

.message-content p {
    margin: 0;
    font-size: 14px;
}

.message-content strong {
    font-weight: 600;
}

.message-content a {
    color: #2980b9;
    text-decoration: underline;
    font-weight: 500;
}

.user-message .message-content a {
    color: #f39c12;
}

/* Chatbot Suggestions */
.chatbot-suggestions {
    padding: 10px 20px;
    background: white;
    border-top: 1px solid #e9ecef;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    max-height: 100px;
    overflow-x: auto;
}

.suggestion-btn {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    color: #495057;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}

.suggestion-btn:hover {
    background: linear-gradient(135deg, #1a5276, #2980b9);
    color: white;
    border-color: #1a5276;
    transform: translateY(-2px);
}

.suggestion-btn i {
    font-size: 12px;
}

/* Chatbot Input */
.chatbot-input-container {
    background: white;
    border-top: 1px solid #e9ecef;
    padding: 15px 20px;
}

.chatbot-input-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8f9fa;
    border-radius: 25px;
    padding: 8px 12px;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.chatbot-input-wrapper:focus-within {
    border-color: #1a5276;
    background: white;
}

.chatbot-btn-voice {
    background: transparent;
    border: none;
    color: #6c757d;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.chatbot-btn-voice:hover {
    background: #e9ecef;
    color: #1a5276;
}

.chatbot-btn-voice.recording {
    color: #e74c3c;
    animation: recordPulse 1s infinite;
}

@keyframes recordPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

.chatbot-input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 8px;
    font-size: 14px;
    outline: none;
}

.chatbot-input::placeholder {
    color: #adb5bd;
}

.chatbot-btn-send {
    background: linear-gradient(135deg, #1a5276, #2980b9);
    border: none;
    color: white;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.chatbot-btn-send:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(26, 82, 118, 0.3);
}

.chatbot-btn-send:active {
    transform: scale(0.95);
}

/* Typing Indicator */
.chatbot-typing-indicator {
    display: flex;
    gap: 4px;
    padding: 10px 0 5px 0;
    align-items: center;
}

.chatbot-typing-indicator span {
    width: 8px;
    height: 8px;
    background: #6c757d;
    border-radius: 50%;
    animation: typingBounce 1.4s infinite;
}

.chatbot-typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.chatbot-typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typingBounce {
    0%, 60%, 100% {
        transform: translateY(0);
    }
    30% {
        transform: translateY(-10px);
    }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .chatbot-container {
        width: 100%;
        height: 100%;
        max-width: 100%;
        max-height: 100%;
        bottom: 0;
        right: 0;
        border-radius: 0;
    }
    
    .chatbot-toggle {
        bottom: 20px;
        right: 20px;
        width: 55px;
        height: 55px;
    }
    
    .chatbot-toggle i {
        font-size: 24px;
    }
    
    .chatbot-header {
        border-radius: 0;
    }
}

/* Scrollbar Styling */
.chatbot-messages::-webkit-scrollbar {
    width: 6px;
}

.chatbot-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.chatbot-messages::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 3px;
}

.chatbot-messages::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}
</style>

<!-- Chatbot JavaScript -->
<script>
class MjengoChatbot {
    constructor() {
        this.sessionId = this.getSessionId();
        this.conversationHistory = [];
        this.apiPath = window.location.pathname.includes('/core/') ? '../chatbot.php' : 'chatbot.php';
        this.initElements();
        this.initEventListeners();
    }
    
    getSessionId() {
        let id = localStorage.getItem('chatbot_session');
        if (!id) {
            id = 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('chatbot_session', id);
        }
        return id;
    }
    
    initElements() {
        this.toggle = document.getElementById('chatbot-toggle');
        this.container = document.getElementById('chatbot-container');
        this.minimize = document.getElementById('chatbot-minimize');
        this.messages = document.getElementById('chatbot-messages');
        this.input = document.getElementById('chatbot-input');
        this.sendBtn = document.getElementById('chatbot-send');
        this.suggestions = document.getElementById('chatbot-suggestions');
        this.typing = document.getElementById('typing-indicator');
    }
    
    initEventListeners() {
        this.toggle.addEventListener('click', () => this.toggleChat());
        this.minimize.addEventListener('click', () => this.toggleChat());
        this.sendBtn.addEventListener('click', () => this.sendMessage());
        this.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.sendMessage();
            }
        });
    }
    
    toggleChat() {
        this.container.classList.toggle('active');
        if (this.container.classList.contains('active')) {
            this.input.focus();
        }
    }
    
    async sendMessage() {
        const msg = this.input.value.trim();
        if (!msg) return;
        
        this.input.value = '';
        this.addMessage(msg, 'user');
        this.typing.style.display = 'flex';
        
        try {
            const response = await fetch(this.apiPath, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({message: msg, session_id: this.sessionId})
            });
            
            const data = await response.json();
            await new Promise(r => setTimeout(r, 800));
            
            this.typing.style.display = 'none';
            this.addMessage(data.reply, 'bot');
            
            if (data.suggestions) {
                this.showSuggestions(data.suggestions);
            }
        } catch (error) {
            this.typing.style.display = 'none';
            this.addMessage('Sorry, error occurred. Call +255 714 859 934', 'bot');
        }
    }
    
    addMessage(text, type) {
        const div = document.createElement('div');
        div.className = `chatbot-message ${type}-message`;
        div.innerHTML = `
            <div class="message-avatar">
                <i class="fas fa-${type === 'bot' ? 'robot' : 'user'}"></i>
            </div>
            <div class="message-content">
                <p>${text.replace(/\n/g, '<br>')}</p>
            </div>
        `;
        this.messages.appendChild(div);
        this.messages.scrollTop = this.messages.scrollHeight;
    }
    
    showSuggestions(suggestions) {
        this.suggestions.innerHTML = '';
        suggestions.forEach(s => {
            const btn = document.createElement('button');
            btn.className = 'suggestion-btn';
            btn.innerHTML = `<i class="fas ${s.icon}"></i> ${s.text}`;
            btn.onclick = () => {
                this.input.value = s.text;
                this.sendMessage();
            };
            this.suggestions.appendChild(btn);
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.chatbot = new MjengoChatbot();
});
</script>
