<?php
// Chatbot API - Clean Rewritten Version 1.0 (Digital Handshake)

// Suppress errors in output, log them instead
error_reporting(E_ALL);
ini_set('display_errors', 0); 
ini_set('log_errors', 1);

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
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Config
$GEMINI_API_KEY = 'AIzaSyA-FCtkZUETvNFlKUs0Zj5rAVA-uVTdans';
$CAL_API_KEY   = 'cal_live_91bd0d4c0256ce06faca4395d055c769';
$CAL_EVENT_ID  = '4249403'; 
$REPORT_EMAIL = 'info@davidefiore.com, fio.davide@gmail.com';

// --- FUNCTIONS ---

function getCalAvailability($days = 60) {
    global $CAL_API_KEY, $CAL_EVENT_ID;
    
    $start = date('Y-m-d');
    $end   = date('Y-m-d', strtotime("+$days days"));
    
    $url = "https://api.cal.com/v1/slots?apiKey={$CAL_API_KEY}&startTime={$start}&endTime={$end}&eventTypeId={$CAL_EVENT_ID}&_t=" . time();
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) return "Errore calendario.";
    
    $data = json_decode($res, true);
    if (!isset($data['slots'])) return "Nessuno slot trovato.";
    
    $availableText = "";
    $count = 0;
    $maxSlots = 2000;
    
    try {
        $tz = new DateTimeZone('Europe/Rome');
    } catch (Exception $e) {
        return "Errore Timezone.";
    }
    
    foreach ($data['slots'] as $dayDate => $slots) {
        $dayTimestamp = strtotime($dayDate);
        $dayOfWeek = date('N', $dayTimestamp); 
        
        if ($dayOfWeek == 3) continue; // Wed
        if (!in_array($dayOfWeek, [1, 2, 4, 5])) continue;
        
        $monthDay = date('m-d', $dayTimestamp);
        // Exclude Holidays
        if (in_array($monthDay, ['01-01', '12-25', '12-26', '08-15'])) continue;
        
        $daySlots = [];
        foreach ($slots as $slot) {
            try {
                $dt = new DateTime($slot['time']);
                $dt->setTimezone($tz);
                $time = $dt->format('H:i');
                
                if ($time < '09:30' || $time > '18:00') continue;
                
                $daySlots[] = $time;
                $count++;
                if ($count > $maxSlots) break 2;
            } catch (Exception $e) { continue; }
        }
        
        if (!empty($daySlots)) {
            $dateObj = new DateTime($dayDate);
            $daysMap = ['Monday'=>'Lunedì','Tuesday'=>'Martedì','Wednesday'=>'Mercoledì','Thursday'=>'Giovedì','Friday'=>'Venerdì','Saturday'=>'Sabato','Sunday'=>'Domenica'];
            $dayNameIt = $daysMap[$dateObj->format('l')] ?? $dateObj->format('l');
            $dateFormatted = "$dayNameIt " . $dateObj->format('d/m/Y');
            $availableText .= "$dateFormatted: " . implode(', ', $daySlots) . "\n";
            
            // Structured Data
            $structuredData[$dateObj->format('Y-m-d')] = $daySlots;
        }
    }
    return ['text' => $availableText ?: "Nessuna disponibilità.", 'raw' => $structuredData ?? []];
}

