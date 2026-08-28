<?php
// =========================================================================
// BACK-END LOGIC ROUTER (Handles Instant MySQL Writes, Deletions, & Reads)
// =========================================================================
$passedScaleIp = trim($_GET['scale_ip'] ?? '');
// Capture incoming page parameters passed from the previous page
$passedDeptId      = intval($_GET['department_id'] ?? 0);
$passedDeptName    = trim($_GET['department_name'] ?? '');
$passedDestId      = intval($_GET['destination_id'] ?? $_GET['destdepartment_id'] ?? 0);
$passedDestName    = trim($_GET['destination_name'] ?? $_GET['destdepartment_name'] ?? '');
$passedCard        = trim($_GET['card_details'] ?? '');

// Get mode from URL (default to OUT)
$currentMode = isset($_GET['mode']) ? strtoupper(trim($_GET['mode'])) : 'OUT';

// Fallback safety layer
if ($passedDeptId === 0 && isset($_GET['sourceDept'])) {
    $passedDeptId = intval($_GET['sourceDept']);
}
if (empty($passedDeptName) && isset($_GET['sourceDeptName'])) {
    $passedDeptName = $_GET['sourceDeptName'];
}

// =========================================================================
// MYSQL DATABASE CONNECTION (for card registry)
// =========================================================================
$mysql_host = "localhost";
$mysql_user = "root";
$mysql_pass = "";
$mysql_db   = "milano";

$myConn = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
if ($myConn->connect_error) {
    error_log("MySQL connection failed: " . $myConn->connect_error);
}

// =========================================================================
// SQL SERVER CONNECTION FUNCTION (for departments)
// =========================================================================
function getMSSQLConnection() {
    $serverName = "localhost\\SQLEXPRESS";
    $connectionOptions = [
        "Database" => "21kEuroStar", 
        "Uid" => "sa", 
        "PWD" => "123456", 
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true,
        "Encrypt" => false
    ];
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    return $conn;
}

// =========================================================================
// FETCH DEPARTMENT NAME FROM SQL SERVER
// =========================================================================
function getDepartmentNameFromSQL($deptId) {
    if (empty($deptId) || $deptId == 0) return '';
    
    $conn = getMSSQLConnection();
    if (!$conn) return '';
    
    $sql = "SELECT DepartmentName FROM Departments WHERE Id = ?";
    $params = [$deptId];
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        sqlsrv_close($conn);
        return '';
    }
    
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    
    return $row ? trim($row['DepartmentName']) : '';
}

// =========================================================================
// FETCH ALL DEPARTMENTS FOR CACHING
// =========================================================================
function getAllDepartments() {
    $conn = getMSSQLConnection();
    if (!$conn) return [];
    
    $sql = "SELECT Id, DepartmentName FROM Departments ORDER BY DepartmentName";
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt === false) {
        sqlsrv_close($conn);
        return [];
    }
    
    $departments = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $departments[$row['Id']] = trim($row['DepartmentName']);
    }
    
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    
    return $departments;
}

// =========================================================================
// CHECK CARD FROM MYSQL DATABASE (milano.Security_Card_Registry)
// =========================================================================
function checkCardFromMySQL($cardCode) {
    global $myConn;
    
    if (!$myConn || $myConn->connect_error) {
        return null;
    }
    
    $tableCheck = $myConn->query("SHOW TABLES LIKE 'Security_Card_Registry'");
    if ($tableCheck->num_rows == 0) {
        return null;
    }
    
    // ============================================================
    // USE OwnerName column (matches card_action.php)
    // ============================================================
    $sql = "SELECT CardID, OwnerName, sDept, dDept, IsActive, IC_Number 
            FROM Security_Card_Registry 
            WHERE CardID = ? AND IsActive = 1";
    $stmt = $myConn->prepare($sql);
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("s", $cardCode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $card = $result->fetch_assoc();
        $stmt->close();
        return $card;
    }
    
    $stmt->close();
    return null;
}

// =========================================================================
// PARSE BARCODE – NOW ACCEPTS QR WITHOUT s_dept/d_dept
// =========================================================================
function parseBarcodeJSON($barcodeData) {
    $barcodeData = trim($barcodeData);
    
    // Try JSON first
    $decoded = json_decode($barcodeData, true);
    if ($decoded && isset($decoded['random_code'])) {
        return [
            's_dept'      => $decoded['s_dept'] ?? null,
            'd_dept'      => $decoded['d_dept'] ?? null,
            'random_code' => $decoded['random_code'] ?? '',
            'remark'      => $decoded['remark'] ?? 'N/A'
        ];
    }
    
    // Fallback to custom format – now accepts QR without s_dept/d_dept
    $customParsed = parseCustomBarcodeFormat($barcodeData);
    if ($customParsed && $customParsed['random_code']) {
        return $customParsed;
    }
    
    return null;
}

function parseCustomBarcodeFormat($rawCode) {
    try {
        $parsed = [
            's_dept'      => null,
            'd_dept'      => null,
            'random_code' => null,
            'remark'      => 'N/A'
        ];

        // Extract s_dept (optional)
        $sMatch = preg_match('/@sdept@\'([^,]+)/', $rawCode, $sMatches);
        if ($sMatch && isset($sMatches[1])) {
            $parsed['s_dept'] = trim($sMatches[1]);
        }

        // Extract d_dept (optional)
        $dMatch = preg_match('/@ddept@\'([^,]+)/', $rawCode, $dMatches);
        if ($dMatch && isset($dMatches[1])) {
            $parsed['d_dept'] = trim($dMatches[1]);
        }

        // Extract random_code (required)
        $rMatch = preg_match('/@randomcode@\'@([^@]+)@/', $rawCode, $rMatches);
        if ($rMatch && isset($rMatches[1])) {
            $parsed['random_code'] = trim($rMatches[1]);
        } else {
            // Try alternative pattern without quotes
            $rMatch2 = preg_match('/@randomcode@\'?([^\',]+)\'?/', $rawCode, $rMatches2);
            if ($rMatch2 && isset($rMatches2[1])) {
                $parsed['random_code'] = trim($rMatches2[1]);
            }
        }

        // Extract remark (optional)
        $rmMatch = preg_match('/@remark@\'@([^@|]+)@/', $rawCode, $rmMatches);
        if ($rmMatch && isset($rmMatches[1])) {
            $parsed['remark'] = trim($rmMatches[1]);
        } else {
            $rmMatch2 = preg_match('/@remark@\'([^,]+)/', $rawCode, $rmMatches2);
            if ($rmMatch2 && isset($rmMatches2[1])) {
                $parsed['remark'] = trim($rmMatches2[1]);
            }
        }

        // If we have at least random_code, consider it a success
        if ($parsed['random_code']) {
            return $parsed;
        }
        return null;
    } catch (Exception $e) {
        error_log("Custom barcode parse error: " . $e->getMessage());
        return null;
    }
}

// =========================================================================
// GET DEPARTMENT NAME WITH CACHING
// =========================================================================
$departmentCache = [];
$departmentCache = getAllDepartments();

function getDepartmentName($deptId, $fallback = '') {
    global $departmentCache;
    
    if (empty($deptId) || $deptId == 0) {
        return $fallback ?: 'Unknown';
    }
    
    if (isset($departmentCache[$deptId])) {
        return $departmentCache[$deptId];
    }
    
    $name = getDepartmentNameFromSQL($deptId);
    if (!empty($name)) {
        $departmentCache[$deptId] = $name;
        return $name;
    }
    
    return $fallback ?: 'Department ' . $deptId;
}

function getDisplayDeptName($deptId, $passedName = '') {
    if (!empty($passedName)) {
        return $passedName;
    }
    return getDepartmentName($deptId);
}

// If department name is missing but ID is provided, fetch the name
if (empty($passedDeptName) && $passedDeptId > 0) {
    $passedDeptName = getDepartmentName($passedDeptId);
}

if (empty($passedDestName) && $passedDestId > 0) {
    $passedDestName = getDepartmentName($passedDestId);
}

