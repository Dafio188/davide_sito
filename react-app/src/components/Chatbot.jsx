import React, { useState, useEffect, useRef } from 'react';
import './Chatbot.css';

const CONFIG = {
    botName: 'Assistente Davide',
    welcomeMessage: 'Ciao! 👋 Sono l\'assistente virtuale di Davide. Sono qui per capire meglio il tuo progetto e aiutarti a prenotare un appuntamento. Come posso aiutarti oggi?',
    quickActions: [
        'Ho un\'idea per un progetto',
        'Vorrei informazioni sui servizi',
        'Vorrei un appuntamento'
    ],
    maxMessages: 20,
    apiEndpoint: '/api/chat.php'
};

const Chatbot = () => {
    const [isOpen, setIsOpen] = useState(false);
    const [messages, setMessages] = useState([]);
    const [inputValue, setInputValue] = useState('');
    const [isTyping, setIsTyping] = useState(false);
    const [conversationContext, setConversationContext] = useState([]);
    const [messageCount, setMessageCount] = useState(0);
    const messagesEndRef = useRef(null);

    useEffect(() => {
        // Show welcome message after mount
        setTimeout(() => {
            addBotMessage(CONFIG.welcomeMessage, true);
        }, 500);
    }, []);

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    const addBotMessage = (content, showQuickActions = false) => {
        setMessages(prev => [...prev, { content, isBot: true, showQuickActions }]);
    };

    const addUserMessage = (content) => {
        setMessages(prev => [...prev, { content, isBot: false }]);
        setMessageCount(prev => prev + 1);
    };

    const handleSend = async () => {
        const content = inputValue.trim();
        if (!content || isTyping) return;

        if (messageCount >= CONFIG.maxMessages) {
            addBotMessage('Abbiamo raggiunto il limite di messaggi per questa sessione. Per continuare, contatta Davide a info@davidefiore.com 📧');
            return;
        }

        addUserMessage(content);
        setInputValue('');
        setIsTyping(true);

        try {
            const response = await fetch(CONFIG.apiEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: content,
                    history: conversationContext
                })
            });

            if (!response.ok) throw new Error('API error');

            const data = await response.json();
            setIsTyping(false);
            addBotMessage(data.reply);

            if (data.context) {
                setConversationContext(data.context);
            }

            if (data.suggestBooking) {
                setTimeout(() => {
                    addBotMessage('📧 Perfetto! Ho inviato un riepilogo a Davide che ti ricontatterà presto per fissare l\'appuntamento. Grazie per avermi contattato!');
                }, 1000);
            }

        } catch (error) {
            console.error('Chat error:', error);
            setIsTyping(false);
            addBotMessage('Mi scuso, ho avuto un problema tecnico. Puoi contattare Davide direttamente a info@davidefiore.com');
        }
    };

    const handleQuickAction = (action) => {
        setInputValue(action);
        setTimeout(() => {
            setInputValue('');
            addUserMessage(action);
            setIsTyping(true);
            // Simulate response for quick actions
            setTimeout(() => {
                setIsTyping(false);
                if (action.includes('progetto')) {
                    addBotMessage('Fantastico! 🚀 Mi piacerebbe saperne di più. Che tipo di progetto hai in mente? (Web App, App Mobile, AI/Chatbot, CRM, altro?)');
                } else if (action.includes('servizi')) {
                    addBotMessage('Davide offre: 💻 Sviluppo App & Web, 📊 CRM & Gestionali, 🧠 AI & Sistemi RAG, 🎓 Training, 🔐 CyberSecurity. Quale ti interessa di più?');
                } else if (action.includes('appuntamento')) {
                    addBotMessage('Perfetto! Per fissare un appuntamento, lasciami il tuo nome e email così Davide ti ricontatterà. 📧');
                }
            }, 1500);
        }, 100);
    };

    const handleKeyPress = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    };

    return (
        <>
            <button
                className={`chatbot-trigger ${isOpen ? 'active' : ''}`}
                onClick={() => setIsOpen(!isOpen)}
                aria-label="Apri chat"
            >
                <svg className="chat-icon" viewBox="0 0 24 24">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
                </svg>
                <svg className="close-icon" viewBox="0 0 24 24">
                    <line x1="18" y1="6" x2="6" y2="18" stroke="white" strokeWidth="2" strokeLinecap="round" />
                    <line x1="6" y1="6" x2="18" y2="18" stroke="white" strokeWidth="2" strokeLinecap="round" />
                </svg>
            </button>

            <div className={`chatbot-window ${isOpen ? 'open' : ''}`}>
                <div className="chatbot-header">
                    <div className="chatbot-avatar">🤖</div>
                    <div className="chatbot-info">
                        <h4>{CONFIG.botName}</h4>
                        <p>Di solito risponde subito</p>
                    </div>
                </div>

                <div className="chatbot-messages">
                    {messages.map((msg, index) => (
                        <React.Fragment key={index}>
                            <div className={`chat-message ${msg.isBot ? 'bot' : 'user'}`}>
                                {msg.content}
                            </div>
                            {msg.showQuickActions && (
                                <div className="quick-actions">
                                    {CONFIG.quickActions.map((action, i) => (
                                        <button
                                            key={i}
                                            className="quick-action-btn"
                                            onClick={() => handleQuickAction(action)}
                                        >
                                            {action}
                                        </button>
                                    ))}
                                </div>
                            )}
                        </React.Fragment>
                    ))}
                    {isTyping && (
                        <div className="chat-message bot typing">
                            <span className="typing-dot"></span>
                            <span className="typing-dot"></span>
                            <span className="typing-dot"></span>
                        </div>
                    )}
                    <div ref={messagesEndRef} />
                </div>

                <div className="chatbot-input">
                    <input
                        type="text"
                        placeholder="Scrivi un messaggio..."
                        value={inputValue}
                        onChange={(e) => setInputValue(e.target.value)}
                        onKeyPress={handleKeyPress}
                        autoComplete="off"
                    />
                    <button onClick={handleSend} disabled={isTyping} aria-label="Invia">
                        <svg viewBox="0 0 24 24">
                            <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" />
                        </svg>
                    </button>
                </div>
            </div>
        </>
    );
};

export default Chatbot;
