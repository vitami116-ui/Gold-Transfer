<?php
require_once 'db_connect.php';
$pdo = getPDO($_GET['db']);
$data = json_decode(file_get_contents('php://input'), true);

try {
    $pdo->beginTransaction();
    $sub = $pdo->prepare("UPDATE Departments SET Weight = Weight - ? WHERE Id = ?");
    $add = $pdo->prepare("UPDATE Departments SET Weight = Weight + ? WHERE Id = ?");

    foreach ($data['transfers'] as $t) {
        $sub->execute([$t['amount'], $t['sourceDept']]);
        $add->execute([$t['amount'], $t['destinationDept']]);
    }
    $pdo->commit();
    echo json_encode(["success" => true]);
} catch (Exception $e) { $pdo->rollBack(); echo json_encode(["success" => false, "message" => $e->getMessage()]); }