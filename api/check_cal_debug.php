<?php
// Script di diagnostica specifica per Cal.com
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

echo "<h1>🔍 Cal.com Availability Debug</h1>";
echo "<p>Testando la disponibilità con le seguenti impostazioni:</p>";
echo "<ul>";
echo "<li>EVENT_ID: " . CAL_EVENT_ID . "</li>";
echo "<li>API_KEY: " . substr(CAL_API_KEY, 0, 8) . "...</li>";
echo "</ul>";

function getEventTypeDetails() {
    $url = "https://api.cal.com/v2/event-types/" . CAL_EVENT_ID;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = [
        "Authorization: Bearer " . CAL_API_KEY,
        "cal-api-version: 2024-06-11"
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

$eventDetails = getEventTypeDetails();
echo "<h3>Dettagli EventType (v2)</h3>";
if (isset($eventDetails['status']) && $eventDetails['status'] === 'success') {
    if (isset($eventDetails['data']['bookingFields'])) {
        echo "<ul>";
        foreach ($eventDetails['data']['bookingFields'] as $field) {
            $req = ($field['required'] ?? false) ? "<b>[OBBLIGATORIO]</b>" : "[Opzionale]";
            echo "<li>Campo: <code>" . $field['slug'] . "</code> ($req) - Tipo: " . $field['type'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:orange'>Chiave 'bookingFields' non trovata. Ecco la struttura ricevuta:</p>";
        echo "<pre style='font-size:10px;'>" . print_r($eventDetails['data'] ?? $eventDetails, true) . "</pre>";
    }
} else {
    echo "<p style='color:red'>Errore nella risposta API EventType:</p>";
    echo "<pre>" . print_r($eventDetails, true) . "</pre>";
}
echo "<hr>";

function getCalAvailabilityDebug($days = 60) {
    $start = date('Y-m-d\TH:i:s\Z'); // v2 preferisce ISO format pieno
    $end   = date('Y-m-d\TH:i:s\Z', strtotime("+$days days"));
    
    // v2 Endpoint: /v2/slots/available
    $url = "https://api.cal.com/v2/slots/available?startTime={$start}&endTime={$end}&eventTypeId=" . CAL_EVENT_ID;
    
    echo "<h3>1. Chiamata API (v2)</h3>";
    echo "<p>URL: <code>$url</code></p>";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    // Header v2
    $headers = [
        "Authorization: Bearer " . CAL_API_KEY,
        "cal-api-version: 2024-06-11",
        "Content-Type: application/json"
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
    
    echo "<p>HTTP Code: <b>$httpCode</b></p>";
    if ($curlErr) echo "<p style='color:red'>cURL Error: $curlErr</p>";

    if ($httpCode !== 200) {
        echo "<pre style='background:#fee; padding:10px;'>" . htmlspecialchars($res) . "</pre>";
        return null;
    }
    
    $jsonData = json_decode($res, true);
    
    // v2 response structure: { "status": "success", "data": { "slots": { "2024-04-11": [...] } } }
    if (!isset($jsonData['data']['slots'])) {
        echo "<p style='color:red'>Formato JSON v2 non riconosciuto (manca data.slots).</p>";
        echo "<pre>" . print_r($jsonData, true) . "</pre>";
        return null;
    }

    $slotsData = $jsonData['data']['slots'];
    echo "<h3>2. Dati Ricevuti</h3>";
    echo "<p>Totale giorni con slot: " . count($slotsData) . "</p>";

    $tz = new DateTimeZone('Europe/Rome');
    $structuredSlots = [];

    echo "<h3>3. Filtraggio per Regole Business</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Data</th><th>Giorno</th><th>Azione</th><th>Slot Trovati</th></tr>";

    foreach ($slotsData as $dayDate => $slots) {
        // v2 può restituire orari come chiavi o oggetti. In genere è 'YYYY-MM-DD' => [ {time: ...}, ... ]
        $dayTimestamp = strtotime($dayDate);
        $dayOfWeek = date('N', $dayTimestamp); 
        $dayName = date('l', $dayTimestamp);
        
        $action = "Processato";
        $reason = "";
        
        // Regole Business: NO Merc(3), Solo Lun(1), Mar(2), Gio(4), Ven(5)
        if ($dayOfWeek == 3) { $action = "SALTATO"; $reason = "Regola: No Mercoledì"; }
        elseif (!in_array($dayOfWeek, [1, 2, 4, 5])) { $action = "SALTATO"; $reason = "Regola: Solo Lun, Mar, Gio, Ven"; }
        
        $monthDay = date('m-d', $dayTimestamp);
        if (in_array($monthDay, ['01-01', '12-25', '12-26', '08-15'])) { $action = "SALTATO"; $reason = "Festività"; }
        
        $daySlots = [];
        foreach ($slots as $slot) {
            $dt = new DateTime($slot['time']);
            $dt->setTimezone($tz);
            $time = $dt->format('H:i');
            if ($time >= '09:30' && $time <= '18:00') {
                $daySlots[] = $time;
            }
        }

        echo "<tr>";
        echo "<td>$dayDate</td>";
        echo "<td>$dayName</td>";
        echo "<td><b style='color:".($action=="SALTATO"?"red":"green")."'>$action</b> $reason</td>";
        echo "<td>" . implode(", ", $daySlots) . "</td>";
        echo "</tr>";

        if ($action == "Processato" && !empty($daySlots)) {
            $structuredSlots[$dayDate] = $daySlots;
        }
    }
    echo "</table>";
    
    return $structuredSlots;
}

$slots = getCalAvailabilityDebug(14); // Testiamo i prossimi 14 giorni

echo "<h2>Esito Diagnostica</h2>";
if (empty($slots)) {
    echo "<p style='color:red'>Nessuno slot disponibile trovato dopo i filtri.</p>";
} else {
    echo "<p style='color:green'>Slot trovati correttamente. Il sistema Cal.com è configurato e risponde.</p>";
}
?>
