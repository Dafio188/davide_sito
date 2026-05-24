/**
 * AI Chatbot Widget (Multi-Instance)
 * Refactored to support both Floating Assistant and Inline Analyst
 */

class DavideAIWidget {
    constructor(config) {
        this.config = {
            id: 'default',
            containerId: null, // If null, renders as floating widget
            inputTriggerId: null, // ID of external input to bind to (for Analyst)
            mode: 'standard', // 'standard' or 'analyst'
            botName: 'Assistente Davide',
            avatar: 'assets/robot_header.png',
            welcomeMessage: 'Ciao! Come posso aiutarti?',
            apiEndpoint: 'api/chat.php',
            ...config
        };

        this.isOpen = false;
        this.messages = [];
        this.context = [];
        this.isTyping = false;
        this.elements = {};

        this.init();
    }

    init() {
        if (this.config.containerId) {
            this.renderInline();
        } else {
            this.renderFloating();
        }
        this.bindInputTrigger();
    }

    renderFloating() {
        const widget = document.createElement('div');
        widget.id = `chatbot-widget-${this.config.id}`;
        widget.innerHTML = `
            <button class="chatbot-trigger" id="trigger-${this.config.id}">
                <img src="assets/robot_avatar.png" alt="Chat" class="trigger-icon" />
                <svg class="close-icon" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18" stroke="white" stroke-width="2"/><line x1="6" y1="6" x2="18" y2="18" stroke="white" stroke-width="2"/></svg>
            </button>
            <div class="chatbot-window" id="window-${this.config.id}">
                <div class="chatbot-header">
                    <div class="chatbot-avatar"><img src="${this.config.avatar}" /></div>
                    <div class="chatbot-info"><h4>${this.config.botName}</h4><p>Di solito risponde subito</p></div>
                    <button class="chatbot-header-close" id="close-${this.config.id}"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
                </div>
                <div class="chatbot-messages" id="msgs-${this.config.id}"></div>
                <div class="chatbot-input">
                    <input type="text" id="input-${this.config.id}" placeholder="Scrivi un messaggio..." autocomplete="off">
                    <button id="send-${this.config.id}"><svg viewBox="0 0 24 24"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg></button>
                </div>
            </div>
        `;
        document.body.appendChild(widget);

        // Bind Elements
        this.elements.window = document.getElementById(`window-${this.config.id}`);
        this.elements.msgs = document.getElementById(`msgs-${this.config.id}`);
        this.elements.input = document.getElementById(`input-${this.config.id}`);
        this.elements.send = document.getElementById(`send-${this.config.id}`);
        this.elements.trigger = document.getElementById(`trigger-${this.config.id}`);
        this.elements.close = document.getElementById(`close-${this.config.id}`);

        // Events
        this.elements.trigger.addEventListener('click', () => this.toggle());
        this.elements.close.addEventListener('click', () => this.toggle());

        this.addBotMessage(this.config.welcomeMessage);
        this.attachCommonEvents();

        // Listen for global triggers linked to this instance
        document.querySelectorAll('.trigger-chat-open').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                if (!this.isOpen) this.toggle();
            });
        });
    }

    renderInline() {
        const container = document.getElementById(this.config.containerId);
        if (!container) return;

        // Inline Terminal Structure
        container.innerHTML = `
            <div class="chatbot-messages" id="msgs-${this.config.id}" style="height: 300px; padding: 15px; overflow-y: auto;"></div>
            <div class="chatbot-input" style="border-top: 1px solid rgba(0,0,0,0.05);">
                <input type="text" id="input-${this.config.id}" placeholder="Richiedi dettagli tecnici..." autocomplete="off">
                <button id="send-${this.config.id}"><svg viewBox="0 0 24 24"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg></button>
            </div>
        `;

        // Hide by default until triggered
        container.style.display = 'none';

        this.elements.window = container;
        this.elements.msgs = document.getElementById(`msgs-${this.config.id}`);
        this.elements.input = document.getElementById(`input-${this.config.id}`);
        this.elements.send = document.getElementById(`send-${this.config.id}`);

        // No close/trigger buttons for inline (controlled externally)
        this.attachCommonEvents();
    }

    attachCommonEvents() {
        this.elements.send.addEventListener('click', () => this.sendMessage());
        this.elements.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendMessage();
        });
    }

    toggle() {
        this.isOpen = !this.isOpen;
        this.elements.window.classList.toggle('open', this.isOpen);
        if (this.elements.trigger) this.elements.trigger.classList.toggle('active', this.isOpen);
        if (this.isOpen) setTimeout(() => this.elements.input.focus(), 100);
    }

    addMessage(text, isBot) {
        const msg = document.createElement('div');
        msg.className = `chat-message ${isBot ? 'bot' : 'user'}`;

        // Analyst Mode Styling
        if (this.config.mode === 'analyst' && isBot) {
            msg.style.fontFamily = 'JetBrains Mono, monospace';
            msg.style.fontSize = '0.9rem';
        }

        if (isBot) {
            // Simple Markdown Parser for Bot Messages
            let html = text
                // Bold: **text**
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                // Lists: - item
                .replace(/^- (.*$)/gm, '<li>$1</li>')
                // Newlines to <br>
                .replace(/\n/g, '<br>');

            // Naive list wrapping (groups of <li>)
            html = html.replace(/((?:<li>.*?<\/li>(?:<br>)?)+)/g, '<ul style="margin: 10px 0; padding-left: 20px;">$1</ul>');

            msg.innerHTML = html;
        } else {
            msg.textContent = text;
        }

        this.elements.msgs.appendChild(msg);
        this.elements.msgs.scrollTop = this.elements.msgs.scrollHeight;
    }

    addBotMessage(text) {
        this.addMessage(text, true);
        this.context.push({ role: 'assistant', content: text });
    }

    addUserMessage(text) {
        this.addMessage(text, false);
        this.context.push({ role: 'user', content: text });
    }

    async sendMessage(overrideText = null) {
        const text = overrideText || this.elements.input.value.trim();
        if (!text || this.isTyping) return;

        this.addUserMessage(text);
        this.elements.input.value = '';
        this.isTyping = true;

        // Typing indicator
        const typing = document.createElement('div');
        typing.className = 'chat-message bot typing';
        typing.innerHTML = `<span>.</span><span>.</span><span>.</span>`;
        this.elements.msgs.appendChild(typing);
        this.elements.msgs.scrollTop = this.elements.msgs.scrollHeight;

        try {
            const response = await fetch(this.config.apiEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: text,
                    history: this.context,
                    mode: this.config.mode // Pass mode to backend
                })
            });

            const data = await response.json();
            typing.remove();

            if (data.error && data.reply) {
                // Show specific error from server (Debug Mode)
                this.addBotMessage(data.reply);
            } else if (data.error) {
                this.addBotMessage("Errore di connessione. Riprova.");
            } else {
                this.addBotMessage(data.reply);
            }
        } catch (e) {
            typing.remove();
            this.addBotMessage("Errore critico (JS). Contatta il supporto.");
            console.error(e);
        }

        this.isTyping = false;
        this.elements.input.focus();
    }

    bindInputTrigger() {
        if (!this.config.inputTriggerId) return;

        const btn = document.getElementById(this.config.inputTriggerId.btn);
        const input = document.getElementById(this.config.inputTriggerId.input); // Textarea
        const containerToHide = document.getElementById(this.config.inputTriggerId.hideContainer); // Wrapper to hide

        if (btn && input) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const text = input.value.trim();

                if (text) {
                    // UI Transition
                    if (containerToHide) containerToHide.style.display = 'none'; // Hide inputs
                    this.elements.window.style.display = 'block'; // Show Chat

                    // Send first message
                    this.sendMessage(text);
                } else {
                    input.focus();
                    input.style.borderColor = 'red';
                    setTimeout(() => input.style.borderColor = '', 1000);
                }
            });
        }
    }
}

// Instantiate
document.addEventListener('DOMContentLoaded', () => {
    // 1. Standard Assistant (Floating)
    new DavideAIWidget({
        id: 'main',
        mode: 'standard'
    });


});