// =========================================================================
// POST REQUEST HANDLERS
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $myConnLocal = new mysqli("localhost", "root", "", "milano");
    if ($myConnLocal->connect_error) {
        echo json_encode(["success" => false, "message" => "Database offline: " . $myConnLocal->connect_error]);
        exit;
    }

    $rawJson = file_get_contents("php://input");
    $payload = json_decode($rawJson, true);

    // ACTION A: INSERT
    if ($_GET['action'] === 'instant_insert') {
        $batchToken = 'BARCODE-STREAM-' . strtoupper(bin2hex(random_bytes(4)));
        $mode = $payload['mode'] ?? 'OUT';

        $deptId      = intval($payload['department_id'] ?? 0);
        $deptName    = $payload['department_name'] ?? getDepartmentName($deptId);
        $destId      = intval($payload['destination_id'] ?? 0);
        $destName    = $payload['destination_name'] ?? getDepartmentName($destId);
        $rCode       = trim($payload['random_code'] ?? '');
        $remark      = $payload['remark'] ?? '';
        $weight      = floatval($payload['weight'] ?? 0.0);
        $authorizedBy = $payload['authorized_by'] ?? '';

        if ($deptId === 0 || empty($deptName)) {
            echo json_encode(["success" => false, "message" => "Submission rejected: Missing Source Department."]);
            exit;
        }
        if ($destId === 0 || empty($destName)) {
            echo json_encode(["success" => false, "message" => "Submission rejected: Missing Destination Department."]);
            exit;
        }
        if (empty($rCode) || $rCode === '-') {
            echo json_encode(["success" => false, "message" => "Submission rejected: No barcode scanned."]);
            exit;
        }

        $stmt = $myConnLocal->prepare("INSERT INTO weight_transfers (batch_token, mode, department_id, department_name, destination_id, destination_name, random_code, customer_remark, weight, authorized_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisisssds", $batchToken, $mode, $deptId, $deptName, $destId, $destName, $rCode, $remark, $weight, $authorizedBy);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "db_id" => intval($myConnLocal->insert_id)]);
        } else {
            echo json_encode(["success" => false, "message" => "Database execute failed: " . $stmt->error]);
        }
        $stmt->close();
        $myConnLocal->close();
        exit;
    }

    // ACTION B: DELETE
    if ($_GET['action'] === 'instant_delete') {
        $dbId = intval($payload['db_id'] ?? 0);

        if ($dbId > 0) {
            $stmt = $myConnLocal->prepare("DELETE FROM weight_transfers WHERE id = ?");
            $stmt->bind_param("i", $dbId);
            
            if ($stmt->execute()) {
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "message" => "Could not delete row: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "message" => "Invalid ID provided."]);
        }
        $myConnLocal->close();
        exit;
    }

    // ACTION C: CHECK CARD
    if ($_GET['action'] === 'check_card') {
        $cardCode = trim($payload['card_code'] ?? '');
        
        if (empty($cardCode)) {
            echo json_encode(["status" => "error", "message" => "No card code provided"]);
            exit;
        }
        
        $card = checkCardFromMySQL($cardCode);
        
        if ($card) {
            $sDeptName = getDepartmentName($card['sDept']);
            $dDeptName = getDepartmentName($card['dDept']);
            
            echo json_encode([
                "status" => "authorized",
                "owner" => $card['OwnerName'] ?? 'Unknown',
                "sDept" => $card['sDept'],
                "dDept" => $card['dDept'],
                "sourceName" => $sDeptName,
                "destName" => $dDeptName,
                "cardCode" => $card['CardID'],
                "icNumber" => $card['IC_Number'] ?? ''
            ]);
        } else {
            echo json_encode([
                "status" => "unauthorized",
                "message" => "Card not found or inactive"
            ]);
        }
        exit;
    }

    // ACTION E: PARSE BARCODE – NOW RETURNS SUCCESS EVEN WITHOUT s_dept/d_dept
    if ($_GET['action'] === 'parse_barcode') {
        $barcodeData = trim($payload['barcode_data'] ?? '');
        
        if (empty($barcodeData)) {
            echo json_encode(["status" => "error", "message" => "No barcode data provided"]);
            exit;
        }
        
        $parsed = parseBarcodeJSON($barcodeData);
        
        if ($parsed && $parsed['random_code']) {
            $sDeptId = $parsed['s_dept'] ?? null;
            $dDeptId = $parsed['d_dept'] ?? null;
            
            $sDeptName = $sDeptId ? getDepartmentName($sDeptId) : null;
            $dDeptName = $dDeptId ? getDepartmentName($dDeptId) : null;
            
            echo json_encode([
                "status" => "success",
                "s_dept" => $sDeptId,
                "d_dept" => $dDeptId,
                "sourceName" => $sDeptName,
                "destName" => $dDeptName,
                "random_code" => $parsed['random_code'],
                "remark" => $parsed['remark']
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Could not parse barcode data",
                "raw" => substr($barcodeData, 0, 100)
            ]);
        }
        exit;
    }

    // ACTION D: SUBMIT BATCH
    if ($_GET['action'] === 'submit_batch_all') {
        $transfers = $payload['transfers'] ?? [];
        if (empty($transfers)) {
            echo json_encode(["success" => false, "message" => "No items available to submit."]);
            exit;
        }

        $logFile = __DIR__ . '/handover_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] SUBMIT BATCH ALL CALLED | DATA: " . json_encode($payload, JSON_UNESCAPED_UNICODE);
        file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND);

        $mode = $payload['mode'] ?? 'OUT';
        $apiUrl = $mode === 'IN' 
            ? "http://192.168.88.88:81/s2t1/save_handover.php?db=21kEuroStar"
            : "http://192.168.88.88:81/msession3/save_handover.php?db=21kEuroStar";
        
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);
        $apiResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        $responseLog = "[$timestamp] API RESPONSE | Error: " . ($curlError ?: 'None') . " | Response: " . $apiResponse;
        file_put_contents($logFile, $responseLog . PHP_EOL, FILE_APPEND);

        $delStmt = $myConnLocal->prepare("DELETE FROM weight_transfers");
        if ($delStmt) {
            $delStmt->execute();
            $delStmt->close();
        }

        echo json_encode(["success" => true, "message" => "All items submitted and queue cleared successfully!"]);
        exit;
    }
}

