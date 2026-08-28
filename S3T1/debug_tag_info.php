<?php
// S3T1/debug_tag_info.php
// This file helps debug why get_tag_info.php is not blocking IN TRANSIT QR codes

header('Content-Type: text/html');

// Get the QR code from URL parameter or use default
$code = isset($_GET['code']) ? $_GET['code'] : 'C27B85600D61';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'OUT';

echo "<h2>🔍 Debug get_tag_info.php</h2>";
echo "<p><strong>QR Code:</strong> " . htmlspecialchars($code) . "</p>";
echo "<p><strong>Mode:</strong> " . htmlspecialchars($mode) . "</p>";
echo "<hr>";

// ============================================================
// 1. Check MySQL - barcode_db (qr_tracking)
// ============================================================
echo "<h3>📊 1. Check MySQL (barcode_db.qr_tracking)</h3>";

$myConn = new mysqli("localhost", "root", "", "barcode_db");
if ($myConn->connect_error) {
    echo "❌ MySQL Connection failed: " . $myConn->connect_error . "<br>";
} else {
    echo "✅ MySQL Connected<br>";
    
    $sql = "SELECT * FROM qr_tracking WHERE random_code = ?";
    $stmt = $myConn->prepare($sql);
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($row as $key => $value) {
            echo "<tr><td><strong>" . htmlspecialchars($key) . "</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
        
        $status = $row['status'] ?? 'unknown';
        echo "<p><strong>Status:</strong> " . htmlspecialchars($status) . "</p>";
        if ($status === 'in_transit') {
            echo "<p style='color:red;font-weight:bold;'>🚫 QR is IN TRANSIT - Should block OUTBOUND!</p>";
        } else {
            echo "<p style='color:green;font-weight:bold;'>✅ QR is ACTIVE - Should allow OUTBOUND</p>";
        }
    } else {
        echo "❌ No record found in qr_tracking for: " . htmlspecialchars($code) . "<br>";
    }
    $stmt->close();
    $myConn->close();
}

echo "<hr>";

// ============================================================
// 2. Check SQL Server - 21kEuroStar (Inventories)
// ============================================================
echo "<h3>📊 2. Check SQL Server (21kEuroStar.Inventories)</h3>";

