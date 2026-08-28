<?php
// save_settings.php
$conn = new mysqli("localhost", "root", "", "milano");

$date   = $_POST['date'];
$src    = $_POST['source'];
$dest   = $_POST['dest'];
$hour   = $_POST['hour'];
$min    = $_POST['minute'];

// UPSERT logic: Insert or Update if the key already exists
$sql = "INSERT INTO userreportsettings (viewdate, sourcedepartmentid, destinationdepartmentid, shifthour, shiftminute) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE shifthour = VALUES(shifthour), shiftminute = VALUES(shiftminute)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("siiii", $date, $src, $dest, $hour, $min);

if ($stmt->execute()) {
    // Redirect back to the report with the same parameters
    header("Location: UNIVERSAL.php?date=$date&source=$src&dest=$dest");
} else {
    echo "Error saving settings: " . $conn->error;
}