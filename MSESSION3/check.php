<?php
// Include your existing connection function
require_once 'db_connect.php';

// Specify the database you want to test
$databaseName = "21kEuroStar"; 

try {
    $pdo = getPDO($databaseName);

    // If we reach this line, the connection was successful
    header('Content-Type: application/json');
    echo json_encode([
        "success" => true,
        "message" => "Successfully connected to the database: $databaseName",
        "details" => [
            "driver" => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
            "version" => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION)
        ]
    ]);

} catch (Exception $e) {
    // Note: getPDO already has a try-catch that dies with a JSON response,
    // but this acts as a safety net.
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        "success" => false,
        "message" => "Testing script failed: " . $e->getMessage()
    ]);
}