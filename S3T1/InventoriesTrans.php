<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

// 1. Validate Input
$db = $_GET['db'] ?? null;
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$db || !isset($data['transfers']) || !is_array($data['transfers'])) {
    echo json_encode(["success" => false, "message" => "Invalid request: Missing database or transfer data."]);
    exit;
}

try {
    $pdo = getPDO($db);
    $inserted = [];

    $pdo->beginTransaction();

    // Prepare statements outside the loop for better performance
    $stmtUpdate = $pdo->prepare("UPDATE Inventories SET Weight = Weight - ? WHERE Id = ?");
    $stmtDelete = $pdo->prepare("DELETE FROM Inventories WHERE Id = ? AND Weight <= 0.0001");
    $stmtInsert = $pdo->prepare("INSERT INTO Inventories (ProductName, Remark, Weight, DepartmentId, IsStone) VALUES (?, ?, ?, ?, 0)");

    foreach ($data['transfers'] as $t) {
        // Validation check for each row
        $inventoryId = (int)($t['inventoryId'] ?? 0);
        $amount = (float)($t['amount'] ?? 0);
        $destDept = (int)($t['destinationDept'] ?? 0);

        if ($amount <= 0) continue; // Skip zero-sum transfers

        // 1 & 2. Handle Source Inventory
        if ($inventoryId > 0) {
            $stmtUpdate->execute([$amount, $inventoryId]);
            $stmtDelete->execute([$inventoryId]);
        }

        // 3. Create New Line in Destination
        $stmtInsert->execute([
            strtoupper($t['productname']), 
            $t['remark'] ?? '', 
            $amount, 
            $destDept
        ]);
        
        // Note: SQL Server PDO requires the sequence name for lastInsertId in some configs, 
        // but usually returns the last identity created in the session.
        $inserted[] = ["InventoryId" => $pdo->lastInsertId()];
    }

    $pdo->commit();
    echo json_encode(["success" => true, "inserted" => $inserted]);

} catch (Exception $e) { 
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error to server for the dev, return clean message to JS
    error_log("Inventory Transfer Error: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Database Error: " . $e->getMessage()
    ]); 
}