<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Error reporting - turn off for production
error_reporting(0);
ini_set('display_errors', 0);

// Set timeout to prevent hanging
set_time_limit(10);

// MSSQL Database Configuration - Use sqlsrv_connect instead of PDO
$serverName = "localhost\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "21kEuroStar",
    "Uid" => "sa",
    "PWD" => "123456",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true,
    "Encrypt" => false
);

// MySQL Connection for barcode_db (QR tracking)
$mysql_host = 'localhost';
$mysql_user = 'root';
$mysql_pass = '';
$mysql_db = 'barcode_db';

// 1. INPUT PARSING
$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$mode = isset($_GET['mode']) ? strtoupper(trim($_GET['mode'])) : 'IN';

if (empty($code)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing barcode tag parameter.'
    ]);
    exit;
}

// ============================================================
// QUICK CHECK: Check qr_tracking first (MySQL)
// ============================================================
$mysqlInTransit = false;
$mysqlStatus = 'unknown';

try {
    $myConn = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
    if (!$myConn->connect_error) {
        $sql = "SELECT status FROM qr_tracking WHERE random_code = ?";
        $stmt = $myConn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $code);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $mysqlStatus = $row['status'];
                $mysqlInTransit = ($row['status'] === 'in_transit');
            }
            $stmt->close();
        }
        $myConn->close();
    }
} catch (Exception $e) {
    // Ignore MySQL errors
}

// ============================================================
// If MySQL says IN TRANSIT, respond quickly without checking MSSQL
// ============================================================
if ($mysqlInTransit && $mode === 'OUT') {
    echo json_encode([
        'success' => false,
        'exists' => true,
        'already_issued' => true,
        'in_transit' => true,
        'status' => 'in_transit',
        'outbound_date' => null,
        'record_count' => 0,
        'message' => "Tag [{$code}] is currently IN TRANSIT. Must be returned (INBOUND) before reissue."
    ]);
    exit;
}

// ============================================================
// Connect to SQL Server using sqlsrv
// ============================================================
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    // If MSSQL connection fails, but MySQL says active, allow OUTBOUND
    if ($mode === 'OUT') {
        echo json_encode([
            'success' => true,
            'exists' => false,
            'already_issued' => false,
            'in_transit' => false,
            'status' => 'available',
            'message' => 'Tag available for issue (MSSQL unavailable).'
        ]);
        exit;
    } else {
        // For INBOUND, we need to check if there's an outbound record
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed. Please try again.'
        ]);
        exit;
    }
}

try {
    // Check MSSQL Inventories
    $records = [];
    $recordCount = 0;
    
    $sql = "SELECT Id, ProductName, Weight, DepartmentId, Remark, CreatedOn 
            FROM Inventories 
            WHERE ProductName = ? 
            ORDER BY CreatedOn ASC";
    $stmt = sqlsrv_query($conn, $sql, array($code));
    
    if ($stmt === false) {
        throw new Exception('Query failed: ' . print_r(sqlsrv_errors(), true));
    }
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $records[] = $row;
    }
    sqlsrv_free_stmt($stmt);
    $recordCount = count($records);
    
    // If no exact match, try LIKE search
    if ($recordCount === 0) {
        $sql = "SELECT Id, ProductName, Weight, DepartmentId, Remark, CreatedOn 
                FROM Inventories 
                WHERE ProductName LIKE ? 
                ORDER BY CreatedOn ASC";
        $likeCode = '%' . $code . '%';
        $stmt = sqlsrv_query($conn, $sql, array($likeCode));
        
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $records[] = $row;
            }
            sqlsrv_free_stmt($stmt);
            $recordCount = count($records);
        }
    }
    
    // Determine if in transit from record count (odd = in transit)
    $isInTransit = ($recordCount > 0 && $recordCount % 2 !== 0);
    
    // If MySQL says ACTIVE but MSSQL says IN TRANSIT, update MySQL
    if ($isInTransit && !$mysqlInTransit) {
        try {
            $myConn = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
            if (!$myConn->connect_error) {
                $sql = "INSERT INTO qr_tracking (random_code, status, outbound_date) 
                        VALUES (?, 'in_transit', NOW()) 
                        ON DUPLICATE KEY UPDATE 
                            status = 'in_transit', 
                            outbound_date = NOW()";
                $stmt2 = $myConn->prepare($sql);
                if ($stmt2) {
                    $stmt2->bind_param("s", $code);
                    $stmt2->execute();
                    $stmt2->close();
                }
                $myConn->close();
            }
        } catch (Exception $e) {
            // Ignore
        }
        $mysqlInTransit = true;
    }
    
    // Get outbound weight
    $outboundWeight = null;
    $hoursDiff = 0;
    $isExpired = false;
    
    if ($recordCount > 0) {
        $outboundRecord = $records[0];
        $outboundWeight = (float)($outboundRecord['Weight'] ?? 0);
        if (isset($outboundRecord['CreatedOn'])) {
            try {
                if ($outboundRecord['CreatedOn'] instanceof DateTime) {
                    $createdTime = $outboundRecord['CreatedOn'];
                } else {
                    $createdTime = new DateTime($outboundRecord['CreatedOn']);
                }
                $currentTime = new DateTime();
                $timeDiff = $currentTime->diff($createdTime);
                $hoursDiff = ($timeDiff->days * 24) + $timeDiff->h + ($timeDiff->i / 60.0);
                // COMMENTED OUT: Expiration check - removed to allow returns anytime
                // $isExpired = $hoursDiff > 24.0;
                $isExpired = false; // Allow returns regardless of age
            } catch (Exception $e) {
                $hoursDiff = 0;
                $isExpired = false;
            }
        }
    }

    // ============================================================
