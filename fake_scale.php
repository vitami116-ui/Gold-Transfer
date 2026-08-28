<?php
// Enable CORS so your browser frontend can fetch it smoothly
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Simulate weight fluctuation (you can change this base value or randomize it)
$baseWeight = 15.250;
$jitter = (sin(time() * 2) * 0.02) + ((mt_rand(0, 10) / 1000) - 0.005);
$simulatedWeight = max(0.000, $baseWeight + $jitter);

$response = [
    "valid" => true,
    "weight" => round($simulatedWeight, 3),
    "unit" => "g",
    "status" => "STABLE"
];

echo json_encode($response);