<?php
header('Content-Type: application/json');

// 1. Database Connection (MySQL)
// Using your standard local settings - update if your Milano DB name is different
$host = "localhost";
$user = "root";
$pass = "";
$db   = "milano"; // Replace with your actual MySQL database name

$conn = new mysqli($host, $user, $pass, $db);

// Check Connection
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database Connection Failed"]);
    exit;
}

// 2. Get the scanned code from the GET request
$code = isset($_GET['code']) ? $_GET['code'] : '';

if (empty($code)) {
    echo json_encode(["status" => "error", "message" => "No card code provided"]);
    exit;
}

// 3. Query the Milano Security Registry
// We fetch the Owner, and the Dept Names to display on your UI
$sql = "SELECT OwnerName, sDept, dDept FROM Security_Card_Registry WHERE CardID = ? AND IsActive = 1 LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // AUTHORIZED: Card found and active
echo json_encode([
    "status" => "authorized",
    "code"   => $code, // IMPORTANT
    "owner"  => strtoupper($row['OwnerName']),
    "sDept"  => $row['sDept'],
    "dDept"  => $row['dDept']
]);
} else {
    // DENIED: Card not in system or deactivated
    echo json_encode([
        "status" => "denied",
        "message" => "Card not recognized in Milano Registry"
    ]);
}

$stmt->close();
$conn->close();
?>