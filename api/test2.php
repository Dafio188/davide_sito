<?php
// Test file - versione 2
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Test Gemini v2</h1>";

$key = 'AIzaSyA-FCtkZUETvNFlKUs0Zj5rAVA-uVTdans';
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $key;

echo "<p>Modello: gemini-2.5-flash</p>";
echo "<p>URL: " . htmlspecialchars($url) . "</p>";

$body = json_encode([
    'contents' => [['role' => 'user', 'parts' => [['text' => 'Rispondi: OK']]]]
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: $code</p>";

if ($code === 200) {
    $data = json_decode($response, true);
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'N/A';
    echo "<p style='color:green'>SUCCESSO! Risposta: $text</p>";
} else {
    echo "<p style='color:red'>ERRORE</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}
?>
