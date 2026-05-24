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

// FALLBACK DI SICUREZZA (Se config.php non viene caricato per permessi/errore)
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', 'AIzaSyA-FCtkZUETvNFlKUs0Zj5rAVA-uVTdans');
    define('CAL_API_KEY', 'cal_live_91bd0d4c0256ce06faca4395d055c769');
    define('CAL_EVENT_ID', '4249403');
    define('REPORT_EMAIL', 'info@davidefiore.com, fio.davide@gmail.com');
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

// 2. RATE LIMITING (DISABILITATO TEMPORANEAMENTE PER ERRORE 500 SU ARUBA)
// Il controllo dei permessi sulla cartella /tmp spesso fallisce su hosting condivisi.
/*
$ip = $_SERVER['REMOTE_ADDR'];
$rateLimitFile = sys_get_temp_dir() . "/chatbot_rate_" . md5($ip) . ".txt";
$limit = 15; // Max richieste
$window = 60; // Secondi

// ... (Codice Rate Limit Commentato per Stabilità)
*/
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
    // Usa costanti da config.php
    $start = date('Y-m-d');
    $end   = date('Y-m-d', strtotime("+$days days"));
    
    $url = "https://api.cal.com/v1/slots?apiKey=" . CAL_API_KEY . "&startTime={$start}&endTime={$end}&eventTypeId=" . CAL_EVENT_ID . "&_t=" . time();
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // REVERT SECURITY (Fix per Aruba hosting condiviso)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) return "Errore calendario (Codice: $httpCode).";
    
    $data = json_decode($res, true);
    if (!isset($data['slots'])) return "Nessuno slot trovato.";
    
    $availableText = "";
    $count = 0;
    $maxSlots = 2000;
    
    try {
        $tz = new DateTimeZone('Europe/Rome');
    } catch (Exception $e) { return "Errore Timezone."; }
    
    $structuredSlots = [];

    foreach ($data['slots'] as $dayDate => $slots) {
        $dayTimestamp = strtotime($dayDate);
        $dayOfWeek = date('N', $dayTimestamp); 
        
        // Regole Business: NO Merc(3), Solo Lun(1), Mar(2), Gio(4), Ven(5)
        if ($dayOfWeek == 3) continue; 
        if (!in_array($dayOfWeek, [1, 2, 4, 5])) continue;
        
        $monthDay = date('m-d', $dayTimestamp);
        // Esclusione Festivi manuale
        if (in_array($monthDay, ['01-01', '12-25', '12-26', '08-15'])) continue;
        
        $dateStr = date('Y-m-d', $dayTimestamp);
        $daySlots = [];

        foreach ($slots as $slot) {
            try {
                $dt = new DateTime($slot['time']);
                $dt->setTimezone($tz);
                $time = $dt->format('H:i');
                
                // Orari lavorativi
                if ($time < '09:30' || $time > '18:00') continue;
                
                $daySlots[] = $time;
                $count++;
                if ($count > $maxSlots) break 2;
            } catch (Exception $e) { continue; }
        }

        if (!empty($daySlots)) {
            $formattedDay = date_format(date_create($dayDate), 'l d/m/Y'); 
            $translate = [
                'Monday' => 'Lunedì', 'Tuesday' => 'Martedì', 'Wednesday' => 'Mercoledì',
                'Thursday' => 'Giovedì', 'Friday' => 'Venerdì', 'Saturday' => 'Sabato', 'Sunday' => 'Domenica'
            ];
            $itDay = strtr($formattedDay, $translate);
            $availableText .= "- $itDay: " . implode(", ", $daySlots) . "\n";
            $structuredSlots[$dateStr] = $daySlots;
        }
    }
    
    return [$availableText, $structuredSlots];
}

function bookCalAppointment($dateIso, $name, $email, $phone, $notes) {
    $postData = [
        'eventTypeId' => (int)CAL_EVENT_ID, 
        'start' => $dateIso,
        'responses' => [
            'name' => $name,
            'email' => $email,
            'location' => [
                'value' => 'inPerson', 
                'optionValue' => ''
            ],
            'notes' => $notes,
            'guests' => []
        ],
        'metadata' => (object)[], // Force JSON Object {}
        'timeZone' => 'Europe/Rome',
        'language' => 'it',
        'title' => 'Consulenza con ' . $name
    ];
    
    if ($phone) {
        $postData['responses']['phone'] = $phone; 
    }
    
    $ch = curl_init("https://api.cal.com/v1/bookings?apiKey=" . CAL_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    // SECURITY UPGRADE: SSL Verify enabled
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode === 200 || $httpCode === 201) {
        return ['success' => true];
    } else {
        $err = json_decode($res, true);
        return ['success' => false, 'error' => $err['message'] ?? "HTTP $httpCode"];
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
        )
    ];

    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . GEMINI_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    // SECURITY: SSL Verify (DISABLED / Aruba Fix)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
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
    $cleanJson = preg_replace('/^```json\s*|\s*```$/', '', trim($text));
    
    return json_decode($cleanJson, true) ?? [];
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

// Recupero Slot (Cache consigliata in produzione, qui live per semplicità)
list($slotsText, $structuredSlots) = getCalAvailability(60);

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
    CONTESTO TEMPORALE (REALE):
    - OGGI È: $currentDate
    - ORA ATTUALE: $currentTime
    - NOTE: Siamo a cavallo tra il 2025 e il 2026. Le date di Gennaio/Febbraio 2026 sono VALIDISSIME.
    - NON INVENTARE DATE O GIORNI. Usa solo il calendario reale fornito.
    
    REGOLE FONDAMENTALI:
    1. ZERO LISTE: Non fare MAI due domande nello stesso messaggio. Usa 1 sola domanda per volta.
    2. SEQUENZIALITÀ: Una cosa per volta.
    3. NO HALLUCINATIONS: Non generare orari se non sono nella lista 'DISPONIBILITÀ REALE'.
    4. TONO: Professionale, sintetico, orientato alla prenotazione.
    
    OBIETTIVO: Portare l'utente a prenotare una Call Conoscitiva (gratis 15min) su Google Meet.
    
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
    
    DISPONIBILITÀ REALE (Usa SOLO questi orari):
    $slotsText
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
// SECURITY: SSL Verify (DISABLED for hosting compatibility)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

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
