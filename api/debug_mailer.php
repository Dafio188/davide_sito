<?php
// Script di Diagnostica Email (Avanzato)
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Email</title>
    <style>body{font-family:sans-serif;padding:20px;max-width:600px;margin:0 auto;line-height:1.6;}</style>
</head>
<body>
    <h1>✉️ Debug Invio Email</h1>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $testEmail = trim($_POST['email'] ?? '');
        
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            echo "<p style='color:red'>❌ Inserisci una email valida.</p>";
        } else {
            echo "<h3>Risultati Test:</h3>";
            
            // CONFIGURAZIONE 1: Senza Header From (Default Server)
            $subject1 = "Test A: Headers Base (No From)";
            $headers1 = "Reply-To: info@davidefiore.com\r\n";
            $headers1 .= "Content-Type: text/plain; charset=utf-8\r\n";
            $headers1 .= "X-Mailer: PHP/" . phpversion();
            
            $sent1 = @mail($testEmail, $subject1, "Test A ricevuto.\nMetodo: Default Sendmail.", $headers1);
            
            echo "<p>Test A (Senza 'From'): " . ($sent1 ? "<b style='color:green'>INVIO RIUSCITO (TRUE)</b>" : "<b style='color:red'>ERRORE (FALSE)</b>") . "</p>";

            // CONFIGURAZIONE 2: Con Header From Esplicito
            $subject2 = "Test B: Headers Completi (With From)";
            $headers2 = "From: info@davidefiore.com\r\n";
            $headers2 .= "Reply-To: info@davidefiore.com\r\n";
            $headers2 .= "Content-Type: text/plain; charset=utf-8\r\n";
            $headers2 .= "X-Mailer: PHP/" . phpversion();
            
            $sent2 = @mail($testEmail, $subject2, "Test B ricevuto.\nMetodo: Explicit From Header.", $headers2);
            
            echo "<p>Test B (Con 'From: info@davidefiore.com'): " . ($sent2 ? "<b style='color:green'>INVIO RIUSCITO (TRUE)</b>" : "<b style='color:red'>ERRORE (FALSE)</b>") . "</p>";
            
            echo "<hr>";
            echo "<p><strong>Istruzioni:</strong> Controlla la tua casella di posta ($testEmail).<br>";
            echo "1. Se hai ricevuto A ma non B -> Il server blocca il cambio del mittente.<br>";
            echo "2. Se hai ricevuto B ma non A -> Gmail/Provider ha bloccato A per spam.<br>";
            echo "3. Se non hai ricevuto nulla -> Problema più grave (IP Blacklist o coda bloccata).</p>";
        }
    }
    ?>

    <form method="POST">
        <label>Inserisci la tua email di test (es. Gmail):</label><br>
        <input type="email" name="email" required style="padding:10px; width:100%; margin:10px 0;" placeholder="la.tua.email@gmail.com">
        <button type="submit" style="padding:10px 20px; cursor:pointer;">Avvia Test Diagnostico</button>
    </form>
</body>
</html>
