<?php
header('Content-Type: application/json');

$serverName = "localhost\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "21kEuroStar",
    "Uid" => "sa",
    "PWD" => "123456"
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

$invId = $_POST['invId'] ?? null;

if (!$invId) {
    echo json_encode(["success" => false, "message" => "Missing Inventory ID"]);
    exit;
}

if (!$conn) {
    echo json_encode(["success" => false, "message" => "DB Connection Failed"]);
    exit;
}

// Update IsStone only if record was created within the last 4 hours
$sql = "UPDATE [21kEuroStar].[dbo].[Inventories] 
        SET IsStone = CASE WHEN IsStone = 1 THEN 0 ELSE 1 END 
        WHERE Id = ? 
        AND CreatedOn >= DATEADD(hour, -4, GETDATE())";

$stmt = sqlsrv_query($conn, $sql, [$invId]);

if ($stmt === false) {
    echo json_encode(["success" => false, "message" => "SQL Error"]);
} elseif (sqlsrv_rows_affected($stmt) > 0) {
    echo json_encode(["success" => true]);
} else {
    // This happens if the ID is wrong OR if the 4-hour limit has passed
    echo json_encode(["success" => false, "message" => "Cannot toggle: Record is older than 4 hours."]);
}

sqlsrv_close($conn);
?>                          