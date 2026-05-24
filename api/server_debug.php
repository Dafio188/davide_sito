<?php
// Script di Diagnostica Server (per HTTPS Loop)
// Stampa gli header per capire come il Load Balancer comunica con Apache
echo "<h1>Server Header Dump</h1>";
echo "<table border='1' cellpadding='10'>";

$keysToCheck = [
    'HTTPS', 
    'SERVER_PORT', 
    'HTTP_X_FORWARDED_PROTO', 
    'HTTP_X_FORWARDED_SSL', 
    'HTTP_FRONT_END_HTTPS',
    'REQUEST_SCHEME',
    'REMOTE_ADDR'
];

foreach ($keysToCheck as $key) {
    echo "<tr><td><strong>$key</strong></td><td>" . ($_SERVER[$key] ?? '<span style="color:red">MISSING</span>') . "</td></tr>";
}
echo "</table>";

echo "<h3>Full Dump:</h3>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";
?>
