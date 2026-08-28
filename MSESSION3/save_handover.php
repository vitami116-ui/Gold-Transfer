<?php
header('Content-Type: application/json');
$dbKey = $_GET['db'] ?? '21kEuroStar';
$dbName = "21kEuroStar";

$baseUrl = "http://192.168.88.88:81/s2t1/";
$data = json_decode(file_get_contents('php://input'), true);
$items = $data['transfers'] ?? $data['logs'] ?? null;

if (!$items) {
    echo json_encode(["success" => false, "message" => "No data received."]);
    exit;
}

function postToEndpoint($url, $payload) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

// STEP 1: Update Dept Weights (+/-)
postToEndpoint("{$baseUrl}DeptWeightTransfer.php?db=$dbName", ["transfers" => $items]);

// STEP 2: Handle Inventory (Deduct/Delete Source, Create Destination)
$invRes = postToEndpoint("{$baseUrl}InventoriesTrans.php?db=$dbName", ["transfers" => $items]);

if ($invRes['success'] && isset($invRes['inserted'][0]['InventoryId'])) {
    $newId = $invRes['inserted'][0]['InventoryId'];
    foreach ($items as &$item) { $item['newInventoryId'] = $newId; }
} else {
    echo json_encode(["success" => false, "message" => "Inventory creation failed: " . json_encode($invRes)]);
    exit;
}

// STEP 3: Create Log - Pass the sender
$sender = $data['sender'] ?? 'SYSTEM';  // <-- Get sender from data
$logRes = postToEndpoint("{$baseUrl}Logs.php?db=$dbName", [
    'logs' => $items,
    'sender' => $sender  // <-- Pass sender to Logs.php
]);

echo json_encode(["success" => true, "message" => "Handover Successful", "log_result" => $logRes]);