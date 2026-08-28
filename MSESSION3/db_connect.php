<?php
// db_connect.php
function getPDO($dbName) {
    $serverName = "localhost\\SQLEXPRESS"; // Double backslash for named instance
    $username = "sa";
    $password = "123456";

    try {
        $dsn = "sqlsrv:Server=$serverName;Database=$dbName;Encrypt=false;TrustServerCertificate=true";
        $pdo = new PDO($dsn, $username, $password);
        
        // Error handling: Throw exceptions instead of warnings
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Fetch mode: Return as associative arrays
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        die(json_encode([
            "success" => false, 
            "message" => "PDO Connection Error: " . $e->getMessage()
        ]));
    }
}