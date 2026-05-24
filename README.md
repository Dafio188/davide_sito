# Antigravity OS - DavideFiore.com 🪐

Sito web premium con design "Apple-feel", animazioni fluide e chatbot AI integrato.

## 🚀 Ultime Modifiche (11/04/2026)

### 📅 Migrazione Cal.com API v2
- **Completata la migrazione** dall'API v1 alla v2 per la gestione degli appuntamenti.
- **Payload "Advanced"**: Implementata la struttura `bookingFieldsResponses` per il corretto invio dei campi personalizzati (`title`, `notes`, `name`, `email`).
- **Fix Timezone**: Risolto il problema di formattazione del fuso orario (`Europe/Rome`) assicurando l'invio di stringhe IANA pulite.
- **Conversione UTC**: Implementata la conversione automatica degli orari in formato ISO 8601 UTC richiesto dal nuovo standard dell'API.
- **Cache Busting**: Aggiornata la versione di tutti gli asset (CSS/JS) alla **v23** per forzare il refresh sui browser degli utenti.

### 🤖 Chatbot AI & RAG
- Ottimizzazione della logica di cattura contatti.
- Integrazione sistema di guida gratuita (Security Hub).
- Debugging avanzato tramite file di log temporanei (`api/tmp/cal_v2_debug.log`).

---

## 🏗️ Stack Tecnologico
- **Frontend**: HTML5, Vanilla CSS (Glassmorphism), JavaScript (ES6+).
- **Animazioni**: Framer Motion (concept), Lenis (smooth scroll), Custom 3D Globe (Three.js).
- **Backend**: PHP 8.x per gestione API e Chatbot.
- **AI**: Google Gemini API.
- **Booking**: Cal.com API v2.

---

## 📦 Deployment
Per generare il pacchetto di deployment:
```powershell
python make_deploy_zip.py
```
Il comando genererà il file `deploy_v23.zip` pronto per il caricamento su Aruba.

---
*Ultimo aggiornamento: 11/04/2026 - Agentic Coding Session*
