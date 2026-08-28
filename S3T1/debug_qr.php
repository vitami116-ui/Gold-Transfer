<?php
header('Content-Type: text/html');

$serverName = "localhost\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "21kEuroStar",
    "Uid" => "sa",
    "PWD" => "123456",
    "CharacterSet" => "UTF-8"
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

$code = isset($_GET['code']) ? $_GET['code'] : 'C27B85600D61';

echo "<h2>Debug QR: $code</h2>";

// Search for the code in Inventories
$sql = "SELECT Id, ProductName, Weight, DepartmentId, Remark, CreatedOn 
        FROM Inventories 
        WHERE ProductName LIKE ? 
        ORDER BY CreatedOn ASC";
$params = ['%' . $code . '%'];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$records = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $records[] = $row;
}

echo "<h3>Found " . count($records) . " records:</h3>";
echo "<pre>";
print_r($records);
echo "</pre>";

// Also check for exact match
$sql2 = "SELECT Id, ProductName, Weight, DepartmentId, Remark, CreatedOn 
         FROM Inventories 
         WHERE ProductName = ?";
$stmt2 = sqlsrv_query($conn, $sql2, [$code]);

echo "<h3>Exact match for '$code':</h3>";
if ($stmt2 !== false) {
    $exact = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
    echo "<pre>";
    print_r($exact);
    echo "</pre>";
}

sqlsrv_close($conn);
?>