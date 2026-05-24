const { GoogleGenerativeAI } = require('@google/generative-ai');

// System prompt for lead qualification
const SYSTEM_PROMPT = `Sei l'assistente virtuale di Davide Fiore, esperto di CyberSecurity, Sviluppo Software e AI.

REGOLE IMPORTANTI:
1. Il tuo obiettivo è CAPIRE cosa il cliente vuole realizzare, NON dare consigli tecnici
2. Fai domande per raccogliere informazioni sul progetto
3. NON fornire preventivi o stime di costo
4. Alla fine della conversazione, suggerisci di prenotare una call con Davide

INFORMAZIONI DA RACCOGLIERE:
- Tipo di progetto (Web App, App Mobile, AI/Chatbot, CRM, Sicurezza, altro)
- Se esiste già qualcosa o si parte da zero
- Tempistiche desiderate
- Budget indicativo (se lo condividono spontaneamente)
- Come preferiscono essere ricontattati

SERVIZI DI DAVIDE:
- Sviluppo App & Web
- CRM & Gestionali per PMI
- AI & Sistemi RAG (Chatbot personalizzati)
- Training AI & Security
- CyberSecurity Assessment

STILE:
- Professionale ma cordiale
- Risposte brevi e chiare
- Usa emoji con moderazione
- Rispondi SOLO in italiano

Quando hai raccolto abbastanza informazioni (tipo progetto, esigenze principali), suggerisci di prenotare una consulenza gratuita con Davide.`;

exports.handler = async (event, context) => {
    // CORS headers
    const headers = {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Headers': 'Content-Type',
        'Access-Control-Allow-Methods': 'POST, OPTIONS'
    };

    // Handle preflight
    if (event.httpMethod === 'OPTIONS') {
        return { statusCode: 200, headers, body: '' };
    }

    if (event.httpMethod !== 'POST') {
        return {
            statusCode: 405,
            headers,
            body: JSON.stringify({ error: 'Method not allowed' })
        };
    }

    try {
        const { message, history = [] } = JSON.parse(event.body);

        if (!message) {
            return {
                statusCode: 400,
                headers,
                body: JSON.stringify({ error: 'Message required' })
            };
        }

        // Initialize Gemini
        const genAI = new GoogleGenerativeAI(process.env.GEMINI_API_KEY);
        const model = genAI.getGenerativeModel({ model: 'gemini-1.5-flash' });

        // Build conversation history for context
        const chatHistory = history.map(msg => ({
            role: msg.role === 'assistant' ? 'model' : 'user',
            parts: [{ text: msg.content }]
        }));

        // Start chat with system instruction
        const chat = model.startChat({
            history: chatHistory,
            systemInstruction: SYSTEM_PROMPT,
            generationConfig: {
                maxOutputTokens: 500,
                temperature: 0.7,
            }
        });

        // Send message and get response
        const result = await chat.sendMessage(message);
        const reply = result.response.text();

        // Check if we should suggest booking
        const suggestBooking =
            reply.toLowerCase().includes('prenotare') ||
            reply.toLowerCase().includes('consulenza') ||
            reply.toLowerCase().includes('call') ||
            history.length >= 8;

        // Update context for next message
        const newContext = [
            ...history,
            { role: 'user', content: message },
            { role: 'assistant', content: reply }
        ];

        // If enough info collected, send email report (async, don't wait)
        if (suggestBooking && history.length >= 6) {
            sendReportEmail(newContext).catch(console.error);
        }

        return {
            statusCode: 200,
            headers,
            body: JSON.stringify({
                reply,
                context: newContext.slice(-10), // Keep last 10 messages
                suggestBooking
            })
        };

    } catch (error) {
        console.error('Chat error:', error);
        return {
            statusCode: 500,
            headers,
            body: JSON.stringify({
                error: 'Internal server error',
                reply: 'Mi scuso, ho avuto un problema tecnico. Riprova tra poco!'
            })
        };
    }
};

// Send email report (simplified - uses fetch to email service)
async function sendReportEmail(conversation) {
    const reportEmail = process.env.REPORT_EMAIL || 'info@davidefiore.com';

    // Format conversation as report
    const report = conversation.map(msg =>
        `${msg.role === 'user' ? '👤 Cliente' : '🤖 Bot'}: ${msg.content}`
    ).join('\n\n');

    const summary = `
📋 NUOVA RICHIESTA DAL CHATBOT

Data: ${new Date().toLocaleString('it-IT')}
Messaggi: ${conversation.length}

--- CONVERSAZIONE ---
${report}
---

Rispondi al cliente per approfondire!
    `.trim();

    // If using EmailJS or similar, send here
    // For now, log to console (visible in Netlify functions logs)
    console.log('=== CHAT REPORT ===');
    console.log(summary);
    console.log('===================');

    // TODO: Implement actual email sending with EmailJS/Resend
    // This can be added later with:
    // await fetch('https://api.emailjs.com/api/v1.0/email/send', {...})
}
