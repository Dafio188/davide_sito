# Deploy Chatbot su Aruba

## File da Caricare

Carica questi file/cartelle nella root del tuo sito su Aruba:

```
📁 api/
   └── chat.php          ← Backend Gemini + Email
📁 css/
   └── chatbot.css       ← Stili chat widget
📁 js/
   └── chatbot.js        ← Frontend chat
```

## Struttura Finale su Aruba

```
public_html/
├── index.html
├── api/
│   └── chat.php         ← NUOVO
├── css/
│   ├── style.css
│   ├── security-network.css
│   └── chatbot.css      ← NUOVO
├── js/
│   ├── main.js
│   ├── security-network.js
│   └── chatbot.js       ← NUOVO
└── assets/
    └── ...
```

## Verifica Dopo il Deploy

1. Vai su `https://davidefiore.com`
2. Clicca sulla bolla chat blu in basso a destra
3. Scrivi un messaggio e verifica che risponda
4. Dopo 6+ messaggi, riceverai email a info@davidefiore.com

## Troubleshooting

### Il chatbot non risponde?
- Verifica che PHP sia attivo su Aruba
- Controlla i log errori in Aruba panel
- Testa `https://davidefiore.com/api/chat.php` direttamente

### Non ricevo email?
- Verifica che `mail()` PHP sia abilitato su Aruba
- Controlla la cartella spam
- Contatta supporto Aruba per SMTP

## Sicurezza

✅ **API Key Protette**: Le chiavi sono ora caricate SOLO dal file `config.php` (che è nel .gitignore).
✅ **Rate Limiting**: Attivo per prevenire abusi (max 20 req/min per IP).
✅ **Sanitizzazione Input**: Frontend protetto da XSS.
✅ **SSL**: Verifica certificati attiva per comunicazioni sicure con Google/Cal.com.

### Post-Deploy
1. Assicurati che la cartella `api/tmp` abbia permessi di scrittura (755).
2. Se ricevi errori 500 su Aruba per SSL, contatta l'assistenza per aggiornare i certificati CA.
