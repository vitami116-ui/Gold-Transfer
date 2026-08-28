<?php
$payload = [
    "sourceDept" => 10085,
    "destinationDept" => 11090,
    "sender" => "TEST_USER",
    "random_code" => "TEST" . date('YmdHis'),
    "mode" => "OUT",
    "transfers" => [
        [
            "productname" => "ISSUE_TRANS",
            "amount" => 5.0,
            "remark" => "Test OUTBOUND - Direct DB",
            "sourceDept" => 10085,
            "destinationDept" => 11090
        ]
    ]
];

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

echo "<h2>Test Save Handover</h2>";
echo "<pre>";
echo "Response: " . print_r(json_decode($res, true), true) . "\n";
echo "Error: " . $err;
echo "</pre>";
?>