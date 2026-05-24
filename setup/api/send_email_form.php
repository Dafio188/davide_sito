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

// 3. GET DATA
$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$message = trim($data['message'] ?? '');
$privacy = $data['privacy'] ?? false; // GDPR CHECK

// 4. VALIDATION
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
