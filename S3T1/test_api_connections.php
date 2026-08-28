<?php
$baseUrl = "http://192.168.88.88:81/s2t1/";

echo "<h2>Testing API Endpoints</h2>";

$endpoints = [
    "DeptWeightTransfer.php?db=21kEuroStar",
    "InventoriesTrans.php?db=21kEuroStar",
    "Logs.php?db=21kEuroStar"
];

foreach ($endpoints as $endpoint) {
    $url = $baseUrl . $endpoint;
    echo "<h3>Testing: $url</h3>";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_NOBODY => true // Just check if it's reachable
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        echo "❌ Error: $err<br>";
    } else {
        echo "✅ HTTP Code: $httpCode<br>";
    }
    echo "<br>";
}
?>