// OUTBOUND (ISSUE) MODE
// ============================================================
if ($mode === 'OUT') {
    // Odd count (1, 3, 5...) = IN TRANSIT - block OUTBOUND
    if ($recordCount % 2 !== 0) {
        echo json_encode([
            'success' => false,
            'exists' => true,
            'already_issued' => true,
            'in_transit' => true,
            'status' => 'in_transit',
            'outbound_date' => null,
            'record_count' => $recordCount,
            'message' => "Tag [{$code}] is currently IN TRANSIT. Must be returned (INBOUND) before reissue."
        ]);
        sqlsrv_close($conn);
        exit;
    }

    // Even count (0, 2, 4...) = Available for OUTBOUND
    echo json_encode([
        'success' => true,
        'exists' => $recordCount > 0,
        'already_issued' => false,
        'in_transit' => false,
        'status' => 'available',
        'message' => 'Tag available for issue.'
    ]);
    sqlsrv_close($conn);
    exit;
}

    // ============================================================
    // INBOUND (RETURN) MODE
    // ============================================================
 // ============================================================
// INBOUND (RETURN) MODE
// ============================================================
if ($mode === 'IN') {
    // Check if record count is even (0, 2, 4, 6...)
    // Even = No active outbound / Cycle complete
    if ($recordCount % 2 === 0) {
        if ($recordCount === 0) {
            echo json_encode([
                'success' => false,
                'message' => "No outbound transfer record found for Tag [{$code}]. Use OUTBOUND mode first."
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Tag [{$code}] has already been returned. This QR cannot be used for INBOUND again. Use OUTBOUND mode to reuse it."
            ]);
        }
        sqlsrv_close($conn);
        exit;
    }
    
    // Odd count (1, 3, 5...) = In transit - allow INBOUND
    $outboundRecord = $records[0];
    $outboundWeight = (float)($outboundRecord['Weight'] ?? 0);
    $hoursDiff = 0;
    
    if (isset($outboundRecord['CreatedOn'])) {
        try {
            if ($outboundRecord['CreatedOn'] instanceof DateTime) {
                $createdTime = $outboundRecord['CreatedOn'];
            } else {
                $createdTime = new DateTime($outboundRecord['CreatedOn']);
            }
            $currentTime = new DateTime();
            $timeDiff = $currentTime->diff($createdTime);
            $hoursDiff = ($timeDiff->days * 24) + $timeDiff->h + ($timeDiff->i / 60.0);
        } catch (Exception $e) {
            $hoursDiff = 0;
        }
    }

    echo json_encode([
        'success'          => true,
        'outbound_weight'  => $outboundWeight,
        's_dept'           => $outboundRecord['DepartmentId'] ?? null,
        'd_dept'           => null,
        'created_on'       => isset($outboundRecord['CreatedOn']) ? 
            ($outboundRecord['CreatedOn'] instanceof DateTime ? 
                $outboundRecord['CreatedOn']->format('Y-m-d H:i:s') : 
                $outboundRecord['CreatedOn']) : null,
        'hours_diff'       => round($hoursDiff, 1),
        'is_expired'       => false,
        'already_returned' => false,
        'remark'           => $outboundRecord['Remark'] ?? 'N/A',
        'in_transit'       => true,
        'record_count'     => $recordCount
    ]);
    sqlsrv_close($conn);
    exit;
}

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
    if (isset($conn)) sqlsrv_close($conn);
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Unknown error occurred'
]);
if (isset($conn)) sqlsrv_close($conn);
exit;
?>