function extractBookingDetails($formattedHistory, $apiKey, $date, $time) {
    // Reconstruct conversation
    $text = "";
    foreach ($formattedHistory as $msg) $text .= "{$msg['role']}: {$msg['parts'][0]['text']}\n";
    
    $nextYear = date('Y') + 1;
    
    $prompt = "
    SEI UN ESTRATTORE DI DATI PER CALENDARIO.
    DATA ODIERNA: $date $time (Anno corrente).
    ANNO PROSSIMO: $nextYear (Usalo se la data richiesta è passata o è Gennaio/Febbraio).
    
    OBIETTIVO: Estrai i dati dell'appuntamento CONFERMATO.JSON.
    
    REGOLE CRITICHE:
    1. ANNO NUOVO: Se l'utente dice '8 Gennaio', ed è Dicembre, DEVI usare $nextYear.
    2. FORMATO: ISO 8601 (YYYY-MM-DDTHH:mm:00).
    
    OUTPUT JSON: { \"isoDate\": \"YYYY-MM-DDTHH:mm:00\", \"prettyDate\": \"...\", \"name\": \"...\", \"email\": \"...\" }
    
    CONVERSAZIONE:
    $text
    ";
    
    $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['contents'=>[['parts'=>[['text'=>$prompt]]]], 'generationConfig'=>['response_mime_type'=>'application/json']]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $res = curl_exec($ch);
    curl_close($ch);
    
    $json = json_decode($res, true);
    $content = $json['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
    
    // SANITIZE MARKDOWN & ARRAY
    $content = preg_replace('/^```json\s*|\s*```$/', '', trim($content));
    $content = str_replace(['```', 'json'], '', $content); // extra safety
    
    $data = json_decode($content, true);
    
    // Handle Array Wrapper
    if (isset($data[0]) && is_array($data[0])) {
        $data = $data[0];
    }
    
    // Auto-Fix Name Doubling (in case regex missed it)
    if (!empty($data['name'])) {
        $n = $data['name'];
        if (strlen($n) > 3 && substr($n, 0, strlen($n)/2) == substr($n, strlen($n)/2)) {
            $data['name'] = substr($n, 0, strlen($n)/2);
        }
    }
    
    // Attach Raw Content for Debug if empty
    if (empty($data['isoDate'])) {
        $data['debug_raw'] = $content;
    }
    
    return $data;
}

function bookCalAppointment($startTime, $name, $email, $phone, $notes) {
    global $CAL_API_KEY, $CAL_EVENT_ID;
    
    $startTime = str_replace('Z', '', $startTime);
    try {
        $dtStart = new DateTime($startTime, new DateTimeZone('Europe/Rome'));
        $dtStart->setTimezone(new DateTimeZone('UTC'));
        $utcStart = $dtStart->format('Y-m-d\TH:i:s.000\Z');
        
        $dtEnd = clone $dtStart;
        $dtEnd->modify('+60 minutes');
        $utcEnd = $dtEnd->format('Y-m-d\TH:i:s.000\Z');
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Date Error'];
    }

    $payload = [
        'eventTypeId' => (int)$CAL_EVENT_ID,
        'start' => $utcStart,
        'end' => $utcEnd,
        'responses' => [
            'name' => $name,
            'email' => $email,
            'location' => [
                'value' => 'inPerson',
                'optionValue' => ''
            ],
            'notes' => "$notes\n\nCONTATTO TELEFONICO: $phone"
        ],
        'timeZone' => 'Europe/Rome',
        'language' => 'it',
        'metadata' => new stdClass()
    ];
    
    $ch = curl_init("https://api.cal.com/v1/bookings?apiKey=" . $CAL_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Debug: Return full response on error
    return ($code >= 200 && $code < 300) ? ['success' => true] : ['success' => false, 'error' => "HTTP $code: $res"];
}



// --- CONTEXT SETUP ---

$days = ['Sunday'=>'Domenica', 'Monday'=>'Lunedì', 'Tuesday'=>'Martedì', 'Wednesday'=>'Mercoledì', 'Thursday'=>'Giovedì', 'Friday'=>'Venerdì', 'Saturday'=>'Sabato'];
$months = ['January'=>'Gennaio', 'February'=>'Febbraio', 'March'=>'Marzo', 'April'=>'Aprile', 'May'=>'Maggio', 'June'=>'Giugno', 'July'=>'Luglio', 'August'=>'Agosto', 'September'=>'Settembre', 'October'=>'Ottobre', 'November'=>'Novembre', 'December'=>'Dicembre'];

$dayIt = $days[date('l')] ?? date('l');
$monthIt = $months[date('F')] ?? date('F');
$currentDate = "$dayIt " . date('d') . " $monthIt " . date('Y');
$currentTime = date('H:i');

// --- PROMPTS ---

$PROMPTS = [];

$PROMPTS['standard'] = "Sei l'assistente virtuale di Davide Fiore.

    CONTESTO TEMPORALE (REALE):
    - OGGI È: $currentDate
    - ORA ATTUALE: $currentTime
    - NOTE: Siamo a cavallo tra il 2025 e il 2026. Le date di Gennaio/Febbraio 2026 sono VALIDISSIME.
    - NON INVENTARE DATE O GIORNI. Usa solo il calendario reale fornito.
    
    REGOLE FONDAMENTALI:
    1. ZERO LISTE: Non fare MAI due domande nello stesso messaggio. Usa 1 sola domanda per volta.
    2. SEQUENZIALITÀ: Una cosa per volta.
    3. NO HALLUCINATIONS: Non generare mai messaggi che iniziano con [SYSTEM NOTE].
    4. GESTIONE NOME:
       - Usa il nome ESATTO fornito dall'utente.
       - SE L'UTENTE DICE \"Gigi\", TU DICI \"Gigi\".
       - VIETATO raddoppiare il nome (MAI dire 'GigiGigi' o 'BauBau'). Se succede, correggiti.
    5. CRITICO - VERIFICA SLOT:
       - NON FIDARTI DI QUELLO CHE DICE L'UTENTE.
       - Se l'utente chiede \"Lunedì 12 alle 17:00\", TU DEVI CERCARE NELLA LISTA \"DISPONIBILITÀ REALE\" qui sotto.
       - Se vedi \"Lunedì 12/01/2026: ... 17:00 ...\", ALLORA OK.
       - Se NON vedi 17:00 nella lista, È OCCUPATO. Rispondi: \"Mi dispiace, quell'orario non è più disponibile. Ecco le alternative: ...\"
       - È MEGLIO RIFIUTARE CHE CREARE UN DOPPIONE.
    6. FASE DISCOVERY (OBBLIGATORIA):
       - Se l'utente dice \"voglio una consulenza\", DEVI CHIEDERE: \"Di cosa ti occupi esattamente?\" PRIMA di chiedere il nome.
       - Obiettivo: Far sentire l'utente ascoltato sul suo progetto.
    7. PRIVACY SLOT: Non mostrare MAI la lista completa delle disponibilità a meno che l'utente non lo chieda esplicitamente.
    8. CONTATTI PUBBLICI: Se l'utente chiede un contatto diretto e non vuole prenotare, PUOI fornire l'email pubblica: info@davidefiore.com.
    9. FLUSSO NATURALE: DISCOVERY -> NOME -> CONTATTO -> PRENOTAZIONE.
    
    ORARI: Lun-Ven 09:30-19:00 (No Mercoledì).
    
    ALGORITMO FLUSSO:
    1. SALUTO & DISCOVERY (Progetto).
    2. NOME.
    3. CONTATTO.
    4. PRENOTAZIONE (Data/Ora). VERIFICA DISPONIBILITÀ.
       - Se occupato, proponi alternative.
    5. CHIUSURA.
    
    CHIUSURA (CHECKLIST OBBLIGATORIA):
    - PRIMA DI CONTROLLARE, assicurati di avere:
      [ ] Nome
      [ ] Contatto (Telefono O Email)
      [ ] GIORNO Specifico
      [ ] ORA Specifica
    - Se hai tutto, dì che stai VERIFICANDO la disponibilità e scrivi il codice segreto: [#TRIGGER_BOOKING#]
    - Esempio: \"Ricevuto. Verifico subito la disponibilità di questo orario... [#TRIGGER_BOOKING#]\"
    
    10. STOP AL LOOP (CRITICO):
    - Se nei messaggi precedenti vedi la frase '[✅ Prenotazione registrata su Calendario]', NON generare PIÙ il codice [#TRIGGER_BOOKING#].
    - L'appuntamento è già preso. Non provare a prenotarlo di nuovo.
    - Se l'utente dice 'ok' o 'confermo' dopo la spunta verde, rispondi solo 'Grazie, a presto!'.";

$PROMPTS['analyst'] = "Sei SYSTEM ANALYST V.1. OGGI È: $currentDate. DISPONIBILITÀ REALE: \n{{REAL_TIME_SLOTS}}";

// APPEND AVAILABILITY TO STANDARD PROMPT DYNAMICALLY
$PROMPTS['standard'] .= "\n\nDISPONIBILITÀ REALE (LISTA UFFICIALE - USA QUESTA PER VERIFICARE TASSATIVAMENTE GLI SLOT):\n{{REAL_TIME_SLOTS}}";

// --- MAIN EXECUTION ---

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['reply' => 'Errore di comunicazione.', 'error' => true]);
    exit;
}

$userMessage = $input['message'] ?? '';
$history = $input['history'] ?? [];
$mode = $input['mode'] ?? 'standard';

// Validation Phone
$validationNote = "";
$cleanNum = preg_replace('/\D/', '', $userMessage);
if (preg_match('/[\d\-\+\s]{6,}/', $userMessage) && strlen($cleanNum) >= 6) {
    $validationNote = "\n[SYSTEM NOTE: Numero ($cleanNum) valido. Procedi.]";
}

// Logic
$basePrompt = $PROMPTS[$mode] ?? $PROMPTS['standard'];
$availability = getCalAvailability(60); 
$realTimeSlots = $availability['text'];
$structuredSlots = $availability['raw'];
$finalSystemPrompt = str_replace('{{REAL_TIME_SLOTS}}', substr($realTimeSlots, 0, 50000), $basePrompt) . $validationNote;

// Build History
$contents = [];
foreach ($history as $msg) {
    $role = $msg['role'] === 'assistant' ? 'model' : 'user'; 
    $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content']]]];
}
$contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

// Gemini Text Gen
$ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $GEMINI_API_KEY);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'contents' => $contents,
    'systemInstruction' => ['parts' => [['text' => $finalSystemPrompt]]],
    'generationConfig' => ['maxOutputTokens' => 500, 'temperature' => 0.4]
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['reply' => 'Errore tecnico AI.', 'error' => true, 'debug' => "HTTP $httpCode"]);
    exit;
}