$serverName = "localhost\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "21kEuroStar",
    "Uid" => "sa",
    "PWD" => "123456",
    "CharacterSet" => "UTF-8"
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    echo "❌ SQL Server Connection failed<br>";
    die(print_r(sqlsrv_errors(), true));
} else {
    echo "✅ SQL Server Connected<br>";
    
    // Search for the QR code in Inventories
    $sql = "SELECT Id, ProductName, Weight, DepartmentId, Remark, CreatedOn 
            FROM Inventories 
            WHERE ProductName = ?";
    $params = array($code);
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        echo "❌ Query failed<br>";
        die(print_r(sqlsrv_errors(), true));
    }
    
    $records = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $records[] = $row;
    }
    
    $recordCount = count($records);
    echo "<p><strong>Total records found:</strong> " . $recordCount . "</p>";
    
    if ($recordCount > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Id</th><th>ProductName</th><th>Weight</th><th>DepartmentId</th><th>Remark</th><th>CreatedOn</th></tr>";
        foreach ($records as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ProductName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Weight']) . "</td>";
            echo "<td>" . htmlspecialchars($row['DepartmentId']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Remark'] ?? '') . "</td>";
            echo "<td>" . ($row['CreatedOn'] ? $row['CreatedOn']->format('Y-m-d H:i:s') : '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Odd count = IN TRANSIT
        $isInTransit = ($recordCount % 2 !== 0);
        echo "<p><strong>Record count:</strong> " . $recordCount . " (Odd = IN TRANSIT, Even = COMPLETE)</p>";
        if ($isInTransit) {
            echo "<p style='color:red;font-weight:bold;'>🚫 QR is IN TRANSIT (odd record count)</p>";
        } else {
            echo "<p style='color:green;font-weight:bold;'>✅ QR is COMPLETE (even record count)</p>";
        }
    } else {
        echo "❌ No records found in Inventories for: " . htmlspecialchars($code) . "<br>";
        echo "<p style='color:orange;'>⚠️ QR has NOT been used for OUTBOUND yet</p>";
    }
    
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

echo "<hr>";

// ============================================================
// 3. Call get_tag_info.php directly using cURL
// ============================================================
echo "<h3>📊 3. Call get_tag_info.php directly</h3>";

$url = "http://192.168.88.88:81/s3t1/get_tag_info.php?code=" . urlencode($code) . "&mode=" . urlencode($mode);
echo "<p><strong>URL:</strong> <a href='" . htmlspecialchars($url) . "' target='_blank'>" . htmlspecialchars($url) . "</a></p>";

// Use cURL instead of file_get_contents
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HEADER => false
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";

if ($curlError) {
    echo "<p style='color:red;'><strong>cURL Error:</strong> " . htmlspecialchars($curlError) . "</p>";
}

if ($response) {
    $json = json_decode($response, true);
    if ($json) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($json as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            echo "<tr><td><strong>" . htmlspecialchars($key) . "</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
        
        if (isset($json['in_transit']) && $json['in_transit'] === true) {
            echo "<p style='color:red;font-weight:bold;'>🚫 get_tag_info.php says: IN TRANSIT - Should block OUTBOUND!</p>";
        } else {
            echo "<p style='color:green;font-weight:bold;'>✅ get_tag_info.php says: Available for OUTBOUND</p>";
        }
    } else {
        echo "❌ Failed to parse JSON response from get_tag_info.php<br>";
        echo "<p><strong>Raw Response:</strong></p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
} else {
    echo "❌ No response from get_tag_info.php<br>";
}

echo "<hr>";

// ============================================================
// 4. Summary
// ============================================================
echo "<h3>📋 Summary</h3>";

// Determine overall status
$mysqlInTransit = false;
$sqlsrvRecordCount = 0;

// Check MySQL
$myConn2 = new mysqli("localhost", "root", "", "barcode_db");
if (!$myConn2->connect_error) {
    $sql = "SELECT status FROM qr_tracking WHERE random_code = ?";
    $stmt = $myConn2->prepare($sql);
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $mysqlInTransit = ($row['status'] === 'in_transit');
    }
    $stmt->close();
    $myConn2->close();
}

// Get record count from SQL Server
$conn2 = sqlsrv_connect($serverName, $connectionOptions);
if ($conn2) {
    $sql = "SELECT COUNT(*) as cnt FROM Inventories WHERE ProductName = ?";
    $stmt = sqlsrv_query($conn2, $sql, array($code));
    if ($stmt) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $sqlsrvRecordCount = $row['cnt'] ?? 0;
        sqlsrv_free_stmt($stmt);
    }
    sqlsrv_close($conn2);
}

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Check</th><th>Result</th></tr>";
echo "<tr><td>MySQL (qr_tracking) status</td><td>" . ($mysqlInTransit ? "<span style='color:red;font-weight:bold;'>in_transit</span>" : "<span style='color:green;'>active</span>") . "</td></tr>";
echo "<tr><td>SQL Server (Inventories) record count</td><td>" . $sqlsrvRecordCount . "</td></tr>";
echo "<tr><td><strong>Should block OUTBOUND?</strong></td><td>" . ($mysqlInTransit ? "<span style='color:red;font-weight:bold;'>YES - Block OUTBOUND</span>" : "<span style='color:green;'>NO - Allow OUTBOUND</span>") . "</td></tr>";
echo "</table>";

// Recommendation
if ($mysqlInTransit && $sqlsrvRecordCount > 0) {
    echo "<p style='color:red;font-weight:bold;'>✅ Both MySQL and SQL Server show IN TRANSIT. OUTBOUND should be BLOCKED.</p>";
    echo "<p>If you're not being blocked, the issue is in <code>process.php</code> not checking the response from <code>get_tag_info.php</code> correctly.</p>";
    echo "<p>Check <code>process.php</code> line ~920 for the OUTBOUND validation logic.</p>";
} elseif ($mysqlInTransit && $sqlsrvRecordCount === 0) {
    echo "<p style='color:orange;font-weight:bold;'>⚠️ Inconsistency: MySQL says IN TRANSIT but SQL Server has no records.</p>";
    echo "<p>Run this in phpMyAdmin to fix: <code>UPDATE qr_tracking SET status = 'active' WHERE random_code = '$code';</code></p>";
} elseif (!$mysqlInTransit && $sqlsrvRecordCount > 0) {
    echo "<p style='color:orange;font-weight:bold;'>⚠️ Inconsistency: SQL Server has records but MySQL says active.</p>";
    echo "<p>Run this in phpMyAdmin to fix: <code>UPDATE qr_tracking SET status = 'in_transit' WHERE random_code = '$code';</code></p>";
} else {
    echo "<p style='color:green;font-weight:bold;'>✅ Both MySQL and SQL Server show QR is available for OUTBOUND.</p>";
}
?>