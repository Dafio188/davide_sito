<?php
// Test Mail Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

$to = "info@davidefiore.com, fio.davide@gmail.com";
$subject = "Test Email from Server - " . date("Y-m-d H:i:s");
$message = "This is a test email to verify that the PHP mail() function is working correctly on your hosting server.";
$headers = "From: noreply@davidefiore.com" . "\r\n" .
           "Reply-To: noreply@davidefiore.com" . "\r\n" .
           "X-Mailer: PHP/" . phpversion();

echo "<h2>PHP Mail Test</h2>";
echo "<p>Attempting to send email to: <strong>$to</strong></p>";

if (mail($to, $subject, $message, $headers)) {
    echo "<p style='color: green; font-weight: bold;'>SUCCESS: Mail accepted for delivery.</p>";
    echo "<p>Please check your inbox (and spam folder).</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>FAILURE: Mail function returned false.</p>";
    echo "<p>Possible reasons:</p><ul><li>PHP mail() is disabled</li><li>SMTP is not configured</li><li>Server restrictions</li></ul>";
}

echo "<h3>PHP Info:</h3>";
echo "Safe Mode: " . (ini_get('safe_mode') ? 'On' : 'Off') . "<br>";
echo "Sendmail Path: " . ini_get('sendmail_path') . "<br>";
?>