$geminiResponse = json_decode($response, true);
$reply = $geminiResponse['candidates'][0]['content']['parts'][0]['text'] ?? 'Non ho capito.';

    // Confirm name cleaner (FrancoFranco -> Franco)
    $reply = preg_replace('/\b([A-Z][a-z]+)\1\b/u', '$1', $reply);

// --- DIGITAL HANDSHAKE ---
$suggestBooking = false;
$triggerTag = '[#TRIGGER_BOOKING#]';

if (stripos($reply, $triggerTag) !== false) {
    $suggestBooking = true;
    // Remove tag from reply
    $reply = str_ireplace($triggerTag, '', $reply);
}

$hasContactInfo = (preg_match('/@/', $userMessage) || strlen($cleanNum) >= 6);

if ($suggestBooking) {
    // Retry Name Cleaning on the trigger message too
    $reply = preg_replace('/\b([A-Z][a-z]+)\1\b/u', '$1', $reply);

    $extractionHistory = $contents;
    // Using cleaned reply is fine, context implies confirmation from text
    $extractionHistory[] = ['role' => 'model', 'parts' => [['text' => $reply]]];
    
    $bookingData = extractBookingDetails($extractionHistory, $GEMINI_API_KEY, $currentDate, $currentTime);
    
    if (!empty($bookingData['isoDate'])) {
        // PHP SIDE VALIDATION (The "Firewall")
        $targetDate = substr($bookingData['isoDate'], 0, 10); // YYYY-MM-DD
        $targetTime = substr($bookingData['isoDate'], 11, 5); // HH:mm
        
        $slotAvailable = false;
        if (isset($structuredSlots[$targetDate]) && in_array($targetTime, $structuredSlots[$targetDate])) {
            $slotAvailable = true;
        }

        if (!$slotAvailable) {
            // SMART RECOVERY: Find alternatives for that day
            $alternatives = [];
            if (isset($structuredSlots[$targetDate])) {
                $alternatives = $structuredSlots[$targetDate];
            }
            
            $altText = !empty($alternatives) ? implode(', ', $alternatives) : "nessun altro orario per quel giorno";
            
            // Overwrite the "Confirmed" message with a helpful correction
            $reply = "Aspetta, ti chiedo scusa! 🛑\n\nMentre parlavamo, il sistema mi segnala che l'orario delle $targetTime non è più disponibile (qualcuno l'ha appena prenotato!).\n\nPerò per quel giorno ($targetDate) ho ancora liberi questi orari:\n$altText\n\nQuale preferisci prenotare?";
            
            // Reset booking flag so we don't try to book contextless next time
            $suggestBooking = false; 
        } else {
            // PROCEED
            $name = $bookingData['name'] ?: "Cliente Web";
            $rawContact = $bookingData['email'] ?: ""; 
            
            $email = "prenotazione@davidefiore.com";
            $phone = null;
            
            if (preg_match('/@/', $rawContact)) {
                $email = $rawContact;
            } else {
                $phone = preg_replace('/\D/', '', $rawContact);
                if (strlen($phone) < 5) $phone = null;
            }
            
            if (!$phone && $hasContactInfo && strlen($cleanNum) >= 6) $phone = $cleanNum;
            
            $notes = "Booked by Chatbot.\nUser: $name\nContact: $rawContact";
            
            $calRes = bookCalAppointment($bookingData['isoDate'], $name, $email, $phone, $notes);
            
            if ($calRes['success']) {
                $reply .= "\n\n✅ Verifica superata! L'appuntamento è confermato.";
            } else {
                $reply .= "\n\n[⚠️ ERRORE DETTAGLIATO: " . $calRes['error'] . "]";
            }
        }
    } else {
        $reply .= "\n\n[⚠️ Nota: Ricezione data incompleta. Debug: " . ($bookingData['debug_raw'] ?? 'N/A') . "]";
    }
    
    sendReportEmail($contents, $REPORT_EMAIL, $bookingData); // Pass booking data
}

