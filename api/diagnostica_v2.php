<?php
// Diagnostica V2.0 - Debugging 500 Error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🛠️ Diagnostica API Chatbot</h1>";

// 1. Check PHP Version
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

// 2. Check Config File
$configFile = __DIR__ . '/config.php';
if (file_exists($configFile)) {
    echo "<p style='color:green'>✅ config.php trovato.</p>";
    
    // Test Load
    try {
        require_once $configFile;
        echo "<p style='color:green'>✅ config.php caricato con successo.</p>";
        
        // Check Constants
        if (defined('GEMINI_API_KEY') && defined('CAL_API_KEY')) {
             echo "<p style='color:green'>✅ Chiavi API definite.</p>";
             // Print partially to verify (safe)
             echo "<p>Gemini Prefix: " . substr(GEMINI_API_KEY, 0, 5) . "...</p>";
        } else {
             echo "<p style='color:red'>❌ Chiavi API MANCANTI dopo il load.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Errore caricamento config.php: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>❌ config.php E' ASSENTE! (Ecco perché dà errore 500)</p>";
}

// 3. Check Temp Dir (Rate Limit)
$tmp = sys_get_temp_dir();
echo "<p><strong>Temp Dir:</strong> $tmp</p>";
if (is_writable($tmp)) {
    echo "<p style='color:green'>✅ Temp Dir è scrivibile.</p>";
    
    // Try writing test file
    $testFile = $tmp . '/test_write_perm.txt';
    if (@file_put_contents($testFile, 'test')) {
        echo "<p style='color:green'>✅ Scrittura file test riuscita.</p>";
        unlink($testFile);
    } else {
        echo "<p style='color:red'>❌ Scrittura file test FALLITA (file_put_contents).</p>";
    }
} else {
    echo "<p style='color:red'>❌ Temp Dir NON è scrivibile!</p>";
}

// 4. Check SSL/cURL
echo "<h3>Test Connettività (SSL)</h3>";
$ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . (defined('GEMINI_API_KEY') ? GEMINI_API_KEY : ''));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); 
// Note: We are testing WITH verifyhost enabled, as per new config.

$res = curl_exec($ch);
if (curl_errno($ch)) {
    echo "<p style='color:red'>❌ cURL Error: " . curl_error($ch) . "</p>";
} else {
    $info = curl_getinfo($ch);
    echo "<p style='color:green'>✅ cURL OK. HTTP Code: " . $info['http_code'] . "</p>";
}
curl_close($ch);

echo "<hr><p>Se vedi tutto verde, il problema potrebbe essere nel body o nella logica complessa di chat.php.</p>";
?>
