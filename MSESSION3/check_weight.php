<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kuala_Lumpur'); // Fix for the 30,000s error
$conn = new mysqli("localhost", "root", "", "milano");

// Force MySQL to match PHP Time
$conn->query("SET time_zone = '+08:00'");

// 1. Get the latest weight
$sql = "SELECT weight_value, created_at FROM scale_logs ORDER BY created_at DESC LIMIT 1";
$result = $conn->query($sql);

if ($row = $result->fetch_assoc()) {
    $weightTime = strtotime($row['created_at']);
    $currentTime = time();
    $age = $currentTime - $weightTime;

    echo json_encode([
        'weight' => $row['weight_value'],
        'age' => max(0, $age), // Prevents negative numbers
        'valid' => ($age <= 10)
    ]);
} else {
    echo json_encode(['weight' => 0, 'age' => 999, 'valid' => false]);
}
?>