// =========================================================================
// FETCH EXISTING RECORDS ON REFRESH
// =========================================================================
$existingRecords = [];
$myConnLocal = new mysqli("localhost", "root", "", "milano");
if (!$myConnLocal->connect_error) {
    $res = $myConnLocal->query("SELECT id as db_id, mode, department_id, department_name, destination_id, destination_name, random_code, customer_remark as remark, weight, authorized_by FROM weight_transfers ORDER BY id ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['db_id'] = intval($row['db_id']);
            if (empty($row['department_name']) || $row['department_name'] == 'Unknown') {
                $row['department_name'] = getDepartmentName($row['department_id']);
            }
            if (empty($row['destination_name']) || $row['destination_name'] == 'Unknown') {
                $row['destination_name'] = getDepartmentName($row['destination_id']);
            }
            $existingRecords[] = $row;
        }
    }
    $myConnLocal->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MILANO SESSION MODE - BATCH TRANSFER</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@300;600&display=swap" rel="stylesheet">
    <style>
        body { background: #020617; color: white; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .orbitron { font-family: 'Orbitron', sans-serif; }
        .glass { background: rgba(255,255,255,0.03); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        
        .mode-btn {
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.03);
        }
        .mode-btn.active-out {
            border-color: #ef4444;
            background: rgba(239, 68, 68, 0.15);
            box-shadow: 0 0 30px rgba(239, 68, 68, 0.15);
        }
        .mode-btn.active-in {
            border-color: #fbbf24;
            background: rgba(251, 191, 36, 0.15);
            box-shadow: 0 0 30px rgba(251, 191, 36, 0.15);
        }
        .mode-btn:hover {
            transform: translateY(-2px);
        }
        
        .scan-mode-btn {
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.03);
        }
        .scan-mode-btn.active-card {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.15);
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.15);
        }
        .scan-mode-btn.active-qr {
            border-color: #8b5cf6;
            background: rgba(139, 92, 246, 0.15);
            box-shadow: 0 0 30px rgba(139, 92, 246, 0.15);
        }
        .scan-mode-btn:hover {
            transform: translateY(-2px);
        }
        
        .scale-connect-btn {
            transition: all 0.3s ease;
            border: 1px solid rgba(16, 185, 129, 0.2);
            background: rgba(16, 185, 129, 0.05);
        }
        .scale-connect-btn:hover {
            background: rgba(16, 185, 129, 0.15);
            border-color: rgba(16, 185, 129, 0.5);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.1);
        }
        .scale-disconnect-btn {
            transition: all 0.3s ease;
            border: 1px solid rgba(239, 68, 68, 0.2);
            background: rgba(239, 68, 68, 0.05);
        }
        .scale-disconnect-btn:hover {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.5);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.1);
        }
        .department-badge {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .department-badge-dest {
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .dept-name-source {
            color: #60a5fa;
        }
        .dept-name-dest {
            color: #fbbf24;
        }
        .submit-out {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        .submit-out:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }
        .submit-in {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #000;
        }
        .submit-in:hover {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        .authorized-badge {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #10b981;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.6rem;
        }
        .waiting-for-card {
            animation: pulse-yellow 1.5s ease-in-out infinite;
        }
        @keyframes pulse-yellow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .mode-indicator {
            font-size: 0.6rem;
            padding: 2px 8px;
            border-radius: 12px;
        }
        .mode-card {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        .mode-qr {
            background: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }
        .reset-btn {
            transition: all 0.3s ease;
            border: 1px solid rgba(239, 68, 68, 0.2);
            background: rgba(239, 68, 68, 0.05);
        }
        .reset-btn:hover {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.5);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.1);
        }
        /* Edit button for customer code */
        #editRemarkBtn {
            display: none;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 2px 8px;
            font-size: 10px;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            margin-left: 6px;
        }
        #editRemarkBtn:hover {
            background: rgba(251, 191, 36, 0.15);
            border-color: #fbbf24;
            color: #fbbf24;
            transform: scale(1.05);
        }
        .remark-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .remark-row .remark-value {
            flex: 1;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-between p-6">

<div class="w-full max-w-6xl flex justify-between items-center mb-6 px-4">
    <div class="flex items-center gap-4">
        <a href="index.php" class="orbitron tracking-[0.2em] text-xl font-bold text-red-600">MILANO GMS</a>
        <div class="orbitron border rounded-full text-sm font-semibold tracking-widest uppercase px-4 py-1 <?= $currentMode === 'OUT' ? 'text-red-500 border-red-500/30' : 'text-yellow-500 border-yellow-500/30' ?>">
            MODE: <?= $currentMode === 'OUT' ? 'OUTBOUND (ISSUE)' : 'INBOUND (RETURN)' ?>
        </div>
    </div>
    <div class="flex items-center gap-4">
        <div class="orbitron text-[11px] text-slate-500 uppercase tracking-wider">
            Scale: <span id="scale_display_ip" class="text-yellow-400">Not Connected</span>
        </div>
        <button id="scale_connect_btn" onclick="connectScale()" class="scale-connect-btn orbitron text-[9px] px-4 py-2 rounded-full uppercase tracking-widest text-emerald-400 hover:text-emerald-300 transition-all">
            🔌 CONNECT
        </button>
        <button id="scale_disconnect_btn" onclick="disconnectScale()" class="scale-disconnect-btn orbitron text-[9px] px-4 py-2 rounded-full uppercase tracking-widest text-red-400 hover:text-red-300 transition-all hidden">
            ⛔ DISCONNECT
        </button>
        <button onclick="resetSession()" class="reset-btn orbitron text-[9px] px-4 py-2 rounded-full uppercase tracking-widest text-red-400 hover:text-red-300 transition-all">
            🔄 RESET SESSION
        </button>
    </div>
</div>

<div class="w-full max-w-6xl grid grid-cols-12 gap-6 items-start my-auto">
    <div class="col-span-4 space-y-4">
        <!-- Scan Mode Switcher -->
        <div class="glass rounded-3xl p-4 border border-white/10">
            <p class="orbitron text-[9px] text-slate-500 tracking-[0.2em] mb-3 uppercase text-center">Scan Mode</p>
            <div class="grid grid-cols-2 gap-3">
                <button id="btnCardMode" onclick="setScanMode('card')" class="scan-mode-btn active-card rounded-2xl p-4 text-center transition-all">
                    <div class="text-2xl mb-1 text-blue-400">🪪</div>
                    <div class="orbitron text-[10px] font-bold text-blue-400">CARD MODE</div>
                    <div class="text-[8px] text-slate-600">Scan card once</div>
                </button>
                <button id="btnQRMode" onclick="setScanMode('qr')" class="scan-mode-btn rounded-2xl p-4 text-center transition-all">
                    <div class="text-2xl mb-1 text-purple-400">📱</div>
                    <div class="orbitron text-[10px] font-bold text-purple-400">QR MODE</div>
                    <div class="text-[8px] text-slate-600">QR for remark, card for path</div>
                </button>
            </div>
            <div class="mt-3 text-center">
                <span id="scanModeIndicator" class="mode-indicator mode-card">CARD MODE ACTIVE</span>
            </div>
        </div>
        
        <div class="glass rounded-3xl p-4 border border-white/10">
            <p class="orbitron text-[9px] text-slate-500 tracking-[0.2em] mb-3 uppercase text-center">Transfer Mode</p>
            <div class="grid grid-cols-2 gap-3">
                <a href="?mode=OUT<?= $passedDeptId ? '&department_id='.$passedDeptId.'&department_name='.urlencode($passedDeptName).'&destination_id='.$passedDestId.'&destination_name='.urlencode($passedDestName).'&card_details='.urlencode($passedCard) : '' ?>" 
                   class="mode-btn <?= $currentMode === 'OUT' ? 'active-out' : '' ?> rounded-2xl p-4 text-center transition-all">
                    <div class="text-2xl mb-1 <?= $currentMode === 'OUT' ? 'text-red-500' : 'text-slate-600' ?>">⬆</div>
                    <div class="orbitron text-[10px] font-bold <?= $currentMode === 'OUT' ? 'text-red-500' : 'text-slate-500' ?>">OUTBOUND</div>
                    <div class="text-[8px] text-slate-600">Issue Items</div>
                </a>
                <a href="?mode=IN<?= $passedDeptId ? '&department_id='.$passedDeptId.'&department_name='.urlencode($passedDeptName).'&destination_id='.$passedDestId.'&destination_name='.urlencode($passedDestName).'&card_details='.urlencode($passedCard) : '' ?>" 
                   class="mode-btn <?= $currentMode === 'IN' ? 'active-in' : '' ?> rounded-2xl p-4 text-center transition-all">
                    <div class="text-2xl mb-1 <?= $currentMode === 'IN' ? 'text-yellow-500' : 'text-slate-600' ?>">⬇</div>
                    <div class="orbitron text-[10px] font-bold <?= $currentMode === 'IN' ? 'text-yellow-500' : 'text-slate-500' ?>">INBOUND</div>
                    <div class="text-[8px] text-slate-600">Return Items</div>
                </a>
            </div>
        </div>
        
        <div class="glass rounded-3xl p-6 flex flex-col items-center justify-center text-center border border-white/5 relative">
            <p class="orbitron text-[10px] text-slate-500 tracking-[0.3em] mb-2 uppercase">Live Scale Stream</p>
            <div id="weight_display" class="orbitron text-5xl font-black text-white leading-none my-4">
                0.000<span class="text-xl text-slate-600 ml-1">g</span>
            </div>
            <div id="capture_status_badge" class="orbitron text-[10px] bg-red-500/10 px-3 py-1 rounded-full text-red-400 uppercase tracking-widest mb-4">
                Waiting for Barcode Scan
            </div>
            
            <div class="mt-2 w-full border-t border-white/10 pt-4">
                <p class="orbitron text-[10px] text-slate-500 tracking-widest uppercase mb-2">Active Code Context</p>
                <div class="glass rounded-xl p-3 text-left space-y-2">
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">Random Code:</div>
                        <div id="display_random_code" class="font-mono text-slate-500 break-all text-sm">-</div>
                    </div>
                    <div class="remark-row">
                        <div class="flex-1">
                            <div class="text-[10px] text-slate-400 uppercase tracking-wider">Customer Code:</div>
                            <div id="display_remark" class="font-bold text-slate-500 text-sm">-</div>
                        </div>
                        <button id="editRemarkBtn" onclick="editRemark()">✎ Edit</button>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">Authorized By:</div>
                        <div id="display_authorized" class="font-bold text-emerald-400 text-sm">-</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">Mode:</div>
                        <div id="display_mode" class="font-bold <?= $currentMode === 'OUT' ? 'text-red-500' : 'text-yellow-500' ?> text-sm">
                            <?= $currentMode === 'OUT' ? 'OUTBOUND' : 'INBOUND' ?>
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">Scan Mode:</div>
                        <div id="display_scan_mode" class="font-bold text-blue-400 text-sm">CARD MODE</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">Session Status:</div>
                        <div id="session_status" class="font-bold text-yellow-400 text-sm">INACTIVE</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">Path Source:</div>
                        <div id="path_source" class="font-bold text-cyan-400 text-sm">CARD</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-span-8 space-y-6">
        <div class="glass rounded-3xl p-6 border border-white/10 shadow-xl">
            <h3 class="orbitron text-xs text-slate-400 tracking-widest mb-4 uppercase font-bold border-b border-white/5 pb-2">Active Target Verification</h3>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block orbitron text-[10px] text-slate-500 tracking-wider uppercase mb-1">Source Department</label>
                    <div class="w-full bg-slate-900/50 border border-white/10 rounded-xl p-2.5 text-sm font-semibold flex items-center justify-between">
                        <span id="ui_dept_name_display" class="dept-name-source">
                            <?php 
                                $deptName = getDisplayDeptName($passedDeptId, $passedDeptName);
                                echo htmlspecialchars($deptName ?: 'Unknown');
                            ?>
                        </span>
                        <span class="department-badge">ID: <?php echo htmlspecialchars($passedDeptId); ?></span>
                    </div>
                    <input type="hidden" id="ui_dept_name" value="<?php echo htmlspecialchars(getDisplayDeptName($passedDeptId, $passedDeptName)); ?>">
                    <input type="hidden" id="ui_dept_id" value="<?php echo htmlspecialchars($passedDeptId); ?>">
                </div>
                <div>
                    <label class="block orbitron text-[10px] text-slate-500 tracking-wider uppercase mb-1">Destination Department</label>
                    <div class="w-full bg-slate-900/50 border border-white/10 rounded-xl p-2.5 text-sm font-semibold flex items-center justify-between">
                        <span id="ui_dest_name_display" class="dept-name-dest">
                            <?php 
                                $destName = getDisplayDeptName($passedDestId, $passedDestName);
                                echo htmlspecialchars($destName ?: 'Unknown');
                            ?>
                        </span>
                        <span class="department-badge-dest">ID: <?php echo htmlspecialchars($passedDestId); ?></span>
                    </div>
                    <input type="hidden" id="ui_dest_name" value="<?php echo htmlspecialchars(getDisplayDeptName($passedDestId, $passedDestName)); ?>">
                    <input type="hidden" id="ui_dest_id" value="<?php echo htmlspecialchars($passedDestId); ?>">
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 mb-4">
                <div>
                    <label class="block orbitron text-[10px] text-slate-500 tracking-wider uppercase mb-1">Card Assignment Reference</label>
                    <input type="text" id="ui_card_details" class="w-full bg-slate-900/50 border border-white/10 rounded-xl p-2.5 text-sm font-mono text-cyan-400" readonly value="<?php echo htmlspecialchars($passedCard); ?>">
                </div>
            </div>
            <div class="mb-5">
                <label class="block orbitron text-[10px] text-slate-500 tracking-wider uppercase mb-1">Session Override Note / Remarks</label>
                <input type="text" id="ui_custom_remark" placeholder="Enter custom session validation details here..." class="w-full bg-slate-950 border border-white/10 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-emerald-500 transition-colors">
            </div>
            <div class="text-center">
                <span id="session_info" class="text-[10px] text-slate-500 tracking-wider">🔒 Session active until manually reset or batch submitted</span>
            </div>
        </div>

        <div class="glass rounded-[2rem] p-6 min-h-[280px] flex flex-col justify-between border border-white/10 shadow-2xl relative">
            <div class="w-full">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="orbitron text-xs text-slate-400 tracking-widest uppercase font-bold border-b border-white/5 pb-2">Real-Time Database Records Table</h3>
                    <span class="text-[10px] text-slate-500">Mode: <span class="<?= $currentMode === 'OUT' ? 'text-red-500' : 'text-yellow-500' ?> font-bold"><?= $currentMode === 'OUT' ? 'OUT' : 'IN' ?></span></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-white/10 orbitron text-slate-400 text-xs tracking-wider">
                                <th class="py-3 px-2 w-16">Item #</th>
                                <th class="py-3 px-4">Route</th>
                                <th class="py-3 px-4">Code</th>
                                <th class="py-3 px-4">Remark</th>
                                <th class="py-3 px-4 text-right">Weight</th>
                                <th class="py-3 px-4 text-center">Authorized By</th>
                                <th class="py-3 px-2 text-center w-12">Action</th>
                            </tr>
                        </thead>
                        <tbody id="table_row_container" class="divide-y divide-white/5 font-medium"></tbody>
                    </table>
                </div>
                <div id="empty_notice" class="text-center py-16 text-slate-500 orbitron text-xs tracking-wider uppercase">
                    [ Scan a barcode to begin capturing changes... ]
                </div>
            </div>

            <div class="mt-8 pt-4 border-t border-white/10 flex justify-end">
                <button onclick="executeBackEnd()" class="w-full py-4 px-6 rounded-2xl <?= $currentMode === 'OUT' ? 'submit-out' : 'submit-in' ?> text-white orbitron font-bold text-sm tracking-widest shadow-lg transition-all flex items-center justify-center gap-3 <?= $currentMode === 'OUT' ? 'shadow-red-950/50' : 'shadow-yellow-950/50' ?>">
                    <span>CONFIRM & SUBMIT ALL ITEMS (1-SHOT)</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<input type="text" id="barcodeInput" autocomplete="off" autofocus style="position:absolute; left:-9999px; opacity:0;">

<script>
// =========================================================================
// WEB SERIAL API SCALE CONNECTION - Shimadzu TX3202L
// =========================================================================

const SCALE_CONFIG = {
    baudRate: 1200,
    dataBits: 8,
    parity: 'none',
    stopBits: 1,
    flowControl: 'hardware'
};

let scalePort = null;
let scaleReader = null;
let scaleKeepReading = false;
let scaleLineBuffer = '';

// Current weight state
let currentScaleWeight = 0.000;
let isConnecting = false;
let currentMode = '<?= $currentMode ?>';

// ============================================================
// SESSION STATE VARIABLES
// ============================================================
let pendingQR = null;           // QR data waiting for card authorization
let pendingQRBarcode = null;    // Raw barcode string
let authorizedUser = null;      // Currently authorized user for the session
let scanMode = 'card';          // 'card' or 'qr'
let isSessionActive = false;    // Track if a session is active

function setScaleStatus(text, connected) {
    const el = document.getElementById('scale_display_ip');
    if (el) {
        el.innerText = text;
        el.className = connected ? 'text-emerald-400' : 'text-yellow-400';
    }
    const connectBtn = document.getElementById('scale_connect_btn');
    const disconnectBtn = document.getElementById('scale_disconnect_btn');
    
    if (connectBtn) {
        connectBtn.classList.toggle('hidden', !!connected);
    }
    if (disconnectBtn) {
        disconnectBtn.classList.toggle('hidden', !connected);
    }
}

function parseScaleLine(line) {
    const cleaned = line.trim();
    if (!cleaned) return null;
    const match = cleaned.match(/([+-]?\s*\d+(?:\.\d+)?)\s*([a-zA-Z]{0,3})\s*$/);
    if (!match) return null;
    const weight = parseFloat(match[1].replace(/\s+/g, ''));
    return isNaN(weight) ? null : weight;
}

// =========================================================================
// FORCE DISCONNECT - Close any existing port connection
// =========================================================================

async function forceDisconnectScale() {
    console.log('🔄 Force disconnecting scale...');
    
    let disconnected = false;
    isConnecting = false;
    
    try {
        scaleKeepReading = false;
        
        if (scaleReader) {
            try {
                await scaleReader.cancel();
                scaleReader.releaseLock();
                console.log('✅ Reader released');
            } catch (e) {
                console.warn('Reader release error:', e);
            }
            scaleReader = null;
        }
        
        if (scalePort) {
            try {
                await scalePort.close();
                console.log('✅ Port closed successfully');
                disconnected = true;
            } catch (e) {
                console.warn('Port close error:', e);
                if (e.message && (e.message.includes('already closed') || e.message.includes('not open'))) {
                    disconnected = true;
                }
            }
            scalePort = null;
        } else {
            disconnected = true;
        }
        
        scaleLineBuffer = '';
        setScaleStatus('Disconnected', false);
        
        console.log('✅ Scale force disconnected:', disconnected);
        return disconnected;
    } catch (err) {
        console.error('Force disconnect error:', err);
        setScaleStatus('Disconnected', false);
        return false;
    }
}

// =========================================================================
// OPEN SCALE PORT WITH RETRY
// =========================================================================

async function openScalePortWithRetry(port, maxRetries = 3) {
    let lastError = null;
    
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            console.log(`🔄 Opening port attempt ${attempt}/${maxRetries}...`);
            
            await port.open({
                baudRate: SCALE_CONFIG.baudRate,
                dataBits: SCALE_CONFIG.dataBits,
                parity: SCALE_CONFIG.parity,
                stopBits: SCALE_CONFIG.stopBits,
                flowControl: SCALE_CONFIG.flowControl
            });
            
            console.log('✅ Port opened successfully!');
            return true;
        } catch (err) {
            console.warn(`Attempt ${attempt} failed:`, err.message);
            lastError = err;
            
            if (err.message && (err.message.includes('already open') || err.message.includes('Failed to open'))) {
                console.log('🔄 Port may be locked, attempting to force close...');
                try {
                    await port.close();
                    await new Promise(r => setTimeout(r, 500));
                } catch (closeErr) {
                    console.warn('Close attempt failed:', closeErr);
                }
            }
            
            if (attempt < maxRetries) {
                await new Promise(r => setTimeout(r, 800));
            }
        }
    }
    
    throw lastError || new Error('Failed to open port after multiple attempts');
}

