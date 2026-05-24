<?php
/**
 * Test file per verificare la connessione a Gemini API
 * Carica questo file su Aruba e aprilo nel browser per testare
 * URL: https://davidefiore.com/api/test-gemini.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Test Connessione Gemini API</h1>";

// 1. Check PHP version
echo "<h3>1. PHP Version</h3>";
echo "<p>PHP " . phpversion() . " ✅</p>";

// 2. Check cURL
echo "<h3>2. cURL Extension</h3>";
if (function_exists('curl_init')) {
    echo "<p>cURL è attivo ✅</p>";
    $curlVersion = curl_version();
    echo "<p>Versione: " . $curlVersion['version'] . ", SSL: " . $curlVersion['ssl_version'] . "</p>";
} else {
    echo "<p style='color:red'>cURL NON è attivo ❌ - Contatta Aruba per attivarlo</p>";
    exit;
}

// 3. Check SSL
echo "<h3>3. OpenSSL</h3>";
if (extension_loaded('openssl')) {
    echo "<p>OpenSSL è attivo ✅</p>";
} else {
    echo "<p style='color:orange'>OpenSSL potrebbe non essere attivo ⚠️</p>";
}

// 4. Test API call
echo "<h3>4. Test Chiamata Gemini API</h3>";

$GEMINI_API_KEY = 'AIzaSyA-FCtkZUETvNFlKUs0Zj5rAVA-uVTdans';
$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $GEMINI_API_KEY;

$requestBody = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [['text' => 'Rispondi solo con: Test OK']]
        ]
    ],
    'generationConfig' => [
        'maxOutputTokens' => 50,
        'temperature' => 0
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "<p style='color:red'>Errore cURL: $curlError ❌</p>";
    echo "<p>Prova: Il server potrebbe bloccare connessioni esterne. Contatta Aruba.</p>";
} elseif ($httpCode === 200) {
    $data = json_decode($response, true);
    $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No response';
    echo "<p style='color:green'>Connessione OK! ✅</p>";
    echo "<p>Risposta Gemini: <strong>$reply</strong></p>";
} else {
    echo "<p style='color:red'>Errore HTTP $httpCode ❌</p>";
    $errorData = json_decode($response, true);
    if (isset($errorData['error'])) {
        echo "<p>Messaggio: " . $errorData['error']['message'] . "</p>";
        echo "<p>Status: " . $errorData['error']['status'] . "</p>";
    }
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// 5. Check mail function
echo "<h3>5. PHP mail()</h3>";
if (function_exists('mail')) {
    echo "<p>mail() è disponibile ✅</p>";
} else {
    echo "<p style='color:orange'>mail() potrebbe non funzionare ⚠️</p>";
}

echo "<hr><p><a href='/'>Torna al sito</a></p>";
?>
