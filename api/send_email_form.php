<?php
// Gestione Form Contatti - Secure Version 2.0
// Features: Config File, Safe Mail, GDPR Check, Anti-Spam Headers

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

// 1. CONFIG
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// 1.1 RATE LIMITING
$ip = $_SERVER['REMOTE_ADDR'];
$rateLimitDir = __DIR__ . '/tmp/';
if (!is_dir($rateLimitDir)) @mkdir($rateLimitDir, 0755, true);

$rateFile = $rateLimitDir . "email_rate_" . md5($ip) . ".json";
$limit = 5; // Max 5 messaggi per finestra
$window = 3600; // 1 ora

$now = time();
$currentRate = ['time' => $now, 'count' => 0];

if (file_exists($rateFile)) {
    $currentRate = json_decode(file_get_contents($rateFile), true);
    if (($now - $currentRate['time']) > $window) {
        $currentRate = ['time' => $now, 'count' => 1];
    } else {
        $currentRate['count']++;
        if ($currentRate['count'] > $limit) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'Troppe richieste. Riprova più tardi.']);
            exit;
        }
    }
} else {
    $currentRate['count'] = 1;
}
file_put_contents($rateFile, json_encode($currentRate));

// 2. HELPER
function safeMail($to, $subject, $body, $headers) {
    if (strpos($to, ',') !== false) {
        $recipients = explode(',', $to);
        $allOk = true;
        foreach ($recipients as $r) {
            $ok = @mail(trim($r), $subject, $body, $headers);
            if (!$ok) { error_log("MAIL FAILED: $r"); $allOk = false; }
        }
        return $allOk;
    } else {
        $ok = @mail($to, $subject, $body, $headers);
        if (!$ok) error_log("MAIL FAILED: $to");
        return $ok;
    }
}

function verifyRecaptcha($token) {
    if (empty($token)) return false;
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token
    ];
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) return true; // Fail safe (se Google è giù, lasciamo passare)
    $res = json_decode($response, true);
    return ($res['success'] && $res['score'] >= 0.5);
}

// 3. GET DATA
$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$message = trim($data['message'] ?? '');
$privacy = $data['privacy'] ?? false; // GDPR CHECK
$honeypot = trim($data['b_website'] ?? ''); // Honeypot field
$recaptchaToken = $data['recaptcha_token'] ?? '';

// 4. VALIDATION
if (!empty($honeypot)) {
    // BOT DETECTED
    error_log("BOT DETECTED from IP: $ip (Honeypot filled)");
    echo json_encode(['success' => true]); // Soft fail
    exit;
}

// 4.1 RECAPTCHA VERIFICATION
if (defined('RECAPTCHA_SECRET_KEY') && RECAPTCHA_SECRET_KEY !== 'YOUR_SECRET_KEY_HERE') {
    if (!verifyRecaptcha($recaptchaToken)) {
        error_log("RECAPTCHA FAILED from IP: $ip");
        echo json_encode(['success' => false, 'error' => 'Attività sospetta rilevata. Riprova.']);
        exit;
    }
}

if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Compila tutti i campi.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Email non valida.']);
    exit;
}

if (!$privacy) {
    echo json_encode(['success' => false, 'error' => 'Devi accettare la privacy policy.']);
    exit;
}

// 5. SEND TO ADMIN
$subject = "NUOVO MESSAGGIO WEB: $name";
$body = "
========================================
📬 NUOVO MESSAGGIO DAL SITO
========================================

👤 NOME:    $name
📧 EMAIL:   $email
📅 DATA:    " . date('d/m/Y H:i') . "

========================================
📝 MESSAGGIO
========================================

$message

========================================
";

// Headers Sicuri (Reply-To fisso su info@ per evitare SPAM/Spoofing)
$headers = "Reply-To: info@davidefiore.com\r\n";
$headers .= "Content-Type: text/plain; charset=utf-8\r\n";
$headers .= "X-Mailer: PHP secure";

// Usa la costante REPORT_EMAIL dal config.php (fio.davide, info@...)
$sent = safeMail(REPORT_EMAIL, $subject, $body, $headers);

// 6. AUTO-REPLY (Il Regalo)
if ($sent && !empty($email)) {
    // Subject "neutro" anti-spam
    $clientSubject = "Conferma ricezione - Davide Fiore";
    $clientBody = "Ciao $name,\n\n";
    $clientBody .= "Ho ricevuto il tuo messaggio e ti risponderò al più presto.\n\n";
    $clientBody .= "Nel frattempo, come ringraziamento per il contatto, voglio darti accesso a una risorsa riservata.\n";
    $clientBody .= "Ho creato una guida interattiva sulla sicurezza digitale che di solito riservo ai clienti.\n\n";
    $clientBody .= "🛡️ Accedi al Security Hub: https://www.davidefiore.com/security_tips.html\n\n";
    $clientBody .= "A presto,\nDavide Fiore";

    $clientHeaders = "Reply-To: info@davidefiore.com\r\n";
    $clientHeaders .= "Content-Type: text/plain; charset=utf-8\r\n";
    $clientHeaders .= "X-Mailer: PHP secure";

    safeMail($email, $clientSubject, $clientBody, $clientHeaders);
}

if ($sent) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Errore server invio email.']);
}
?>
