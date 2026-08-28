<?php
$payload = [
    "sourceDept" => 10085,
    "destinationDept" => 11090,
    "sender" => "BRYAN",
    "random_code" => "TEST_DEBUG_" . date('YmdHis'),
    "mode" => "OUT",
    "transfers" => [
        [
            "productname" => "ISSUE_TRANS",
            "amount" => 5.55,
            "remark" => "Debug Test",
            "sourceDept" => 10085,
            "destinationDept" => 11090
        ]
    ]
];

echo "<h2>Testing save_handover.php</h2>";
echo "<pre>";
echo "Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init("http://localhost/S3T1/save_handover.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 30
]);
$res = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

echo "Response: " . $res . "\n";
if ($err) echo "CURL Error: " . $err . "\n";
echo "</pre>";

// Check if error logs exist
echo "<h3>Debug Logs</h3>";
$logFile = __DIR__ . '/save_handover_debug.log';
if (file_exists($logFile)) {
    echo "<pre>" . file_get_contents($logFile) . "</pre>";
} else {
    echo "No debug log found.";
}
?>