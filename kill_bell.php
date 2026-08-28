<?php
// 1. Database connection settings
$serverName = "localhost\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "21kEuroStar",
    "Uid" => "sa",
    "PWD" => "123456",
    "CharacterSet" => "UTF-8"
);

// 2. Define Log File Path
$logFile = __DIR__ . 'kill_bell_log.txt';

$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    $errorMsg = "[" . date('Y-m-d H:i:s') . "] CONNECTION FAILED: " . print_r(sqlsrv_errors(), true);
    file_put_contents($logFile, $errorMsg . PHP_EOL, FILE_APPEND);
    die($errorMsg);
}

/**
 * Logic:
 * 1. Find records where IsStone is currently 1.
 * 2. Set IsStone back to 0.
 * 3. 
 */
$sql = "UPDATE [21kEuroStar].[dbo].[Inventories]
        SET [IsStone] = 0
        WHERE [IsStone] = 1";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    $errorMsg = "[" . date('Y-m-d H:i:s') . "] SQL ERROR: " . print_r(sqlsrv_errors(), true);
    file_put_contents($logFile, $errorMsg . PHP_EOL, FILE_APPEND);
} else {
    $rowsAffected = sqlsrv_rows_affected($stmt);
    
    // Create the log entry
    $logEntry = "[" . date('Y-m-d H:i:s') . "] SUCCESS: Reset $rowsAffected records to IsStone=0 and time to 6:00 AM.";
    
    // Write to the text file (FILE_APPEND prevents overwriting old logs)
    file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND);
    
    echo $logEntry;
}

sqlsrv_close($conn);
?>