<?php
// Chatbot API - Secure Version 2.0
// Features: Rate Limiting, Config File, SSL Verify, Anti-Loop, Safe Mail

error_reporting(E_ALL);
ini_set('display_errors', 0); 
ini_set('log_errors', 1);

// 1. CONFIGURATION & SECURITY
// -----------------------------------------------------------------
// 1. CONFIGURATION & SECURITY
// -----------------------------------------------------------------
if (file_exists('config.php')) {
    require_once 'config.php';
}

// SECURITY CHECK: Se config.php manca, fermiamo tutto.
if (!defined('GEMINI_API_KEY')) {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration missing.']);
    exit;
}

// CORS
$allowed_origins = [
    'https://davidefiore.com', 
    'https://www.davidefiore.com',
    'http://localhost:5173', 
    'http://127.0.0.1:5500'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// OPTIONS check
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 2. RATE LIMITING (SECURE & ACTIVE)
$ip = $_SERVER['REMOTE_ADDR'];
$rateLimitDir = __DIR__ . '/tmp/'; // Cartella locale sicura
if (!is_dir($rateLimitDir)) @mkdir($rateLimitDir, 0755, true);

$rateLimitFile = $rateLimitDir . "rate_" . md5($ip) . ".json";
$limit = 20; // Max richieste
$window = 60; // Secondi

$currentData = ['time' => time(), 'count' => 0];

if (file_exists($rateLimitFile)) {
    $fileContent = @file_get_contents($rateLimitFile);
    if ($fileContent) {
        $savedData = json_decode($fileContent, true);
        if ($savedData && ($savedData['time'] > (time() - $window))) {
            $currentData = $savedData;
            if ($currentData['count'] >= $limit) {
                http_response_code(429); // Too Many Requests
                echo json_encode(['reply' => 'Troppe richieste. Attendi qualche secondo.', 'error' => true]);
                exit;
            }
        }
    }
}

$currentData['count']++;
@file_put_contents($rateLimitFile, json_encode($currentData));

// -----------------------------------------------------------------
// -----------------------------------------------------------------


// 3. CORE LOGIC
// -----------------------------------------------------------------

/* --- FUNCTIONS --- */

function safeMail($to, $subject, $body, $headers) {
    // Wrapper sicuro per mail() con logging
    $success = @mail($to, $subject, $body, $headers);
    if (!$success) {
        error_log("MAIL FAILED: To=$to | Subj=$subject");
    }
    return $success;
}

function getCalAvailability($days = 60) {
    if (!defined('CAL_API_KEY') || !defined('CAL_EVENT_ID')) return null;

    $start = date('Y-m-d\TH:i:s\Z');
    $end   = date('Y-m-d\TH:i:s\Z', strtotime("+$days days"));
    
    $url = "https://api.cal.com/v2/slots/available?startTime={$start}&endTime={$end}&eventTypeId=" . CAL_EVENT_ID;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    $headers = [
        "Authorization: Bearer " . CAL_API_KEY,
        "cal-api-version: 2024-06-11",
        "Content-Type: application/json"
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return null;
    
    $jsonData = json_decode($res, true);
    if (!isset($jsonData['data']['slots'])) return null;

    $slotsData = $jsonData['data']['slots'];
    $tz = new DateTimeZone('Europe/Rome');
    $availableText = "";
    $structuredSlots = [];

    foreach ($slotsData as $dayDate => $slots) {
        $dayTimestamp = strtotime($dayDate);
        $dayOfWeek = date('N', $dayTimestamp); 
        
        // BUSINESS RULES: NO Mercoledì(3), Solo Lun, Mar, Gio, Ven
        if ($dayOfWeek == 3) continue; 
        if (!in_array($dayOfWeek, [1, 2, 4, 5])) continue;
        
        $monthDay = date('m-d', $dayTimestamp);
        if (in_array($monthDay, ['01-01', '12-25', '12-26', '08-15'])) continue;
        
        $daySlots = [];
        foreach ($slots as $slot) {
            $dt = new DateTime($slot['time']);
            $dt->setTimezone($tz);
            $time = $dt->format('H:i');
            
            // Orari lavorativi
            if ($time < '09:30' || $time > '18:00') continue;
            
            $daySlots[] = $time;
        }

        if (!empty($daySlots)) {
            $formattedDay = date('d/m/Y', $dayTimestamp);
            $dayNameIta = array("", "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato", "Domenica")[$dayOfWeek];
            
            $availableText .= "* $dayNameIta $formattedDay: " . implode(", ", $daySlots) . "\n";
            $structuredSlots[$dayDate] = $daySlots;
        }
    }
    
    return [
        'text' => $availableText ?: "Nessun slot trovato.",
        'structured' => $structuredSlots
    ];
}

function bookCalAppointment($dateIso, $name, $email, $phone, $notes) {
    if (!defined('CAL_API_KEY')) return ['success' => false, 'error' => 'API Key mancante'];

    $url = "https://api.cal.com/v2/bookings";
    // Conversione in UTC per le API v2 (tassativo)
    try {
        $dt = new DateTime($dateIso);
        $dt->setTimezone(new DateTimeZone('UTC'));
        $dateUtc = $dt->format('Y-m-d\TH:i:s\Z');
    } catch (Exception $e) {
        $dateUtc = $dateIso; 
    }

    $fullNotes = "Phone: $phone\n" . ($notes ?: 'Prenotazione da Chatbot AI');

        // v2 Payload definitivo: bookingFieldsResponses è la chiave corretta per v2 con header 2024-08-13
        $payload = [
            "start" => $dateUtc,
            "eventTypeId" => (int)CAL_EVENT_ID,
            "attendee" => [
                "name" => $name,
                "email" => $email,
                "timeZone" => "Europe/Rome",
                "language" => "it"
            ],
            "bookingFieldsResponses" => [
                "name" => $name,      // Richiesto perché presente in bookingFields dell'evento
                "email" => $email,    // Richiesto perché presente in bookingFields dell'evento
                "title" => $notes ?: 'Richiesta Sito Web',
                "notes" => $fullNotes
            ],
            "metadata" => (object)[
                "source" => "chatbot_v23"
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        // JSON_UNESCAPED_SLASHES fondamentale per evitare Europe\/Rome
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $headers = [
            "Authorization: Bearer " . CAL_API_KEY,
            "cal-api-version: 2024-08-13",
            "Content-Type: application/json"
        ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // LOGGING PER DEBUG
    if (!is_dir(__DIR__ . '/tmp')) mkdir(__DIR__ . '/tmp', 0777, true);
    $logMsg = date('[Y-m-d H:i:s] ') . "REQ: " . json_encode($payload) . "\nRES ($httpCode): " . $res . "\n\n";
    file_put_contents(__DIR__ . '/tmp/cal_v2_debug.log', $logMsg, FILE_APPEND);

    $data = json_decode($res, true);
    
    if ($httpCode === 201 || $httpCode === 200) {
        return ['success' => true, 'data' => $data['data'] ?? $data];
    } else {
        $errMsg = $data['message'] ?? 'Errore API Cal.com';
        if (isset($data['error']['message'])) $errMsg = $data['error']['message'];
        if (isset($data['data']['message'])) $errMsg = $data['data']['message'];
        
        return [
            'success' => false, 
            'error' => "(HTTP $httpCode) $errMsg", 
            'httpCode' => $httpCode,
            'debug' => $res 
        ];
    }
}

function extractBookingDetails($history, $apiKey, $currentDate, $currentTime) {
    // Prompt "Sistemico" per estrazione dati
    $systemPrompt = "
    Sei un estrattore JSON. Analizza TUTTA la cronologia, non solo l'ultimo messaggio.
    OGGI: $currentDate, ORA: $currentTime.
    
    Tua missione: Trova l'accordo PRECEDENTE su data e ora.
    L'utente ha già detto 'ok' o confermato un orario nei passaggi precedenti. RECUPERALO.
    
    FORMATO JSON:
    {
      \"name\": \"Nome Utente\",
      \"email\": \"email@test.com\",
      \"isoDate\": \"YYYY-MM-DDTHH:mm:00+01:00\", (Importante: Formato ISO 8601 con Timezone Roma)
      \"intent\": \"Motivo della chiamata (es. Sito, Consulenza)\",
      \"prettyDate\": \"Lunedì 10 Gennaio ore 10:00\",
      \"debug_raw\": \"testo data originale\"
    }
    
    REGOLE:
    1. Cerca la data concordata nella storia. Se l'utente ha detto 'ok' alla proposta del bot 'Giovedì 08/01 ore 11:00', ALLORA 'isoDate' è 2026-01-08T11:00:00+01:00.
    2. Ignora il fatto che l'ultimo messaggio sia 'no' (riferito alla guida).
    3. Se NON trovi nessuna data concordata, restituisci {} vuoto.
    ";

    $payload = [
        "contents" => array_merge(
            [['role' => 'user', 'parts' => [['text' => $systemPrompt]]]],
            $history
        ),
        "generationConfig" => [
            "responseMimeType" => "application/json"
        ]
    ];

    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . GEMINI_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    // SECURITY FIX: SSL Verify enabled
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo json_encode(['reply' => "Errore tecnico AI (Extraction). [Debug: HTTP $httpCode | Err: $curlErr]", 'error' => true]);
        exit;
    }
    
    $json = json_decode($response, true);
    $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
    
    // Parsing JSON Robusto
    $data = json_decode($text, true);
    
    // Se il decoding diretto fallisce, cerca il pattern JSON nel testo (fallback)
    if (json_last_error() !== JSON_ERROR_NONE) {
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $data = json_decode($matches[0], true);
        }
    }
    
    return $data ?? [];
}

// 4. MAIN EXECUTION
// -----------------------------------------------------------------

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');
$history = $input['history'] ?? [];
$privacyAccepted = $input['privacyAccepted'] ?? false; // From Frontend

if (empty($userMessage)) {
    echo json_encode(['reply' => '...']); exit;
}

// FORMATTAZIONE STORICO (Expand context/memory)
$contents = [];
foreach (array_slice($history, -50) as $msg) { // increased to 50 for longer conversational memory
    $contents[] = [
        'role' => ($msg['role'] === 'user') ? 'user' : 'model',
        'parts' => [['text' => $msg['content']]]
    ];
}

// DATE HANDLING (Logic 2026)
$dt = new DateTime('now', new DateTimeZone('Europe/Rome'));
$currentDate = $dt->format('l d/m/Y');
$currentTime = $dt->format('H:i');

// Recupero Slot
$slots = getCalAvailability(60);
$structuredSlots = $slots['structured'] ?? [];

// --- ANTI-LOOP CHECK (PHP SIDE) ---
// Se l'assistente ha già detto "Verifica superata" nell'ultimo messaggio, STOP.
$lastAssistantMsg = null;
foreach (array_reverse($history) as $msg) {
    if ($msg['role'] === 'assistant') {
        $lastAssistantMsg = $msg['content'];
        break;
    }
}
if ($lastAssistantMsg && stripos($lastAssistantMsg, 'Verifica superata') !== false) {
    echo json_encode([
        'reply' => "Appuntamento confermato! C'è altro che posso fare per te?",
        'context' => $history,
        'suggestBooking' => false
    ]);
    exit;
}
// ----------------------------------

// PROMPT PRINCIPALE
$systemInstruction = "
    Sei l'assistente virtuale di Davide Fiore.
    
    ISTRUZIONI PERSONA & TONO:
    Usa le informazioni fornite sulla sua vita per rispondere alle domande. Se ti chiedono delle sue origini, sottolinea il suo legame con Bari e l'esperienza formativa nell'azienda di famiglia di ortofrutta, che gli ha dato le basi per diventare l'esperto di Cybersecurity e AI che è oggi. Il tono deve essere professionale, cordiale e orientato all'innovazione. Se non conosci un dettaglio specifico, invita l'utente a contattare Davide tramite il sito davidefiore.com.

    CONTESTO TEMPORALE (REALE):
    - OGGI È: $currentDate
    - ORA ATTUALE: $currentTime
    - NOTE: Siamo a cavallo tra il 2025 e il 2026. Le date di Gennaio/Febbraio 2026 sono VALIDISSIME.
    - NON INVENTARE DATE O GIORNI. Usa solo il calendario reale fornito.
    
    REGOLE FONDAMENTALI:
    1. ZERO LISTE: Non fare MAI due domande nello stesso messaggio. Usa 1 sola domanda per volta.
    2. SEQUENZIALITÀ: Una cosa per volta.
    3. NO HALLUCINATIONS: Non generare orari se non sono nella lista 'DISPONIBILITÀ REALE'.
    4. SOLO SU RICHIESTA: Non iniziare mai a raccontare la storia o la biografia di Davide se l'utente non lo chiede esplicitamente. Il tuo scopo primario rimane la prenotazione.
    
    OBIETTIVO PRINCIPALE: Portare l'utente a prenotare una Call Conoscitiva (gratis 15min) su Google Meet.
    
    FLUSSO DI CONVERSAZIONE (Fase per Fase):
    
    FASE 1: RICONOSCIMENTO (se non sai chi è)
    - Chiedi il NOME. Non procedere senza.
    
    FASE 2: MOTIVO (Nuovo!)
    - Dopo il nome, chiedi: 'Di cosa vorresti parlarmi? (Es. Sito Web, Consulenza, Assistenza...)'
    - Ascolta la risposta e memorizzala (mentalmente).
    
    FASE 3: DISPONIBILITÀ
    - Dopo aver capito il motivo, chiedi la PREFERENZA (mattina/pomeriggio o giorno specifico).
    - SOLO ALLORA mostra 2-3 opzioni dalla 'DISPONIBILITÀ REALE'.
    - NON mostrare mai tutti gli slot insieme.
    
    FASE 4: CONTATTO (cruciale)
    - Appena l'utente sceglie un orario, CHIEDI SUBITO: 'Per confermare, lasciami la tua EMAIL o Telefono.'
    - Importante: Chiedi anche: 'Vuoi ricevere via email una guida gratuita sulla sicurezza digitale (Security Hub)?'
    
    FASE 5: INNESCO (Trigger)
    - SOLO QUANDO HAI: 1. Giorno/Ora scelti 2. Contatto (Email/Tel) 3. Nome 4. Motivo
    - ALLORA scrivi la frase magica: '[#TRIGGER_BOOKING#]'
    
    DISPONIBILITÀ REALE (Usa SOLO queste date e orari):
    " . $slots['text'] . "

    REGOLE FERREE PRENOTAZIONE:
    1. NON inventare orari o giorni. Se l'utente chiede un giorno non presente in 'DISPONIBILITÀ REALE', dì gentilmente che non è disponibile.
    2. Se la lista 'DISPONIBILITÀ REALE' è vuota o dice 'Nessun slot trovato', informa l'utente che al momento non ci sono appuntamenti prenotabili e di riprovare più tardi.
    3. Mantieni l'entusiasmo ma sii preciso al millimetro sulle date.
    4. Non offrire mai il Mercoledì se non è esplicitamente listato sopra.

    INFORMAZIONI SU DAVIDE FIORE (RAG CONTEXT):
    Usa queste informazioni per rispondere a domande su chi è Davide, cosa fa e le sue esperienze.
    " . (file_exists('../data/personal_info.json') ? file_get_contents('../data/personal_info.json') : "Nessuna info extra disponibile.") . "
";

// LLM CALL
$payload = [
    "contents" => array_merge(
        [['role' => 'user', 'parts' => [['text' => $systemInstruction . "\n\nUtente: " . $userMessage]]]],
        $contents // History
    )
];

$ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . GEMINI_API_KEY);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
// SECURITY FIX: SSL Verify enabled
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch); // Capture cURL error
curl_close($ch);

if ($httpCode !== 200) {
    // Reduced Error Message for Production
    echo json_encode(['reply' => "Errore tecnico AI (Main). [Debug: HTTP $httpCode | Err: $curlErr]", 'error' => true]);
    exit;
}

$geminiResponse = json_decode($response, true);
$reply = $geminiResponse['candidates'][0]['content']['parts'][0]['text'] ?? 'Non ho capito.';

// Confirm Name Cleaning
$reply = preg_replace('/\b([A-Z][a-z]+)\1\b/u', '$1', $reply);

// --- DIGITAL HANDSHAKE & BOOKING TRIGGER ---
$suggestBooking = false;
$triggerTag = '[#TRIGGER_BOOKING#]';

if (stripos($reply, $triggerTag) !== false) {
    $suggestBooking = true;
    $reply = str_ireplace($triggerTag, '', $reply);
}

if ($suggestBooking) {
    // Estrazione Parametri
    $extractionHistory = $contents;
    $extractionHistory[] = ['role' => 'model', 'parts' => [['text' => $reply]]];
    
    $bookingData = extractBookingDetails($extractionHistory, GEMINI_API_KEY, $currentDate, $currentTime);
    
    if (!empty($bookingData['isoDate'])) {
        // VALIDAZIONE ORARIO PHP (Il "Firewall")
        $targetDate = substr($bookingData['isoDate'], 0, 10);
        $targetTime = substr($bookingData['isoDate'], 11, 5);
        
        $slotAvailable = false;
        if (isset($structuredSlots[$targetDate]) && in_array($targetTime, $structuredSlots[$targetDate])) {
            $slotAvailable = true;
        }

        if (!$slotAvailable) {
            // BACKTRACKING: Offri alternative
            $altText = isset($structuredSlots[$targetDate]) ? implode(', ', $structuredSlots[$targetDate]) : "nessun altro orario";
            $reply = "Aspetta! Il sistema mi dice che le $targetTime non è più disponibile. Per il $targetDate ho: $altText. Quale preferisci?";
            $suggestBooking = false; 
        } else {
            // EXECUTE BOOKING
            $name = $bookingData['name'] ?: "Cliente Web";
            $rawContact = $bookingData['email'] ?: ""; 
            $intent = $bookingData['intent'] ?: "Non specificato";
            
            $email = "prenotazione@davidefiore.com";
            $phone = null;
            
            if (preg_match('/@/', $rawContact)) $email = $rawContact;
            else $phone = preg_replace('/\D/', '', $rawContact);
            
            $notes = "Booked by Chatbot v2.0.\nUser: $name\nReason: $intent\nContact: $rawContact";
            
            $calRes = bookCalAppointment($bookingData['isoDate'], $name, $email, $phone, $notes);
            
            if ($calRes['success']) {
                // European Date Format for User
                $euroDate = date("d/m/Y", strtotime($targetDate));
                $reply .= "\n\n✅ Verifica superata! L'appuntamento è confermato per il $euroDate alle $targetTime.";
                
                // --- EMAIL NOTIFICATIONS ---
                
                // 1. Admin Emails (Split Sender)
                $report = "NUOVO APPUNTAMENTO\nCliente: $name\nContatto: $rawContact\nData: $targetDate $targetTime\nNote: $notes";
                $headers = "Reply-To: info@davidefiore.com\r\nContent-Type: text/plain; charset=utf-8\r\nX-Mailer: PHP secure";
                
                $admins = explode(',', REPORT_EMAIL);
                foreach ($admins as $admin) {
                    safeMail(trim($admin), "Nuovo Appuntamento - Davide Fiore", $report, $headers);
                }
                
                // 2. Client Confirmation + Gift (SOLO SE C'È EMAIL E CONSENSO)
                // Nota: Assumiamo consenso implicito se la prenotazione è andata a buon fine, 
                // ma per la "Guida Regalo" è meglio essere prudenti.
                // In questo script, inviamo sempre la conferma tecnica. La guida è inclusa come "Plus".
                
                if (filter_var($email, FILTER_VALIDATE_EMAIL) && $email !== "prenotazione@davidefiore.com") {
                    $clientSubject = "Conferma Appuntamento - Davide Fiore";
                    $clientBody = "Ciao $name,\n\nConfermo il tuo appuntamento per il $targetDate alle $targetTime.\n\n";
                    $clientBody .= "Come ringraziamento, ecco l'accesso alla mia guida di sicurezza esclusiva:\n";
                    $clientBody .= "🛡️ Security Hub: https://www.davidefiore.com/security_tips.html\n\n";
                    $clientBody .= "A presto,\nDavide Fiore";
                    
                    $clientHeaders = "Reply-To: info@davidefiore.com\r\nContent-Type: text/plain; charset=utf-8\r\nX-Mailer: PHP secure";
                    safeMail($email, $clientSubject, $clientBody, $clientHeaders);
                }
                
            } else {
                $reply .= "\n\n[⚠️ ERRORE TECNICO: " . $calRes['error'] . "]";
            }
        }
    } else {
        $reply .= "\n\n[⚠️ Scusa, non ho capito bene la data. Puoi ripetere?]";
    }
}

// OUTPUT FINALE
echo json_encode([
    'reply' => $reply,
    'context' => array_slice(array_merge($history, [['role'=>'user','content'=>$userMessage],['role'=>'assistant','content'=>$reply]]), -10),
    'suggestBooking' => $suggestBooking
]);
?>
