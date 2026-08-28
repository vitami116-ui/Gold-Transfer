<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// MSSQL Database Configuration
$db_host = 'localhost\SQLEXPRESS'; // Update with your MSSQL IP/Instance
$db_name = '21kEuroStar';
$db_user = 'sa';
$db_pass = '123456';
$recycletag = 1;

try {
    // PDO Connection to SQL Server
    $pdo = new PDO("sqlsrv:Server=$db_host;Database=$db_name", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database Connection Failed: ' . $e->getMessage()
    ]);
    exit;
}

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

try {
    // 2. QUERY MSSQL INVENTORIES TABLE
    // Fetch all records associated with this tag code, ordered by creation date
    $stmt = $pdo->prepare("
        SELECT 
            [Id],
            [ProductName],
            [Weight],
            [DepartmentId],
            [Remark],
            [CreatedOn]
        FROM [21kEuroStar].[dbo].[Inventories]
        WHERE [ProductName] = :code
        ORDER BY [CreatedOn] ASC
    ");
    $stmt->execute([':code' => $code]);
    $records = $stmt->fetchAll();

    $recordCount = count($records);

    // ==========================================
    // 3. OUTBOUND (ISSUE MODE) VALIDATION
    // ==========================================
    if ($mode === 'OUT') {
        // Even count = Currently IN TRANSIT
        // Odd count = Cycle is COMPLETE / CLOSED (ready to be recycled/reissued)
        $isInTransit = ($recordCount % 2 !== 0);

        if ($recordCount > 0 && $isInTransit && $recycletag == 1) {
            // Block reuse because it's currently active / in transit (even count)
            echo json_encode([
                'success' => true,
                'exists' => true,
                'already_issued' => true,
                'in_transit' => true,
                'message' => "Tag [{$code}] is currently IN TRANSIT (active cycle, count: {$recordCount}). Cannot reissue until completed."
            ]);
            exit;
        }

        if ($recordCount > 0 && !$isInTransit && $recycletag == 1) {
            // Allow recycling because the cycle is complete (odd count)
            echo json_encode([
                'success' => true,
                'exists' => false,
                'already_issued' => false,
                'in_transit' => false,
                'message' => "Tag [{$code}] previous cycle is complete (odd count: {$recordCount}). Recycling allowed."
            ]);
            exit;
        }

        if ($recordCount > 0 && $recycletag == 0) {
            // Strict behavior: any existing record blocks reissue
            echo json_encode([
                'success' => true,
                'exists' => true,
                'already_issued' => true,
                'message' => "Tag [{$code}] has already been issued."
            ]);
            exit;
        }

        // Fresh and unused tag
        echo json_encode([
            'success' => true,
            'exists' => false,
            'already_issued' => false,
            'message' => 'Tag available for issue.'
        ]);
        exit;
    }

    // ==========================================
    // 4. INBOUND (RETURN MODE) VALIDATION
    // ==========================================
    if ($mode === 'IN') {
        // Even count = Currently IN TRANSIT (ready to be received back)
        // Odd count = Cycle is completed/closed
        $isInTransit = ($recordCount % 2 !== 0);

        if ($recordCount === 0) {
            // No record found for this tag
            echo json_encode([
                'success' => false,
                'message' => "No active outbound transfer record found for Tag [{$code}]."
            ]);
            exit;
        }

        if ($recordCount > 0 && !$isInTransit && $recycletag == 1) {
            // Cycle already completed (odd count)
            echo json_encode([
                'success' => true,
                'already_returned' => true,
                'message' => "Tag [{$code}] has already been returned and finalized (odd count: {$recordCount})."
            ]);
            exit;
        }

        if ($recordCount > 0 && $isInTransit && $recycletag == 1) {
            // Valid Inbound: Tag is in transit (even count), allow processing return
            $outRecord = $records[$recordCount - 1]; // Get latest outbound record
            $createdTime = new DateTime($outRecord['CreatedOn']);
            $currentTime = new DateTime();
            
            // Calculate age of tag in hours
            $timeDiff = $currentTime->diff($createdTime);
            $hoursDiff = ($timeDiff->days * 24) + $timeDiff->h + ($timeDiff->i / 60.0);
            $isExpired = $hoursDiff > 24.0;

            echo json_encode([
                'success'          => true,
                'outbound_weight'  => (float)$outRecord['Weight'],
                's_dept'           => $outRecord['DepartmentId'], // Issuing Department ID
                'd_dept'           => null,                       // Will be assigned by user card
                'created_on'       => $outRecord['CreatedOn'],
                'hours_diff'       => round($hoursDiff, 1),
                'is_expired'       => $isExpired,
                'already_returned' => false,
                'in_transit'       => true,
                'remark'           => $outRecord['Remark'] ?? 'N/A',
                'message'          => "Tag [{$code}] is valid for return (in transit, count: {$recordCount})."
            ]);
            exit;
        }

        // Fallback for strict mode or unexpected flow
        echo json_encode([
            'success' => false,
            'message' => "Tag [{$code}] validation failed for Inbound mode."
        ]);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Query Execution Error: ' . $e->getMessage()
    ]);
    exit;
}