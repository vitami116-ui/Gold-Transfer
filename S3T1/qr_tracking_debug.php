<?php
// S3T1/check_debug.php
$logFile = __DIR__ . '/qr_tracking_debug.log';
if (file_exists($logFile)) {
    echo "<h2>qr_tracking_debug.log</h2>";
    echo "<pre>" . file_get_contents($logFile) . "</pre>";
} else {
    echo "qr_tracking_debug.log not found.<br>";
}

$handoverLog = __DIR__ . '/handover_debug.log';
if (file_exists($handoverLog)) {
    echo "<h2>handover_debug.log</h2>";
    echo "<pre>" . file_get_contents($handoverLog) . "</pre>";
} else {
    echo "handover_debug.log not found.<br>";
}
?>