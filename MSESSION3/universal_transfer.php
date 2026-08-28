<?php
// Processing Logic Block for Batch Submission
// FIX: Check for the action via $_GET to avoid conflicts with raw JSON payloads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'commit_batch') {
    header('Content-Type: application/json');
    
    $myConn = new mysqli("localhost", "root", "", "session_mode");
    if ($myConn->connect_error) {
        echo json_encode(["success" => false, "message" => "Database connection to session_mode failed."]);
        exit;
    }

    $rawJson = file_get_contents("php://input");
    $payload = json_decode($rawJson, true);

    if (!empty($payload['items'])) {
        $batchToken = 'BATCH-' . strtoupper(bin2hex(random_bytes(4)));
        
        // FIX: Provide a default fallback fallback if mode isn't explicitly defined in payload
        $mode = $payload['mode'] ?? 'OUT'; 
        $successCount = 0;

        $stmt = $myConn->prepare("INSERT INTO weight_transfers (batch_token, mode, department_id, department_name, random_code, customer_remark, weight) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($payload['items'] as $item) {
            // FIX: Ensure keys exist to avoid undefined index warnings crashing JSON output
            $deptId   = intval($item['department_id'] ?? 0);
            $deptName = $item['department_name'] ?? 'Unknown';
            $rCode    = $item['random_code'] ?? 'N/A';
            $remark   = $item['remark'] ?? '';
            $weight   = floatval($item['weight'] ?? 0.0);

            $stmt->bind_param("ssisssd", 
                $batchToken,
                $mode,
                $deptId,
                $deptName,
                $rCode,
                $remark,
                $weight
            );
            if ($stmt->execute()) { 
                $successCount++; 
            }
        }
        $stmt->close();
        echo json_encode(["success" => true, "count" => $successCount]);
    } else {
        echo json_encode(["success" => false, "message" => "Queue is empty."]);
    }
    $myConn->close();
    exit;
}
?>