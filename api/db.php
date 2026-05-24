<?php
// Previeni l'accesso diretto
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

/**
 * Ritorna un'istanza di connessione PDO a SQLite ed inizializza la tabella se non esiste.
 */
function get_db_connection() {
    $db_path = __DIR__ . '/../data/news.db';
    
    try {
        // Connessione a SQLite. Se il file non esiste, PDO lo creerà automaticamente
        $pdo = new PDO("sqlite:" . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Inizializzazione della tabella tech_news se non esiste
        $pdo->exec("CREATE TABLE IF NOT EXISTS tech_news (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            summary TEXT,
            content TEXT,
            image_url TEXT,
            external_url TEXT,
            social_caption TEXT,
            is_published INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        return $pdo;
    } catch (PDOException $e) {
        // Registra l'errore
        error_log("Database Connection Error: " . $e->getMessage());
        throw new Exception("Errore durante l'inizializzazione del database.");
    }
}
