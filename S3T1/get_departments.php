<?php
// Set header with UTF-8 charset specification
header('Content-Type: application/json; charset=utf-8');

// Database configuration
$serverName = "localhost\\sqlexpress";
$connectionOptions = array(
    "Database" => "21kEurostar",
    "Uid" => "sa",
    "PWD" => "123456",
    "CharacterSet" => "UTF-8" // <-- FIX 1: Force SQLSRV connection to pull UTF-8 (supports Chinese)
);

// Connect using SQLSRV
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

// Your SQL query
$sql = "SELECT id, DepartmentName FROM dbo.Departments";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(["error" => "Query failed"]);
    exit();
}

$deptMap = [];

// Loop through rows and build the key-value pair
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $deptMap[(string)$row['id']] = $row['DepartmentName'];
}

// Free statement and close connection
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

// Output as JSON object and preserve Unicode characters (Chinese)
echo json_encode($deptMap, JSON_UNESCAPED_UNICODE); // <-- FIX 2: Prevent encoding Chinese into \uXXXX codes
?>