// =========================================================================
// CONNECT SCALE
// =========================================================================

async function connectScale() {
    if (isConnecting) {
        console.log('⏳ Already connecting...');
        return;
    }
    
    if (!('serial' in navigator)) {
        setScaleStatus('Unsupported — use Chrome/Edge over https:// or localhost', false);
        Swal.fire({
            icon: 'error',
            title: 'SCALE CONNECTION UNAVAILABLE',
            html: `Web Serial isn't available in this browser context.<br><br>
                   This page must be opened in <b>Chrome</b> or <b>Edge</b>,
                   and served over <b>https://</b> or <b>http://localhost</b>`,
            background: '#020617', color: '#fff'
        });
        return;
    }

    isConnecting = true;
    setScaleStatus('Connecting...', false);

    try {
        await forceDisconnectScale();
        await new Promise(r => setTimeout(r, 500));
        
        scalePort = await navigator.serial.requestPort();
        await openScalePortWithRetry(scalePort);
        
        try {
            await scalePort.setSignals({ dataTerminalReady: true, requestToSend: true });
        } catch (sigErr) {
            console.warn('setSignals not supported/failed:', sigErr);
        }

        setScaleStatus('Connected', true);
        scaleKeepReading = true;
        isConnecting = false;
        scaleReadLoop();
        
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Scale Connected',
            text: 'Ready to capture weight readings',
            showConfirmButton: false,
            timer: 2000,
            background: '#020617',
            color: '#fff'
        });
    } catch (err) {
        console.error('Connection error:', err);
        isConnecting = false;
        
        if (err.message && err.message.includes('cancelled')) {
            setScaleStatus('Connection cancelled', false);
            return;
        }
        
        setScaleStatus('Connection failed', false);
        
        let errorMessage = err.message || 'Could not connect to scale';
        if (errorMessage.includes('Failed to open serial port')) {
            errorMessage = 'The COM port is already in use by another application or tab.\n\nPlease close other applications using the scale (like Python scripts, terminal programs, or other browser tabs) and try again.';
        }
        
        Swal.fire({
            icon: 'error',
            title: 'CONNECTION FAILED',
            text: errorMessage,
            background: '#020617',
            color: '#fff',
            confirmButtonColor: '#ef4444'
        });
    }
}

// =========================================================================
// DISCONNECT SCALE
// =========================================================================

async function disconnectScale() {
    console.log('🔄 Manual disconnect requested...');
    isConnecting = false;
    const result = await forceDisconnectScale();
    
    if (result) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: 'Scale Disconnected',
            text: 'You can now reconnect if needed',
            showConfirmButton: false,
            timer: 2000,
            background: '#020617',
            color: '#fff'
        });
    }
}

// =========================================================================
// SCALE READ LOOP
// =========================================================================

async function scaleReadLoop() {
    while (scaleKeepReading) {
        try {
            const textDecoder = new TextDecoderStream();
            scalePort.readable.pipeTo(textDecoder.writable).catch(() => {});
            scaleReader = textDecoder.readable.getReader();

            try {
                while (scaleKeepReading) {
                    const { value, done } = await scaleReader.read();
                    if (done) {
                        scaleKeepReading = false;
                        break;
                    }
                    if (value) {
                        scaleLineBuffer += value;
                        let idx;
                        while ((idx = scaleLineBuffer.search(/[\r\n]/)) >= 0) {
                            const line = scaleLineBuffer.slice(0, idx);
                            scaleLineBuffer = scaleLineBuffer.slice(idx + 1);
                            if (line.trim().length > 0) {
                                const w = parseScaleLine(line);
                                if (w !== null) onScaleWeight(w);
                            }
                        }
                    }
                }
            } catch (err) {
                console.warn('Scale read error (recovering):', err);
                const name = err && err.name ? err.name : '';
                if (name === 'BreakError' || name === 'FramingError' || name === 'ParityError') {
                    setScaleStatus(`Connected — line noise (${name})`, true);
                } else {
                    setScaleStatus('Connected — read error', true);
                }
            } finally {
                try {
                    if (scaleReader) scaleReader.releaseLock();
                } catch (e) {}
            }

            if (scaleKeepReading) {
                await new Promise(r => setTimeout(r, 150));
            }
        } catch (err) {
            console.warn('Scale loop error:', err);
            if (scaleKeepReading) {
                await new Promise(r => setTimeout(r, 500));
            }
        }
    }
}

