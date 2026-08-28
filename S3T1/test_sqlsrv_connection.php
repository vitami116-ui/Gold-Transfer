<?php
header('Content-Type: text/html');

$serverName = "localhost\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "21kEuroStar",
    "Uid" => "sa",
    "PWD" => "123456",
    "CharacterSet" => "UTF-8"
);

echo "<h2>Testing SQL Server Connection</h2>";

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    echo "❌ Connection failed:<br>";
    die(print_r(sqlsrv_errors(), true));
} else {
    echo "✅ Connected successfully!<br>";
    
    // Test a simple query
    $sql = "SELECT TOP 1 Id, ProductName FROM Inventories";
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt === false) {
        echo "❌ Query failed:<br>";
        die(print_r(sqlsrv_errors(), true));
    }
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
    
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
?>