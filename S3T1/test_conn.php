<?php
// Configuration
$host = "WLI-JTL\\A2006"; // Use the name that worked in your test
$db   = "24k";
$user = "sa";
$pass = "123456";

try {
    // The DSN (Data Source Name) string
    $dsn = "sqlsrv:Server=$host;Database=$db;Encrypt=false;TrustServerCertificate=true";
    
    // Create the connection
    $pdo = new PDO($dsn, $user, $pass);

    // Set error mode to Exceptions so we can catch database errors easily
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // This tells PDO to return data as associative arrays by default
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    echo "<h3>✅ PDO Connected Successfully!</h3>";

    // --- TEST QUERY: Get Departments ---
    $stmt = $pdo->query("SELECT TOP 25 Id, DepartmentName, Weight FROM Departments");
    $rows = $stmt->fetchAll();

    echo "<pre>";
    print_r($rows);
    echo "</pre>";

} catch (PDOException $e) {
    echo "<h3>❌ Connection Failed</h3>";
    echo "Error: " . $e->getMessage();
}
?>