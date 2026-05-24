<?php
/**
 * API di Importazione News Generata da Gemini per davidefiore.com
 */

// Imposta header JSON
header('Content-Type: application/json; charset=utf-8');

// Includi file di configurazione e database
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Permetti solo richieste POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Metodo non consentito. Utilizzare POST.'
    ]);
    exit;
}

// Recupera la chiave API dall'header X-Api-Key (gestendo varie configurazioni del server Aruba)
$api_key = null;
if (isset($_SERVER['HTTP_X_API_KEY'])) {
    $api_key = $_SERVER['HTTP_X_API_KEY'];
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (isset($headers['X-Api-Key'])) {
        $api_key = $headers['X-Api-Key'];
    } elseif (isset($headers['x-api-key'])) {
        $api_key = $headers['x-api-key'];
    }
}

// Verifica la validità della chiave API
if (empty($api_key) || $api_key !== NEWS_API_KEY) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Chiave API non valida o mancante (X-Api-Key).'
    ]);
    exit;
}

// Leggi e decodifica il payload JSON
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['news']) || !is_array($data['news'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Payload JSON non valido. Struttura attesa: {"news": [...]}'
    ]);
    exit;
}

try {
    // Ottieni la connessione al database
    $db = get_db_connection();
    
    // Avvia la transazione per massimizzare le performance e l'atomicità
    $db->beginTransaction();
    
    $stmt = $db->prepare("
        INSERT INTO tech_news (
            title, 
            summary, 
            content, 
            image_url, 
            external_url, 
            social_caption,
            is_published
        ) VALUES (
            :title, 
            :summary, 
            :content, 
            :image_url, 
            :external_url, 
            :social_caption,
            1
        )
    ");
    
    $imported_count = 0;
    
    foreach ($data['news'] as $news_item) {
        $title = isset($news_item['title']) ? trim($news_item['title']) : '';
        
        // Il titolo è l'unico campo obbligatorio per pubblicare una news
        if (empty($title)) {
            continue;
        }
        
        $summary        = isset($news_item['summary']) ? trim($news_item['summary']) : null;
        $content        = isset($news_item['content']) ? trim($news_item['content']) : null;
        $image_url      = isset($news_item['image_url']) ? trim($news_item['image_url']) : null;
        $external_url   = isset($news_item['external_url']) ? trim($news_item['external_url']) : null;
        $social_caption = isset($news_item['social_caption']) ? trim($news_item['social_caption']) : null;
        
        $stmt->execute([
            ':title'          => $title,
            ':summary'        => $summary,
            ':content'        => $content,
            ':image_url'      => $image_url,
            ':external_url'   => $external_url,
            ':social_caption' => $social_caption
        ]);
        
        $imported_count++;
    }
    
    // Conferma l'inserimento di tutte le news
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Importazione completata con successo.',
        'imported_count' => $imported_count
    ]);
    
} catch (Exception $e) {
    // Se qualcosa va storto, annulla la transazione
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore del server durante l\'importazione: ' . $e->getMessage()
    ]);
}
