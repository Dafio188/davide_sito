<?php
// Diagnostica V3.0 - Deep Gemini Test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>📡 Gemini API Deep Trace</h1>";

if (file_exists('config.php')) {
    require_once 'config.php';
    echo "<p>✅ config.php caricato.</p>";
} else {
    // Fallback manuale per il test
    define('GEMINI_API_KEY', 'AIzaSyA-FCtkZUETvNFlKUs0Zj5rAVA-uVTdans'); 
    echo "<p>⚠️ config.php non trovato. Uso chiave fallback.</p>";
}

function listModels() {
    echo "<h3>🔍 Listing Available Models...</h3>";
    $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . GEMINI_API_KEY;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // SSL Relaxed
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    
    if ($httpCode == 200) {
        $data = json_decode($res, true);
        echo "<ul>";
        foreach ($data['models'] as $model) {
            // Filter for 'generateContent' capable models
            if (in_array("generateContent", $model['supportedGenerationMethods'])) {
                echo "<li><strong>" . $model['name'] . "</strong><br><small>" . $model['version'] . "</small></li>";
            }
        }
        echo "</ul>";
        echo "<p style='color:green'>✅ Lista ricevuta. Copia il nome esatto di un modello Gemini sopra.</p>";
    } else {
        echo "<pre style='color:red; background:#eee; padding:10px;'>" . htmlspecialchars($res) . "</pre>";
        echo "<p style='color:red'>❌ LIST MODELS FALLITO. La chiave potrebbe essere non valida o servizi API disabilitati.</p>";
    }
}

listModels();
?>