function sendReportEmail($conversation, $toEmail, $bookingData = null) {
    $subject = "Nuova Chatbot - " . date('d/m H:i');
    $header = "";

    if ($bookingData && !empty($bookingData['name'])) {
        $name = strtoupper($bookingData['name']);
        $date = $bookingData['prettyDate'] ?? $bookingData['isoDate'];
        $email = $bookingData['email'] ?? "N/D";
        
        $subject = "NUOVO APPUNTAMENTO: $name ($date)";
        
        $header = "
========================================
🚀 NUOVO APPUNTAMENTO CONFERMATO
========================================

👤 CLIENTE:  $name
📞 CONTATTO: $email
📅 QUANDO:   $date

========================================
💬 TRASCRIZIONE CHAT
========================================
";
    }

    $report = $header;
    foreach ($conversation as $msg) {
        $role = strtoupper($msg['role']);
        $text = $msg['content'] ?? ($msg['parts'][0]['text'] ?? '');
        $report .= "[$role]: $text\n\n";
    }
    
    // $headers = "From: info@davidefiore.com\r\n"; // RIMOSSO: Aruba lo blocca se non autenticato
    $headers = "Reply-To: info@davidefiore.com\r\n";
    $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return @mail($toEmail, $subject, $report, $headers);
}

// Output
echo json_encode([
    'reply' => $reply,
    'context' => array_slice(array_merge($history, [['role'=>'user','content'=>$userMessage],['role'=>'assistant','content'=>$reply]]), -10),
    'suggestBooking' => $suggestBooking
]);
?>
