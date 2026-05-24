# 🚀 ISTRUZIONI DEPLOY SU ARUBA (Aggiornato 13/05/2026)

## File da caricare
`deploy_v25.zip`

## Procedura step-by-step:

### 1. BACKUP PREVENTIVO
Prima di sovrascrivere, fai un backup del sito attuale:
- Accedi al File Manager di Aruba
- Seleziona tutti i file nella root (public_html o www)
- Scaricali come backup

### 2. CARICAMENTO ZIP
- Carica il file `deploy_v25.zip` nella cartella principale del sito
- Estrai lo zip **sovrascrivendo** i file esistenti

### 3. VERIFICA FILE CRITICI
Assicurati che questi file siano presenti:
- ✅ api/chat.php (Standard Cal.com v2)
- ✅ index.html (v=25 per cache busting)
- ✅ js/chatbot.js (v=25)
- ✅ ai-rag.html, web-development.html, performance-seo.html, ai-training.html, crm-gestionali.html
- ✅ robots.txt e sitemap.xml (OTTIMIZZATI)

### 4. SVUOTA CACHE BROWSER
**IMPORTANTE!** Dopo il caricamento:
- Premi `CTRL + F5` (Windows) o `CMD + SHIFT + R` (Mac)
- La versione **v=25** forzerà il caricamento dei nuovi file API e stili.

### 5. VERIFICA FUNZIONALE
Controlla che:
- La chat cliccabile funzioni regolarmente.
- La prenotazione appuntamenti (Cal.com) sia fluida.
- I nuovi progetti in "Personal Lab" (SoftMatch e CercArtigiano) siano visibili.

---

## 📝 Modifiche tecniche 13/05/2026:

1. **Personal Lab Expansion**:
   - Aggiunti CercArtigiano.com e SoftMatch.it.
   - Generazione preview AI personalizzate.

2. **SEO & Indexing Mastery**:
   - Ottimizzazione spinta di `sitemap.xml` con Image Tags.
   - Hardening di `robots.txt` per proteggere file sensibili e script.
   - Aggiornamento metadati `lastmod`.

3. **Cache Busting Globale**:
   - Versione CSS/JS aggiornata alla **v=25**.

---
*Per supporto tecnico durante il deploy, consultare i log in `api/tmp/cal_v2_debug.log`.*
