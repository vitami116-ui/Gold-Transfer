<?php
echo "<h2>Testing Database Connection</h2>";

$serverName = "localhost\SQLEXPRESS";
$dbName = "21kEuroStar";
$username = "sa";
$password = "123456";

try {
    $pdo = new PDO("sqlsrv:Server=$serverName;Database=$dbName;Encrypt=false;TrustServerCertificate=true", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to SQL Server successfully!<br>";

    // Test query
    $stmt = $pdo->query("SELECT TOP 5 Id, DepartmentName FROM Departments");
    $rows = $stmt->fetchAll();
    echo "<h3>Departments:</h3>";
    echo "<pre>";
    print_r($rows);
    echo "</pre>";

    // Test insert
    $testInsert = $pdo->prepare("INSERT INTO Inventories (ProductName, Remark, Weight, DepartmentId, IsStone) VALUES (?, ?, ?, ?, 0)");
    $testInsert->execute(['TEST_' . date('YmdHis'), 'Test Insert', 1.0, 10085]);
    $newId = $pdo->lastInsertId();
    echo "✅ Test insert successful! New ID: $newId<br>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " line " . $e->getLine() . "<br>";
}
?>