// =========================================================================
// AUTO-CONNECT
// =========================================================================

async function tryAutoConnectScale() {
    if (!('serial' in navigator)) {
        setScaleStatus('Web Serial unsupported (use Chrome/Edge)', false);
        return;
    }

    if (isConnecting) {
        console.log('⏳ Already connecting, skipping auto-connect');
        return;
    }

    try {
        await forceDisconnectScale();
        await new Promise(r => setTimeout(r, 300));
        
        const ports = await navigator.serial.getPorts();
        console.log('🔍 Available ports:', ports.length);
        
        if (ports.length > 0) {
            for (const port of ports) {
                try {
                    const info = await port.getInfo();
                    console.log('📌 Found saved port:', info);
                    
                    scalePort = port;
                    await openScalePortWithRetry(port);
                    
                    try {
                        await scalePort.setSignals({ dataTerminalReady: true, requestToSend: true });
                    } catch (sigErr) {
                        console.warn('setSignals not supported/failed:', sigErr);
                    }
                    
                    setScaleStatus('Connected', true);
                    scaleKeepReading = true;
                    scaleReadLoop();
                    console.log('✅ Scale auto-connected successfully!');
                    return true;
                } catch (err) {
                    console.warn('Failed to open saved port:', err.message);
                    if (err.message && err.message.includes('already open')) {
                        console.warn('⚠️ Port is already open, attempting recovery...');
                        await forceDisconnectScale();
                        await new Promise(r => setTimeout(r, 500));
                        try {
                            scalePort = port;
                            await openScalePortWithRetry(port);
                            setScaleStatus('Connected', true);
                            scaleKeepReading = true;
                            scaleReadLoop();
                            console.log('✅ Port recovered and connected!');
                            return true;
                        } catch (recoverErr) {
                            console.warn('Recovery failed:', recoverErr);
                        }
                    }
                }
            }
            
            setScaleStatus('Click CONNECT', false);
            return false;
        } else {
            console.log('ℹ️ No saved ports found');
            setScaleStatus('Click CONNECT', false);
            return false;
        }
    } catch (err) {
        console.warn('Auto-connect failed:', err);
        setScaleStatus('Click CONNECT', false);
        return false;
    }
}

// =========================================================================
// SERIAL EVENT LISTENERS
// =========================================================================

if (navigator.serial) {
    navigator.serial.addEventListener('disconnect', () => {
        scaleKeepReading = false;
        setScaleStatus('Disconnected', false);
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'warning',
            title: 'Scale Disconnected',
            text: 'The scale has been disconnected. Click CONNECT to reconnect.',
            showConfirmButton: false,
            timer: 3000,
            background: '#020617',
            color: '#fff'
        });
    });

    navigator.serial.addEventListener('connect', async () => {
        if (!scaleKeepReading) {
            await new Promise(r => setTimeout(r, 1000));
            await tryAutoConnectScale();
        }
    });
}

// =========================================================================
// WEIGHT HANDLER
// =========================================================================

function onScaleWeight(w) {
    console.log("⚖️ Scale weight received:", w);
    console.log("📊 Pending QR Data:", window._pendingQRData);
    console.log("👤 Authorized User:", authorizedUser);
    console.log("📋 Pending QR:", pendingQR);
    console.log("📌 Scan Mode:", scanMode);
    
    if (w <= 0.001) {
        document.getElementById('weight_display').innerHTML = `WAITING...<span class="text-xl text-slate-600 ml-1">g</span>`;
        return;
    }

    currentScaleWeight = w;
    document.getElementById('weight_display').innerHTML = `${w.toFixed(3)}<span class="text-xl text-slate-600 ml-1">g</span>`;

    let hasPendingData = false;
    let pendingData = null;
    
    if (scanMode === 'qr' && pendingQR && authorizedUser && window._pendingQRData) {
        hasPendingData = true;
        pendingData = window._pendingQRData;
        console.log("✅ QR Mode - Found pending data");
    }
    else if (scanMode === 'card' && authorizedUser && window._pendingQRData) {
        hasPendingData = true;
        pendingData = window._pendingQRData;
        console.log("✅ Card Mode - Found pending data");
    }
    
    if (hasPendingData && pendingData) {
        console.log("✅ We have pending data - logging weight:", pendingData);
        if (w !== lastLoggedWeight && w > 0.001) {
            lastLoggedWeight = w;
            logWeightToDatabase(w);
        } else if (w <= 0.001) {
            lastLoggedWeight = -1.0;
        }
    } else {
        console.log("❌ No pending data found:", { 
            scanMode, 
            hasPendingQR: !!pendingQR, 
            hasAuthorizedUser: !!authorizedUser, 
            hasPendingData: !!window._pendingQRData 
        });
    }
}

// =========================================================================
// SET SCAN MODE
// =========================================================================
function setScanMode(mode) {
    console.log("🔄 Switching scan mode to:", mode);
    
    if (runningUiQueue.length > 0) {
        Swal.fire({
            title: 'Clear Queue?',
            text: `Switching to ${mode.toUpperCase()} mode will clear the current queue (${runningUiQueue.length} items).`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Clear & Switch',
            cancelButtonText: 'Cancel',
            background: '#020617',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                clearQueueAndSwitch(mode);
            }
        });
    } else {
        clearQueueAndSwitch(mode);
    }
}

function clearQueueAndSwitch(mode) {
    scanMode = mode;
    
    runningUiQueue = [];
    pendingQR = null;
    pendingQRBarcode = null;
    authorizedUser = null;
    window._pendingQRData = null;
    lastLoggedWeight = -1.0;
    isSessionActive = false;
    
    // Hide edit button
    document.getElementById('editRemarkBtn').style.display = 'none';
    
    safeUpdateElement('ui_dept_id', '');
    safeUpdateElement('ui_dept_name', '');
    safeUpdateElement('ui_dept_name_display', 'Unknown');
    safeUpdateElement('ui_dest_id', '');
    safeUpdateElement('ui_dest_name', '');
    safeUpdateElement('ui_dest_name_display', 'Unknown');
    safeUpdateElement('display_random_code', '-', 'font-mono text-slate-500 break-all text-sm');
    safeUpdateElement('display_remark', '-', 'font-bold text-slate-500 text-sm');
    safeUpdateElement('display_authorized', '-', 'font-bold text-emerald-400 text-sm');
    safeUpdateElement('source_name', '---');
    safeUpdateElement('source_status', 'Wait for scan...');
    safeUpdateElement('session_status', 'INACTIVE', 'font-bold text-yellow-400 text-sm');
    safeUpdateElement('path_source', 'CARD', 'font-bold text-cyan-400 text-sm');
    
    const userCardSource = document.getElementById('user_card_source');
    if (userCardSource) userCardSource.classList.remove('active', 'verified');
    
    renderTableInterface();
    
    const cardBtn = document.getElementById('btnCardMode');
    const qrBtn = document.getElementById('btnQRMode');
    const indicator = document.getElementById('scanModeIndicator');
    const displayScanMode = document.getElementById('display_scan_mode');
    
    if (mode === 'card') {
        cardBtn.classList.add('active-card');
        cardBtn.classList.remove('active-qr');
        qrBtn.classList.remove('active-card', 'active-qr');
        indicator.className = 'mode-indicator mode-card';
        indicator.innerText = 'CARD MODE ACTIVE';
        if (displayScanMode) {
            displayScanMode.innerText = 'CARD MODE';
            displayScanMode.className = 'font-bold text-blue-400 text-sm';
        }
        safeUpdateElement('path_source', 'CARD', 'font-bold text-cyan-400 text-sm');
        safeUpdateElement('scan_prompt', 'CARD MODE: Scan a card', 'orbitron text-sm uppercase tracking-widest text-blue-400');
        safeUpdateElement('capture_status_badge', 'CARD MODE - Scan card', 'orbitron text-[10px] bg-blue-500/10 px-3 py-1 rounded-full text-blue-400 uppercase tracking-widest mb-4');
    } else {
        qrBtn.classList.add('active-qr');
        qrBtn.classList.remove('active-card');
        cardBtn.classList.remove('active-card', 'active-qr');
        indicator.className = 'mode-indicator mode-qr';
        indicator.innerText = 'QR MODE ACTIVE';
        if (displayScanMode) {
            displayScanMode.innerText = 'QR MODE';
            displayScanMode.className = 'font-bold text-purple-400 text-sm';
        }
        safeUpdateElement('path_source', 'CARD (QR provides remark)', 'font-bold text-cyan-400 text-sm');
        safeUpdateElement('scan_prompt', 'QR MODE: Scan QR then card', 'orbitron text-sm uppercase tracking-widest text-purple-400');
        safeUpdateElement('capture_status_badge', 'QR MODE - Scan QR first', 'orbitron text-[10px] bg-purple-500/10 px-3 py-1 rounded-full text-purple-400 uppercase tracking-widest mb-4');
    }
    
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'info',
        title: `${mode.toUpperCase()} MODE ACTIVATED`,
        text: mode === 'qr' ? 'QR provides remark, Card provides path' : 'Card provides both path and remark',
        showConfirmButton: false,
        timer: 2500,
        background: '#020617',
        color: '#fff'
    });
}

// =========================================================================
// RESET SESSION
// =========================================================================
function resetSession() {
    if (runningUiQueue.length > 0) {
        Swal.fire({
            title: 'Reset Session?',
            text: `This will clear the current authorization but keep the queue (${runningUiQueue.length} items).`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Reset',
            cancelButtonText: 'Cancel',
            background: '#020617',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                performReset();
            }
        });
    } else {
        performReset();
    }
}

