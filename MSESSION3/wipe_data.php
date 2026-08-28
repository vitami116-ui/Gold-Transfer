<?php
$conn = new mysqli("localhost", "root", "", "milano");
$conn->query("TRUNCATE TABLE scale_logs");
?>