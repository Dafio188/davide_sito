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
        
        this.isDragging = false;
        this.dragStartX = 0;
        this.dragStartY = 0;
        this.hasMoved = false;

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
            <button class="chatbot-trigger" id="trigger-${this.config.id}" style="user-select: none;">
                <img src="assets/robot_avatar.png" alt="Chat" class="trigger-icon" draggable="false" />
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
        this.elements.trigger.addEventListener('mousedown', (e) => this.dragStart(e));
        this.elements.trigger.addEventListener('touchstart', (e) => this.dragStart(e), { passive: false });
        
        // Window close
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
            // SECURITY: Sanitize input first
            let safeText = text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");

            // Simple Markdown Parser for Bot Messages
            let html = safeText
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

    dragStart(e) {
        if (e.type === 'mousedown') e.preventDefault();
        this.isDragging = true;
        this.hasMoved = false;
        
        const clientX = e.type === 'touchstart' ? e.touches[0].clientX : e.clientX;
        const clientY = e.type === 'touchstart' ? e.touches[0].clientY : e.clientY;
        
        this.dragStartX = clientX;
        this.dragStartY = clientY;
        
        const rect = this.elements.trigger.getBoundingClientRect();
        this.dragOffsetX = clientX - rect.left;
        this.dragOffsetY = clientY - rect.top;
        
        this.elements.trigger.classList.add('dragging');
        
        this.onMouseMove = (e) => this.dragMove(e);
        this.onMouseUp = () => this.dragEnd();
        
        window.addEventListener('mousemove', this.onMouseMove);
        window.addEventListener('mouseup', this.onMouseUp);
        window.addEventListener('touchmove', this.onMouseMove, { passive: false });
        window.addEventListener('touchend', this.onMouseUp);
    }

    dragMove(e) {
        if (!this.isDragging) return;
        
        const clientX = e.type === 'touchmove' ? e.touches[0].clientX : e.clientX;
        const clientY = e.type === 'touchmove' ? e.touches[0].clientY : e.clientY;
        
        // Prevent scrolling on touch
        if (e.type === 'touchmove') e.preventDefault();
        
        const x = clientX - this.dragOffsetX;
        const y = clientY - this.dragOffsetY;
        
        const maxX = window.innerWidth - this.elements.trigger.offsetWidth;
        const maxY = window.innerHeight - this.elements.trigger.offsetHeight;
        
        const safeX = Math.max(0, Math.min(x, maxX));
        const safeY = Math.max(0, Math.min(y, maxY));
        
        this.elements.trigger.style.left = `${safeX}px`;
        this.elements.trigger.style.top = `${safeY}px`;
        this.elements.trigger.style.right = 'auto';
        this.elements.trigger.style.bottom = 'auto';
        
        this.updateWindowPosition(safeX, safeY);
        
        if (Math.abs(clientX - this.dragStartX) > 5 || Math.abs(clientY - this.dragStartY) > 5) {
            this.hasMoved = true;
        }
    }

    dragEnd() {
        if (!this.isDragging) return;
        this.isDragging = false;
        this.elements.trigger.classList.remove('dragging');
        
        window.removeEventListener('mousemove', this.onMouseMove);
        window.removeEventListener('mouseup', this.onMouseUp);
        window.removeEventListener('touchmove', this.onMouseMove);
        window.removeEventListener('touchend', this.onMouseUp);
        
        if (!this.hasMoved) {
            this.toggle();
        }
    }

    updateWindowPosition(x, y) {
        const onRightSide = x > window.innerWidth / 2;
        const onBottomHalf = y > window.innerHeight / 2;
        
        if (onRightSide) {
            this.elements.window.style.right = `${window.innerWidth - (x + this.elements.trigger.offsetWidth / 2)}px`;
            this.elements.window.style.left = 'auto';
        } else {
            this.elements.window.style.left = `${x}px`;
            this.elements.window.style.right = 'auto';
        }
        
        if (onBottomHalf) {
            this.elements.window.style.bottom = `${window.innerHeight - y + 20}px`;
            this.elements.window.style.top = 'auto';
        } else {
            this.elements.window.style.top = `${y + this.elements.trigger.offsetHeight + 20}px`;
            this.elements.window.style.bottom = 'auto';
        }
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
