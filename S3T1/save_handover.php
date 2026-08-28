<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$serverName = "localhost\SQLEXPRESS";
$dbName = "21kEuroStar";
$username = "sa";
$password = "123456";

// Get the input data
$data = json_decode(file_get_contents('php://input'), true);
$items = $data['transfers'] ?? $data['logs'] ?? null;

if (!$items) {
    echo json_encode(["success" => false, "message" => "No data received."]);
    exit;
}

// Debug log
$debugLog = __DIR__ . '/save_handover_debug.log';
file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Received payload: " . json_encode($data) . "\n", FILE_APPEND);

try {
    // Connect to SQL Server
    $pdo = new PDO("sqlsrv:Server=$serverName;Database=$dbName;Encrypt=false;TrustServerCertificate=true", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->beginTransaction();
    
    $insertCount = 0;
    $inventoryIds = [];

    // STEP 1: Update Department Weights
    $subStmt = $pdo->prepare("UPDATE Departments SET Weight = Weight - ? WHERE Id = ?");
    $addStmt = $pdo->prepare("UPDATE Departments SET Weight = Weight + ? WHERE Id = ?");

    foreach ($items as $t) {
        $amount = (float)$t['amount'];
        $sourceDept = (int)$t['sourceDept'];
        $destDept = (int)$t['destinationDept'];
        
        if ($amount <= 0) continue;
        
        $subStmt->execute([$amount, $sourceDept]);
        $addStmt->execute([$amount, $destDept]);
        
        file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Dept weights updated: $amount from $sourceDept to $destDept\n", FILE_APPEND);
    }

    // STEP 2: Handle Inventory - Create new inventory item
    $insertStmt = $pdo->prepare("INSERT INTO Inventories (ProductName, Remark, Weight, DepartmentId, IsStone) VALUES (?, ?, ?, ?, 0)");

    foreach ($items as $index => $t) {
        $amount = (float)$t['amount'];
        $destDept = (int)$t['destinationDept'];
        // Use the QR code as the ProductName if available, otherwise use 'ISSUE_TRANS'
        $productName = $data['random_code'] ?? $t['productname'] ?? 'ISSUE_TRANS';
        $remark = $t['remark'] ?? '';

        if ($amount <= 0) continue;

        file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Inserting inventory: $productName, $amount, $destDept\n", FILE_APPEND);

        $insertStmt->execute([
            strtoupper($productName),
            $remark,
            $amount,
            $destDept
        ]);

        $newId = $pdo->lastInsertId();
        $inventoryIds[] = $newId;
        $insertCount++;
        $items[$index]['newInventoryId'] = $newId;
        
        file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Inventory created: ID $newId\n", FILE_APPEND);
    }

    // STEP 3: Create Transaction Logs
    $logStmt = $pdo->prepare("INSERT INTO TransactionLogs 
        ([SourceDepartmentId], [DestinationDepartmentId], [Weight], [Remark], [User], [CreatedOn], [InventoryId], [UserId]) 
        VALUES (?, ?, ?, ?, ?, GETDATE(), ?, 0)");

    $sender = $data['sender'] ?? 'SYSTEM';
    $logCount = 0;

    foreach ($items as $t) {
        if (isset($t['newInventoryId']) && $t['newInventoryId'] > 0) {
            file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Creating log for Inventory ID: " . $t['newInventoryId'] . "\n", FILE_APPEND);
            
            $logStmt->execute([
                (int)$t['sourceDept'],
                (int)$t['destinationDept'],
                (float)$t['amount'],
                (string)($t['remark'] ?? ''),
                (string)$sender,
                (int)$t['newInventoryId']
            ]);
            $logCount++;
        }
    }

    // ============================================================
    // STEP 4: Commit the SQL Server transaction
    // ============================================================
    $pdo->commit();
    
    file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Transaction committed. InsertCount: $insertCount, LogCount: $logCount\n", FILE_APPEND);

    // ============================================================
    // STEP 5: ONLY update QR Tracking if records were actually inserted
    // ============================================================
    $mode = $data['mode'] ?? 'OUT';
    $randomCode = $data['random_code'] ?? null;
    $qrUpdated = false;

    // IMPORTANT: Only update QR tracking if records were inserted
    if ($randomCode && $insertCount > 0 && $logCount > 0) {
        try {
            $myConn = new mysqli("localhost", "root", "", "barcode_db");
            if (!$myConn->connect_error) {
                if ($mode === 'OUT') {
                    // OUTBOUND: Mark as IN TRANSIT only if records were inserted
                    $sql = "INSERT INTO qr_tracking (random_code, status, outbound_date, outbound_transaction_id) 
                            VALUES (?, 'in_transit', NOW(), ?) 
                            ON DUPLICATE KEY UPDATE 
                                status = 'in_transit', 
                                outbound_date = NOW(), 
                                outbound_transaction_id = ?,
                                inbound_date = NULL,
                                inbound_transaction_id = NULL";
                    $stmt = $myConn->prepare($sql);
                    $stmt->bind_param("sii", $randomCode, $insertCount, $insertCount);
                    $stmt->execute();
                    $stmt->close();
                    $qrUpdated = true;
                    file_put_contents($debugLog, date('Y-m-d H:i:s') . " - QR Tracking updated: $randomCode -> in_transit\n", FILE_APPEND);
                    
                } elseif ($mode === 'IN') {
                    // INBOUND: ONLY set to ACTIVE after successful commit
                    $sql = "UPDATE qr_tracking SET 
                                status = 'active', 
                                inbound_date = NOW(), 
                                inbound_transaction_id = ?,
                                outbound_date = NULL
                            WHERE random_code = ?";
                    $stmt = $myConn->prepare($sql);
                    $stmt->bind_param("is", $insertCount, $randomCode);
                    $stmt->execute();
                    $stmt->close();
                    $qrUpdated = true;
                    file_put_contents($debugLog, date('Y-m-d H:i:s') . " - QR Tracking updated: $randomCode -> active\n", FILE_APPEND);
                }
                $myConn->close();
            } else {
                file_put_contents($debugLog, date('Y-m-d H:i:s') . " - MySQL connection failed: " . $myConn->connect_error . "\n", FILE_APPEND);
            }
        } catch (Exception $e) {
            file_put_contents($debugLog, date('Y-m-d H:i:s') . " - QR Tracking update failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    } else {
        file_put_contents($debugLog, date('Y-m-d H:i:s') . " - QR Tracking NOT updated. randomCode: $randomCode, insertCount: $insertCount, logCount: $logCount\n", FILE_APPEND);
    }

    echo json_encode([
        "success" => true,
        "message" => "Handover Successful",
        "rows_inserted" => $insertCount,
        "inventory_ids" => $inventoryIds,
        "logs_inserted" => $logCount,
        "qr_updated" => $qrUpdated
    ]);

} catch (Exception $e) {
    // Rollback SQL Server transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    file_put_contents($debugLog, date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>