# 🚀 ISTRUZIONI DEPLOY SU ARUBA (Aggiornato 24/05/2026)

## File da caricare
`deploy_v26.zip`

## Procedura step-by-step:

### 1. BACKUP PREVENTIVO
Prima di sovrascrivere, fai un backup del sito attuale:
- Accedi al File Manager di Aruba
- Seleziona tutti i file nella root (public_html o www)
- Scaricali come backup

### 2. CARICAMENTO ZIP
- Carica il file `deploy_v26.zip` nella cartella principale del sito
- Estrai lo zip **sovrascrivendo** i file esistenti

### 3. VERIFICA FILE CRITICI
Assicurati che questi file siano presenti:
- ✅ api/chat.php (Standard Cal.com v2)
- ✅ api/db.php (Helper SQLite auto-inizializzante) [NEW]
- ✅ api/news_import.php (API Importation News) [NEW]
- ✅ data/.htaccess (Sicurezza database) [NEW]
- ✅ data/news.db (Database SQLite pre-popolato) [NEW]
- ✅ news.php (Pagina notizie premium) [NEW]
- ✅ css/news.css (Stili notizie e modale) [NEW]
- ✅ index.html, ai-rag.html, web-development.html, performance-seo.html, ai-training.html, crm-gestionali.html (con navbar aggiornate)

### 4. SVUOTA CACHE BROWSER
**IMPORTANTE!** Dopo il caricamento:
- Premi `CTRL + F5` (Windows) o `CMD + SHIFT + R` (Mac)
- Svuota la cache del browser per assicurarti di vedere i menù aggiornati.

### 5. VERIFICA FUNZIONALE
Controlla che:
- La nuova pagina `news.php` si carichi e mostri i 3 articoli di prova.
- Cliccando sulle card si apra la modale glassmorphic di dettaglio.
- L'URL `https://www.davidefiore.com/data/news.db` restituisca errore `403 Forbidden` (confermando la protezione attiva).

---

## 📝 Modifiche tecniche 24/05/2026:

1. **Tech News Integration**:
   - Creato database SQLite con tabella `tech_news` per l'invio quotidiano delle notizie tramite Gemini.
   - Protetto il database con htaccess dedicato.
   - Endpoint API `api/news_import.php` protetto da X-Api-Key.
   - Frontend premium `news.php` Apple-style.

2. **Navbar Global Upgrade**:
   - Aggiunto il link "News" alle barre di navigazione di tutte le pagine.

---
*Per supporto tecnico durante il deploy, consultare i log in `api/tmp/cal_v2_debug.log`.*