function performReset() {
    pendingQR = null;
    pendingQRBarcode = null;
    authorizedUser = null;
    window._pendingQRData = null;
    isSessionActive = false;
    lastLoggedWeight = -1.0;
    
    // Hide edit button
    document.getElementById('editRemarkBtn').style.display = 'none';
    
    safeUpdateElement('display_random_code', '-', 'font-mono text-slate-500 break-all text-sm');
    safeUpdateElement('display_remark', '-', 'font-bold text-slate-500 text-sm');
    safeUpdateElement('display_authorized', '-', 'font-bold text-emerald-400 text-sm');
    safeUpdateElement('session_status', 'INACTIVE', 'font-bold text-yellow-400 text-sm');
    safeUpdateElement('ui_dept_name_display', 'Unknown');
    safeUpdateElement('ui_dest_name_display', 'Unknown');
    
    if (scanMode === 'card') {
        safeUpdateElement('scan_prompt', 'CARD MODE: Scan a card', 'orbitron text-sm uppercase tracking-widest text-blue-400');
        safeUpdateElement('capture_status_badge', 'CARD MODE - Scan card', 'orbitron text-[10px] bg-blue-500/10 px-3 py-1 rounded-full text-blue-400 uppercase tracking-widest mb-4');
    } else {
        safeUpdateElement('scan_prompt', 'QR MODE: Scan QR then card', 'orbitron text-sm uppercase tracking-widest text-purple-400');
        safeUpdateElement('capture_status_badge', 'QR MODE - Scan QR first', 'orbitron text-[10px] bg-purple-500/10 px-3 py-1 rounded-full text-purple-400 uppercase tracking-widest mb-4');
    }
    
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'info',
        title: 'SESSION RESET',
        text: 'Authorization cleared. Scan again to continue.',
        showConfirmButton: false,
        timer: 2000,
        background: '#020617',
        color: '#fff'
    });
}

// =========================================================================
// CHECK CARD FROM MYSQL VIA AJAX
// =========================================================================
async function checkCard(cardCode) {
    try {
        const response = await fetch(`${TARGET_URL}?action=check_card`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ card_code: cardCode })
        });
        const result = await response.json();
        return result;
    } catch (err) {
        console.error('Card check failed:', err);
        return null;
    }
}

// =========================================================================
// PARSE BARCODE JSON VIA AJAX
// =========================================================================
async function parseBarcodeData(barcodeData) {
    try {
        const response = await fetch(`${TARGET_URL}?action=parse_barcode`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ barcode_data: barcodeData })
        });
        const result = await response.json();
        return result;
    } catch (err) {
        console.error('Barcode parse failed:', err);
        return null;
    }
}

// =========================================================================
// APPLICATION LOGIC
// =========================================================================

const TARGET_URL = window.location.href.split('?')[0];

let runningUiQueue = <?php echo json_encode($existingRecords); ?>;
let lastLoggedWeight = -1.0;
let scannedRandomCode = '';
let scannedRemark = '';

console.log("⚙️ [SYSTEM BOOT] Session Mode loaded.");
renderTableInterface();

document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 Session Mode loaded, connecting to scale...');
    setScaleStatus('Connecting...', false);
    setTimeout(() => {
        tryAutoConnectScale();
    }, 500);
    renderTableInterface();
});

// =========================================================================
// HELPER FUNCTION TO SAFELY UPDATE DOM ELEMENTS
// =========================================================================
function safeUpdateElement(id, value, className = null) {
    const el = document.getElementById(id);
    if (el) {
        if (value !== undefined) el.innerText = value;
        if (className) el.className = className;
        return true;
    }
    return false;
}

// =========================================================================
// EDIT REMARK (CUSTOMER CODE) - Modal version
// =========================================================================
function editRemark() {
    const currentValue = document.getElementById('display_remark').textContent;
    Swal.fire({
        title: 'Edit Customer Code',
        html: `
            <label for="remark-input" class="text-slate-300 text-sm block text-left mb-1">Enter new customer code / remark:</label>
            <input id="remark-input" class="swal2-input" type="text" maxlength="50" value="${currentValue !== '-' ? currentValue : ''}" placeholder="Type new customer code...">
        `,
        background: '#020617',
        color: '#fff',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#ef4444',
        showCancelButton: true,
        confirmButtonText: '✓ Save',
        cancelButtonText: '✕ Cancel',
        didOpen: () => {
            const input = document.getElementById('remark-input');
            if (input) {
                setTimeout(() => input.focus(), 100);
            }
        },
        preConfirm: () => {
            const val = document.getElementById('remark-input').value.trim();
            if (val === '') {
                Swal.showValidationMessage('Customer code cannot be empty');
                return false;
            }
            return val;
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const newValue = result.value;
            // Update UI
            document.getElementById('display_remark').textContent = newValue;
            // Update pending data
            if (window._pendingQRData) {
                // Update the remark in pending data, preserving the authorized part
                const authPart = window._pendingQRData.authorizedBy ? ` | Authorized: ${window._pendingQRData.authorizedBy}` : '';
                window._pendingQRData.remark = `${newValue}${authPart}`;
                // Also update scannedRemark if it exists
                scannedRemark = window._pendingQRData.remark;
                console.log("📝 Customer code updated to:", newValue);
            }
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Customer Code Updated',
                text: `New code: ${newValue}`,
                showConfirmButton: false,
                timer: 2000,
                background: '#020617',
                color: '#fff'
            });
        }
    });
}

// =========================================================================
// BARCODE SCANNER HANDLER
// =========================================================================

const barcodeInput = document.getElementById('barcodeInput');
barcodeInput.focus();
document.addEventListener('click', (e) => { 
    // Ignore clicks if a SweetAlert modal is open or an input field is clicked
    if (Swal.isVisible() || e.target.tagName === 'INPUT') return;
    barcodeInput.focus(); 
});

barcodeInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        const barcode = barcodeInput.value.trim();
        if (!barcode) return;
        
        console.log("📋 SCANNED BARCODE:", barcode);
        console.log("📌 Current Scan Mode:", scanMode);

        // ============================================================
        // CARD MODE: Scan card directly
        // ============================================================
        if (scanMode === 'card') {
            console.log("🔍 Card Mode - checking as user card...");
            checkCard(barcode).then(cardResult => {
                if (cardResult && cardResult.status === 'authorized') {
                    console.log("✅ Card authorized:", cardResult);
                    
                    pendingQR = null;
                    authorizedUser = null;
                    window._pendingQRData = null;
                    
                    authorizedUser = cardResult;
                    processCardDirect(cardResult);
                    
                    barcodeInput.value = '';
                    return;
                }
                
                const sessionCards = ['0005547220', '0005508764', '0005525546', '0005542541', '0005508437', '0005523764'];
                
                if (sessionCards.includes(barcode)) {
                    Swal.fire({
                        icon: 'info',
                        title: '🔄 SESSION CARD',
                        text: 'Session mode activated. Switch to QR mode for QR scanning.',
                        background: '#020617',
                        color: '#fff',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    barcodeInput.value = '';
                    return;
                }
                
                console.log("❌ Card not found or inactive");
                Swal.fire({
                    icon: 'warning',
                    title: 'CARD NOT RECOGNIZED',
                    text: 'This card is not registered or is inactive.',
                    background: '#020617',
                    color: '#fff'
                });
                barcodeInput.value = '';
            }).catch(err => {
                console.error('❌ Card check error:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'SYSTEM ERROR',
                    text: 'Could not verify card. Please check the system.',
                    background: '#020617',
                    color: '#fff'
                });
                barcodeInput.value = '';
            });
            return;
        }

        // ============================================================
        // QR MODE: QR provides remark, Card provides path
        // ============================================================
        if (scanMode === 'qr') {
            if (barcode.startsWith('}@sdept@') || barcode.includes('@randomcode@') || barcode.includes('{"s_dept"')) {
                console.log('🔍 QR Code detected - requiring card authorization');
                
                parseBarcodeData(barcode).then(parseResult => {
                    if (parseResult && parseResult.status === 'success') {
                        pendingQR = parseResult;
                        pendingQRBarcode = barcode;
                        authorizedUser = null;
                        
                        safeUpdateElement('scan_prompt', '⚠️ SCAN USER CARD TO AUTHORIZE', 'orbitron text-sm animate-pulse uppercase tracking-widest text-yellow-400');
                        safeUpdateElement('capture_status_badge', 'AWAITING CARD AUTHORIZATION', 'orbitron text-[10px] bg-yellow-500/20 px-3 py-1 rounded-full text-yellow-400 uppercase tracking-widest animate-pulse mb-4');
                        safeUpdateElement('display_random_code', parseResult.random_code || barcode, 'font-mono text-yellow-400 break-all text-sm');
                        safeUpdateElement('display_remark', parseResult.remark || 'Awaiting Authorization...', 'font-bold text-yellow-400 text-sm');
                        safeUpdateElement('display_authorized', '⚠️ PENDING', 'font-bold text-yellow-400 text-sm');
                        safeUpdateElement('path_source', 'QR provides remark, CARD provides path', 'font-bold text-cyan-400 text-sm');
                        
                        safeUpdateElement('ui_dept_name_display', 'Waiting for Card...');
                        safeUpdateElement('ui_dest_name_display', 'Waiting for Card...');
                        
                        barcodeInput.value = '';
                        
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'info',
                            title: 'QR SCANNED',
                            text: 'Now scan your user card to authorize',
                            showConfirmButton: false,
                            timer: 2000,
                            background: '#020617',
                            color: '#fff'
                        });
                    } else {
                        console.log("❌ QR parse failed:", parseResult);
                        Swal.fire({
                            icon: 'error',
                            title: 'INVALID QR',
                            text: parseResult?.message || 'Could not parse this QR code.',
                            background: '#020617',
                            color: '#fff'
                        });
                        barcodeInput.value = '';
                    }
                }).catch(err => {
                    console.error("❌ QR parse error:", err);
                    Swal.fire({
                        icon: 'error',
                        title: 'PARSE ERROR',
                        text: err.message || 'Could not parse QR code.',
                        background: '#020617',
                        color: '#fff'
                    });
                    barcodeInput.value = '';
                });
                return;
            }

            // ============================================================
            // QR MODE: User Card (provides the path)
            // ============================================================
            console.log("🔍 QR Mode - checking as user card...");
            checkCard(barcode).then(cardResult => {
                if (cardResult && cardResult.status === 'authorized') {
                    console.log("✅ Card authorized:", cardResult);
                    
                    if (pendingQR) {
                        authorizedUser = cardResult;
                        // Process QR - CARD provides path, QR provides remark
                        processQRWithUser(pendingQR, cardResult);
                        pendingQRBarcode = null;
                        barcodeInput.value = '';
                        return;
                    }
                    
                    if (!pendingQR) {
                        authorizedUser = cardResult;
                        safeUpdateElement('source_name', cardResult.owner);
                        safeUpdateElement('source_status', `Authorized: ${cardResult.owner}`);
                        safeUpdateElement('display_authorized', cardResult.owner, 'font-bold text-emerald-400 text-sm');
                        safeUpdateElement('scan_prompt', 'SCAN QR TO CONTINUE', 'orbitron text-sm uppercase tracking-widest text-purple-400');
                        
                        const userCardSource = document.getElementById('user_card_source');
                        if (userCardSource) userCardSource.classList.add('active', 'verified');
                        
                        barcodeInput.value = '';
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'USER AUTHORIZED',
                            text: `${cardResult.owner} is ready. Scan a QR.`,
                            showConfirmButton: false,
                            timer: 2000,
                            background: '#020617',
                            color: '#fff'
                        });
                        return;
                    }
                }
                
                const sessionCards = ['0005547220', '0005508764', '0005525546', '0005542541', '0005508437', '0005523764'];
                
                if (sessionCards.includes(barcode)) {
                    if (pendingQR) {
                        pendingQR = null;
                        pendingQRBarcode = null;
                        authorizedUser = null;
                        
                        Swal.fire({
                            icon: 'info',
                            title: '🔄 PENDING QR CANCELLED',
                            text: 'Session card scanned - pending QR cancelled.',
                            background: '#020617',
                            color: '#fff',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        
                        safeUpdateElement('scan_prompt', 'QR MODE: Scan QR then card', 'orbitron text-sm uppercase tracking-widest text-purple-400');
                        safeUpdateElement('capture_status_badge', 'QR MODE - Scan QR first', 'orbitron text-[10px] bg-purple-500/10 px-3 py-1 rounded-full text-purple-400 uppercase tracking-widest mb-4');
                        safeUpdateElement('display_authorized', '-', 'font-bold text-emerald-400 text-sm');
                        safeUpdateElement('display_random_code', '-', 'font-mono text-slate-500 break-all text-sm');
                        safeUpdateElement('display_remark', '-', 'font-bold text-slate-500 text-sm');
                        
                        barcodeInput.value = '';
                        return;
                    }
                    
                    Swal.fire({
                        icon: 'info',
                        title: '🔄 SESSION CARD',
                        text: 'Session mode activated. Scan a QR then a user card.',
                        background: '#020617',
                        color: '#fff',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    barcodeInput.value = '';
                    return;
                }
                
                console.log("❌ Card not found or inactive");
                Swal.fire({
                    icon: 'warning',
                    title: 'CARD NOT RECOGNIZED',
                    text: 'This card is not registered or is inactive.',
                    background: '#020617',
                    color: '#fff'
                });
                barcodeInput.value = '';
            }).catch(err => {
                console.error('❌ Card check error:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'SYSTEM ERROR',
                    text: 'Could not verify card. Please check the system.',
                    background: '#020617',
                    color: '#fff'
                });
                barcodeInput.value = '';
            });
            return;
        }
    }
});

