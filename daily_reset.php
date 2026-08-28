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
$logFile = __DIR__ . '/stone_reset_log.txt';

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
 * 3. Update CreatedOn to 6:00 AM today.
 */
$sql = "UPDATE [21kEuroStar].[dbo].[Inventories]
        SET [IsStone] = 0, 
            [CreatedOn] = DATETIMEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), DAY(GETDATE()), 6, 0, 0, 0)
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

// 2. Define Yesterday's Date Boundaries (2026-05-17)
$yesterdayStart = date('Y-m-d 00:00:00', strtotime('yesterday'));
$yesterdayEnd   = date('Y-m-d 23:59:59', strtotime('yesterday'));
$params = array($yesterdayStart, $yesterdayEnd);

// Start Transaction
if (sqlsrv_begin_transaction($conn) === false) {
    die(print_r(sqlsrv_errors(), true));
}

try {
    // 3. Count ALL records in Inventories for yesterday
    $sqlTotalInv = "
        SELECT COUNT(*) AS total_count 
        FROM [dbo].[Inventories] 
        WHERE [CreatedOn] >= ? AND [CreatedOn] <= ?
    ";
    $stmtTotal = sqlsrv_query($conn, $sqlTotalInv, $params);
    if ($stmtTotal === false) throw new Exception(print_r(sqlsrv_errors(), true));
    
    $rowTotal = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC);
    $totalCount = $rowTotal['total_count'];

    // 4. Count ONLY 06:00:00 AM records in Inventories for yesterday
    $sqlSixAmInv = "
        SELECT COUNT(*) AS six_am_count 
        FROM [dbo].[Inventories] 
        WHERE [CreatedOn] >= ? 
          AND [CreatedOn] <= ? 
          AND CAST([CreatedOn] AS TIME) = '06:00:00.000'
    ";
    $stmtSixAm = sqlsrv_query($conn, $sqlSixAmInv, $params);
    if ($stmtSixAm === false) throw new Exception(print_r(sqlsrv_errors(), true));
    
    $rowSixAm = sqlsrv_fetch_array($stmtSixAm, SQLSRV_FETCH_ASSOC);
    $sixAmCount = $rowSixAm['six_am_count'];

    echo "Total Yesterday Inventories: $totalCount <br>";
    echo "Yesterday 06:00 AM Inventories: $sixAmCount <br><br>";

    // 5. If the counts match (and there's actually data to move), perform the update
    if ($totalCount === $sixAmCount && $totalCount > 0) {
        echo "Counts match! Shifting 06:00 AM records forward...<br>";

        $sqlUpdate = "
            UPDATE [dbo].[Inventories]
            SET [CreatedOn] = DATEADD(day, 1, [CreatedOn])
            WHERE [CreatedOn] >= ? 
              AND [CreatedOn] <= ?
              AND CAST([CreatedOn] AS TIME) = '06:00:00.000'
        ";

        $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $params);
        if ($stmtUpdate === false) throw new Exception(print_r(sqlsrv_errors(), true));
        
        $rowsUpdated = sqlsrv_rows_affected($stmtUpdate);
        
        sqlsrv_commit($conn);
        echo "Success! Moved $rowsUpdated records forward by +1 day.";

    } else {
        sqlsrv_rollback($conn);
        if ($totalCount !== $sixAmCount) {
            echo "Validation Failed: Total count ($totalCount) does not match 06:00 AM count ($sixAmCount). Transaction rolled back.";
        } else {
            echo "Validation Skipped: No inventory records found for yesterday.";
        }
    }

} catch (Exception $e) {
    sqlsrv_rollback($conn);
    echo "Error detected. All database changes rolled back.<br>";
    echo "Details: " . $e->getMessage();
}
sqlsrv_close($conn);
?>