<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$db = $_GET['db'] ?? '';
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['logs'])) {
    die(json_encode(["success" => false, "message" => "No log data received"]));
}

try {
    $pdo = getPDO($db);
    $pdo->beginTransaction();

    // Use brackets for all columns to be safe with SQL Server reserved words
    $sql = "INSERT INTO TransactionLogs 
            ([SourceDepartmentId], [DestinationDepartmentId], 
            [Weight], [Remark], [User], [CreatedOn], [InventoryId], [UserId]) 
            VALUES (?, ?, ?, ?, ?, GETDATE(), ?, 0)";
    
    $stmt = $pdo->prepare($sql);
    $count = 0;

    foreach ($data['logs'] as $l) {
        // Ensure values are the correct types
        $params = [
            (int)$l['sourceDept'], 
            (int)$l['destinationDept'], 
            (float)$l['amount'], 
            (string)($l['remark'] ?? ''), 
            (string)($data['sender'] ?? 'System'), 
            (int)$l['newInventoryId'] // Crucial: must be an INT
        ];

        if ($stmt->execute($params)) {
            $count++;
        }
    }

    $pdo->commit();
    echo json_encode(["success" => true, "rows_inserted" => $count]);

} catch (Exception $e) { 
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack(); 
    echo json_encode(["success" => false, "message" => "SQL Error: " . $e->getMessage()]); 
}