// ============================================================
// PROCESS CARD DIRECTLY (CARD MODE)
// ============================================================
function processCardDirect(cardResult) {
    let sourceId, destId;
    let sourceName, destName;
    
    if (currentMode === 'OUT') {
        sourceId = cardResult.dDept;
        destId = cardResult.sDept;
        sourceName = cardResult.destName || cardResult.dDept;
        destName = cardResult.sourceName || cardResult.sDept;
    } else {
        sourceId = cardResult.sDept;
        destId = cardResult.dDept;
        sourceName = cardResult.sourceName || cardResult.sDept;
        destName = cardResult.destName || cardResult.dDept;
    }
    
    console.log("📌 Card Mode - Source:", sourceId, "Dest:", destId);
    
    safeUpdateElement('ui_dept_id', sourceId);
    safeUpdateElement('ui_dept_name', sourceName);
    safeUpdateElement('ui_dept_name_display', sourceName);
    safeUpdateElement('ui_dest_id', destId);
    safeUpdateElement('ui_dest_name', destName);
    safeUpdateElement('ui_dest_name_display', destName);
    safeUpdateElement('display_random_code', 'CARD TRANSACTION', 'font-mono text-emerald-400 break-all text-sm');
    safeUpdateElement('display_remark', `HandOver ${currentMode === 'IN' ? 'Inbound' : 'Outbound'}`, 'font-bold text-white text-sm');
    safeUpdateElement('display_authorized', cardResult.owner, 'font-bold text-emerald-400 text-sm');
    safeUpdateElement('source_name', cardResult.owner);
    safeUpdateElement('source_status', `Authorized: ${cardResult.owner}`);
    safeUpdateElement('session_status', 'ACTIVE', 'font-bold text-emerald-400 text-sm');
    safeUpdateElement('path_source', 'CARD', 'font-bold text-cyan-400 text-sm');
    isSessionActive = true;
    
    const userCardSource = document.getElementById('user_card_source');
    if (userCardSource) userCardSource.classList.add('active', 'verified');
    
    safeUpdateElement('capture_status_badge', 'READY: Press PRINT on scale', 'orbitron text-[10px] bg-emerald-500/20 px-3 py-1 rounded-full text-emerald-400 uppercase tracking-widest mb-4');
    safeUpdateElement('scan_prompt', 'PRESS PRINT ON SCALE TO CAPTURE WEIGHT', 'orbitron text-sm animate-pulse uppercase tracking-widest text-emerald-400');
    
    window._pendingQRData = {
        sourceId: sourceId,
        sourceName: sourceName,
        destId: destId,
        destName: destName,
        randomCode: 'CARD_TRANSACTION',
        remark: `HandOver ${currentMode === 'IN' ? 'Inbound' : 'Outbound'}`,
        authorizedBy: cardResult.owner,
        authorizedCard: cardResult.cardCode
    };
    // Show the edit button for customer code
document.getElementById('editRemarkBtn').style.display = 'inline-block';
    console.log("✅ Card processed, _pendingQRData set:", window._pendingQRData);
    
    lastLoggedWeight = -1.0;
    barcodeInput.value = '';
    setTimeout(() => { barcodeInput.focus(); }, 50);
    
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'CARD READY',
        text: `Press PRINT on scale for ${cardResult.owner}`,
        showConfirmButton: false,
        timer: 1500,
        background: '#020617',
        color: '#fff'
    });
}

// ============================================================
// PROCESS QR WITH AUTHORIZED USER (QR MODE)
// CARD provides the path, QR provides the remark
// ============================================================
function processQRWithUser(parseResult, cardResult) {
    // Use random_code from QR
    scannedRandomCode = parseResult.random_code || parseResult;
    
    // ============================================================
    // CARD determines the department path
    // ============================================================
    let sourceId, destId;
    let sourceName, destName;
    
    if (currentMode === 'OUT') {
        sourceId = cardResult.dDept;
        destId = cardResult.sDept;
        sourceName = cardResult.destName || cardResult.dDept;
        destName = cardResult.sourceName || cardResult.sDept;
    } else {
        sourceId = cardResult.sDept;
        destId = cardResult.dDept;
        sourceName = cardResult.sourceName || cardResult.sDept;
        destName = cardResult.destName || cardResult.dDept;
    }
    
    // ============================================================
    // QR provides the remark
    // ============================================================
    scannedRemark = parseResult.remark || 'Scanned Transfer Tag';
    scannedRemark = `${scannedRemark} | Authorized: ${cardResult.owner}`;
    
    console.log("📌 QR Mode - Source (from CARD):", sourceId, "Dest (from CARD):", destId);
    console.log("📌 QR data (remark only):", parseResult);
    
    safeUpdateElement('ui_dept_id', sourceId);
    safeUpdateElement('ui_dept_name', sourceName);
    safeUpdateElement('ui_dept_name_display', sourceName);
    safeUpdateElement('ui_dest_id', destId);
    safeUpdateElement('ui_dest_name', destName);
    safeUpdateElement('ui_dest_name_display', destName);
    safeUpdateElement('display_random_code', scannedRandomCode, 'font-mono text-emerald-400 break-all text-sm');
    safeUpdateElement('display_remark', scannedRemark, 'font-bold text-white text-sm');
    safeUpdateElement('display_authorized', cardResult.owner, 'font-bold text-emerald-400 text-sm');
    safeUpdateElement('source_name', cardResult.owner);
    safeUpdateElement('source_status', `Authorized: ${cardResult.owner}`);
    safeUpdateElement('session_status', 'ACTIVE', 'font-bold text-emerald-400 text-sm');
    safeUpdateElement('path_source', 'CARD (QR provides remark)', 'font-bold text-cyan-400 text-sm');
    isSessionActive = true;
    
    // Show the edit button for customer code
    document.getElementById('editRemarkBtn').style.display = 'inline-block';
    
    const userCardSource = document.getElementById('user_card_source');
    if (userCardSource) userCardSource.classList.add('active', 'verified');
    
    safeUpdateElement('capture_status_badge', 'READY: Press PRINT on scale', 'orbitron text-[10px] bg-emerald-500/20 px-3 py-1 rounded-full text-emerald-400 uppercase tracking-widest mb-4');
    safeUpdateElement('scan_prompt', 'PRESS PRINT ON SCALE TO CAPTURE MORE', 'orbitron text-sm animate-pulse uppercase tracking-widest text-emerald-400');
    
    window._pendingQRData = {
        sourceId: sourceId,
        sourceName: sourceName,
        destId: destId,
        destName: destName,
        randomCode: scannedRandomCode,
        remark: scannedRemark,  // QR's remark
        authorizedBy: cardResult.owner,
        authorizedCard: cardResult.cardCode
    };
    
    console.log("✅ QR processed with CARD path and QR remark");
    console.log("✅ _pendingQRData:", window._pendingQRData);
    
    lastLoggedWeight = -1.0;
    barcodeInput.value = '';
    setTimeout(() => { barcodeInput.focus(); }, 50);
    
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'QR AUTHORIZED',
        text: `Path from: ${cardResult.owner} | Remark: ${parseResult.remark || 'N/A'}`,
        showConfirmButton: false,
        timer: 2000,
        background: '#020617',
        color: '#fff'
    });
}

