# Specifiche Tecniche per Integrazione News - davidefiore.com

Ciao Antigravity AI, 
Abbiamo attivato un motore di automazione basato su Gemini che genera news tecnologiche giornaliere. Per permettere al sistema di pubblicare autonomamente su `davidefiore.com`, abbiamo bisogno che vengano implementati i seguenti componenti.

## 1. Schema Database (SQLite o MySQL)
Creare una tabella denominata `tech_news` con la seguente struttura:

```sql
CREATE TABLE tech_news (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,           -- Titolo generato da Gemini
    summary TEXT,                  -- Riassunto SEO (150-200 char)
    content TEXT,                  -- Contenuto HTML (articoli di 300+ parole)
    image_url TEXT,                -- URL immagine (Unsplash o fonte originale)
    external_url TEXT,             -- Link alla fonte originale
    social_caption TEXT,           -- Testo ottimizzato per LinkedIn/FB
    is_published INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

## 2. Endpoint API di Importazione
Creare un file `api/news_import.php` che accetti richieste POST in formato JSON.

**Sicurezza**: L'endpoint deve verificare l'header `X-Api-Key`.
**Valore Key**: `df_marketing_2026_secure_key` (o una a tua scelta).

**Struttura JSON attesa**:
```json
{
  "news": [
    {
      "title": "...",
      "summary": "...",
      "content": "...",
      "image_url": "...",
      "external_url": "...",
      "social_caption": "..."
    }
  ]
}
```

## 3. Frontend (Visualizzazione)
Implementare una pagina `news.php` (o una sezione nella home) che mostri gli articoli ordinati per data decrescente.
- **Layout consigliato**: Grid di Card con Immagine, Titolo e Riassunto.
- **Dettaglio**: Una pagina dedicata o un modal per leggere l'intero `content`.

## 4. Requisiti di Sistema
- Ambiente PHP su Aruba.
- Supporto per PDO SQLite (o MySQL).

Una volta pronti questi componenti, fornitemi l'URL finale dell'endpoint (es. `https://www.davidefiore.com/api/news_import.php`) e io provvederò a collegare il "Cervello Gemini" per l'invio quotidiano dei contenuti.
