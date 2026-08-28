<?php
// api_departments.php (Host: 10.251.155.145)

// Force UTF-8 Encoding Header
header('Content-Type: application/json; charset=utf-8');

// 1. STRICT IP RESTRICTION: Only allow requests from Server B
$allowed_ip = '10.251.155.121'; // Adjust to 10.251.155.121 if that is your exact app server IP
$client_ip  = $_SERVER['REMOTE_ADDR'] ?? '';

if ($client_ip !== $allowed_ip) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Forbidden: Access denied for IP ' . $client_ip
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// 2. MSSQL CONNECTION CONFIGURATION
$db_host = 'localhost\SQLEXPRESS'; // MSSQL Instance
$db_name = '21kEuroStar';
$db_user = 'sa';
$db_pass = '123456';

try {
    $pdo = new PDO("sqlsrv:Server=$db_host;Database=$db_name", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 3. QUERY DEPARTMENTS
    $stmt = $pdo->prepare("SELECT * FROM dbo.departments");
    $stmt->execute();
    $departments = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count'   => count($departments),
        'data'    => $departments
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database Connection Failed: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}