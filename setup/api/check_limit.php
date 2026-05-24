<?php
// DIAGNOSTIC SCRIPT - CHECK CALENDAR LIMITS
$CAL_API_KEY   = 'cal_live_91bd0d4c0256ce06faca4395d055c769';
$CAL_EVENT_ID  = '4249403'; 

$start = date('Y-m-d');
$end   = date('Y-m-d', strtotime("+60 days"));

echo "<h1>Diagnostica Calendario</h1>";
echo "<p>Richiesta inviata a Cal.com: Slot dal <b>$start</b> al <b>$end</b> (60 Giorni)</p>";

$url = "https://api.cal.com/v1/slots?apiKey={$CAL_API_KEY}&startTime={$start}&endTime={$end}&eventTypeId={$CAL_EVENT_ID}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
$res = curl_exec($ch);
curl_close($ch);

$data = json_decode($res, true);
$slots = $data['slots'] ?? [];

if (empty($slots)) {
    echo "<h2 style='color:red'>NESSUNO SLOT TROVATO</h2>";
    echo "<p>Risposta API: " . htmlspecialchars(substr($res, 0, 500)) . "</p>";
} else {
    $dates = array_keys($slots);
    $first = reset($dates);
    $last = end($dates);
    $count = 0;
    foreach($slots as $s) $count += count($s);
    
    echo "<h2>Risultati:</h2>";
    echo "<ul>";
    echo "<li>Totale Slot Trovati: <b>$count</b></li>";
    echo "<li>Prima Data Disponibile: <b>$first</b></li>";
    echo "<li>Ultima Data Disponibile: <b>$last</b></li>";
    echo "</ul>";
    
    echo "<h3>Analisi:</h3>";
    $lastDate = new DateTime($last);
    $targetDate = new DateTime('2026-01-12'); // Jan 12th
    
    if ($lastDate < $targetDate) {
        echo "<p style='color:red; font-weight:bold'>⚠️ IL LIMITE È SU CAL.COM</p>";
        echo "<p>Il codice chiede 60 giorni, ma Cal.com restituisce dati solo fino al <b>" . $lastDate->format('d/m/Y') . "</b>.</p>";
        echo "<p><b>Soluzione:</b> Vai su Cal.com > Event Types > [Tuo Evento] > Availability / Limits > Controlla 'Minimum booking notice' o 'Future booking limit'. Deve essere impostato almeno a 60 giorni.</p>";
    } else {
        echo "<p style='color:green'>✅ La data del 12 Gennaio è visibile.</p>";
    }
}
?>
