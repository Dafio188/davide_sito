<?php
// Script di test per invio email
error_reporting(E_ALL);
ini_set('display_errors', 1);

$to = "info@davidefiore.com, fio.davide@gmail.com";
$subject = "TEST EMAIL MANUALE " . date('H:i:s');
$message = "Se leggi questo, il server invia correttamente le email.\nHeaders usati:\nReply-To e Content-Type.";

// Headers identici a chat.php
$headers = "Reply-To: info@davidefiore.com\r\n";
$headers .= "Content-Type: text/plain; charset=utf-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

echo "Tentativo di invio a: $to<br>";
echo "Oggetto: $subject<br>";
echo "Headers: <pre>$headers</pre><br>";

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo "<h1 style='color:green'>✅ INVIO RIUSCITO (TRUE)</h1>";
    echo "Controlla la casella di posta (anche Spam).";
} else {
    echo "<h1 style='color:red'>❌ INVIO FALLITO (FALSE)</h1>";
    echo "Il server ha rifiutato di inviare l'email.";
}
?>