// ============================================================
// LOG WEIGHT TO DATABASE
// ============================================================
async function logWeightToDatabase(weightValue) {
    console.log("📝 logWeightToDatabase called with weight:", weightValue);
    console.log("📊 window._pendingQRData:", window._pendingQRData);
    
    if (!window._pendingQRData) {
        console.error("❌ No pending data found!");
        Swal.fire({
            icon: 'error',
            title: 'ERROR',
            text: 'No data found. Please scan a card or QR first.',
            background: '#020617',
            color: '#fff'
        });
        return;
    }
    
    const data = window._pendingQRData;
    console.log("📊 Data:", data);
    
    const customRemark = document.getElementById('ui_custom_remark')?.value?.trim() || '';
    const mergedRemarks = customRemark ? `${data.remark} | ${customRemark}` : data.remark;

    const targetPayload = {
        department_id: data.sourceId,
        department_name: data.sourceName,
        destination_id: data.destId,
        destination_name: data.destName,
        random_code: data.randomCode,
        remark: mergedRemarks,
        weight: weightValue,
        mode: currentMode,
        authorized_by: data.authorizedBy
    };
    
    console.log("📤 Sending payload:", targetPayload);

    try {
        const postDestination = `${TARGET_URL}?action=instant_insert`;
        console.log("🌐 POST to:", postDestination);
        
        const res = await fetch(postDestination, { 
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(targetPayload)
        });
        
        const result = await res.json();
        console.log("📥 Response:", result);
        
        if (result.success) {
            targetPayload.db_id = parseInt(result.db_id);
            targetPayload.department_name = data.sourceName;
            targetPayload.destination_name = data.destName;
            targetPayload.mode = currentMode;
            targetPayload.authorized_by = data.authorizedBy;
            runningUiQueue.push(targetPayload);
            console.log("✅ Item added to queue. Queue length:", runningUiQueue.length);
            renderTableInterface();
            
            safeUpdateElement('capture_status_badge', 'READY: Press PRINT on scale again', 'orbitron text-[10px] bg-emerald-500/20 px-3 py-1 rounded-full text-emerald-400 uppercase tracking-widest mb-4');
            safeUpdateElement('scan_prompt', 'PRESS PRINT ON SCALE TO CAPTURE MORE', 'orbitron text-sm animate-pulse uppercase tracking-widest text-emerald-400');
            
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'WEIGHT CAPTURED',
                text: `${weightValue.toFixed(3)}g recorded. Press PRINT for next item.`,
                showConfirmButton: false,
                timer: 1500,
                background: '#020617',
                color: '#fff'
            });
        } else {
            console.error("❌ Insert failed:", result);
            Swal.fire({
                icon: 'error',
                title: 'SAVE FAILED',
                text: result.message || 'Could not save weight.',
                background: '#020617',
                color: '#fff'
            });
        }
    } catch (err) {
        console.error("❌ Stream write fault:", err);
        Swal.fire({
            icon: 'error',
            title: 'NETWORK ERROR',
            text: err.message,
            background: '#020617',
            color: '#fff'
        });
    }
}

function renderTableInterface() {
    const container = document.getElementById('table_row_container');
    const emptyNotice = document.getElementById('empty_notice');
    if (!container) return;
    
    container.innerHTML = "";

    if (runningUiQueue.length === 0) {
        if (emptyNotice) emptyNotice.classList.remove('hidden');
        return;
    }
    if (emptyNotice) emptyNotice.classList.add('hidden');

    runningUiQueue.forEach((item, index) => {
        const row = document.createElement('tr');
        row.className = "hover:bg-white/5 transition-colors duration-150 group";
        
        const sourceName = item.department_name || item.department_id;
        const destName = item.destination_name || item.destination_id;
        const itemMode = item.mode || currentMode;
        const authorizedBy = item.authorized_by || '—';
        
        row.innerHTML = `
            <td class="py-4 px-2 orbitron text-slate-400 font-bold">
                <span class="bg-slate-800 text-slate-300 group-hover:bg-red-500/20 group-hover:text-red-400 w-14 h-6 rounded flex items-center justify-center text-xs border border-white/5 transition-all">
                    Item ${index + 1}
                </span>
            </td>
            <td class="py-4 px-4 text-white font-semibold tracking-wide leading-tight">
                <span class="dept-name-source">${sourceName}</span>
                <span class="block text-[10px] text-amber-500/80 uppercase mt-1">➔ <span class="dept-name-dest">${destName}</span></span>
                <span class="text-[8px] ${itemMode === 'OUT' ? 'text-red-500' : 'text-yellow-500'} mt-1 block">${itemMode === 'OUT' ? 'OUTBOUND' : 'INBOUND'}</span>
            </td>
            <td class="py-4 px-4 text-slate-300 font-mono text-xs">${item.random_code}</td>
            <td class="py-4 px-4 text-slate-400 italic text-xs max-w-[150px] truncate">${item.remark || '<span class="text-slate-600">No remark</span>'}</td>
            <td class="py-4 px-4 text-right orbitron font-bold text-emerald-400 tracking-wide text-base">
                ${parseFloat(item.weight).toFixed(3)}<span class="text-xs font-normal text-slate-500 ml-0.5">g</span>
            </td>
            <td class="py-4 px-4 text-center">
                <span class="authorized-badge">${authorizedBy}</span>
            </td>
            <td class="py-4 px-2 text-center">
                <button onclick="cancelRowAndRecord(${item.db_id})" class="w-6 h-6 inline-flex items-center justify-center rounded-md bg-red-500/10 hover:bg-red-500 text-red-400 hover:text-white transition-all text-sm font-bold shadow-md shadow-red-950/20">&times;</button>
            </td>
        `;
        container.appendChild(row);
    });
}

async function cancelRowAndRecord(databaseRowId) {
    try {
        const postDestination = `${TARGET_URL}?action=instant_delete`;
        const res = await fetch(postDestination, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ db_id: databaseRowId })
        });
        const result = await res.json();
        if (result.success) {
            runningUiQueue = runningUiQueue.filter(item => parseInt(item.db_id) !== parseInt(databaseRowId));
            renderTableInterface();
        } else {
            Swal.fire({ icon: 'error', title: 'Deletion Error', text: result.message, background: '#020617', color: '#fff' });
        }
    } catch(err) {
        Swal.fire({ icon: 'error', title: 'Network Loss', text: 'Could not resolve deletion processing route.', background: '#020617', color: '#fff' });
    }
}

async function executeBackEnd() {
    if (runningUiQueue.length === 0) {
        Swal.fire({ icon: 'error', title: 'EMPTY QUEUE', text: 'No items captured to submit.', background: '#020617', color: '#fff' });
        return;
    }

    const finalSource = parseInt(document.getElementById('ui_dept_id')?.value) || 0;
    const finalDest = parseInt(document.getElementById('ui_dest_id')?.value) || 0;
    const cardDetails = document.getElementById('ui_card_details')?.value?.trim() || '';

    const sender = runningUiQueue.length > 0 ? (runningUiQueue[0].authorized_by || 'SYSTEM') : 'SYSTEM';

    const transfersArray = runningUiQueue.map(item => {
        let productName = (currentMode === 'OUT') ? 'ISSUE_TRANS' : 'RETURN_TRANS';
        
        return {
            productname: productName,
            amount: parseFloat(item.weight),
            remark: item.remark || 'Batch Transfer Item',
            sourceDept: item.department_id || finalSource,
            destinationDept: item.destination_id || finalDest
        };
    });

    const payload = {
        sourceDept: finalSource,
        destinationDept: finalDest,
        card_details: cardDetails,
        mode: currentMode,
        sender: sender,
        transfers: transfersArray
    };

    console.log("📤 Submitting payload with sender:", sender);

    Swal.fire({ title: 'RECORDING BATCH...', background: '#020617', color: '#fff', showConfirmButton: false, didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(`${TARGET_URL}?action=submit_batch_all`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });

        const result = await res.json();

        if (result.success) {
            Swal.fire({ 
                icon: 'success', 
                title: 'TRANSFER COMPLETE', 
                text: `Successfully processed ${runningUiQueue.length} items in 1-shot.`,
                background: '#020617', 
                color: '#fff', 
                timer: 1200, 
                showConfirmButton: false 
            }).then(() => location.href = 'index.php');
        } else {
            Swal.fire({ icon: 'error', title: 'SAVE FAILED', text: result.message || 'Unknown database error', background: '#020617', color: '#fff' });
        }
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'NETWORK FAILURE', text: err.message, background: '#020617', color: '#fff' });
    }
}
</script>
</body>
</html>