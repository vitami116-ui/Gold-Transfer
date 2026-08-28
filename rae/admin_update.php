<?php
require_once __DIR__.'/admin_common.php'; 
rae_admin_require_auth();

if($_SERVER['REQUEST_METHOD']!=='POST'){
    header('Location: admin_edit.php');
    exit;
}

$id = (int)($_POST['id']??0);
$source = (int)($_POST['source_id']??0);
$dest = (int)($_POST['destination_id']??0);
$product = trim((string)($_POST['product_name']??''));
$weight = (float)($_POST['weight']??-1);
$remark = (string)($_POST['remark']??'');
$reason = trim((string)($_POST['reason']??''));

// Get date and time
$transactionDate = trim((string)($_POST['transaction_date']??''));
$transactionTime = trim((string)($_POST['transaction_time']??''));

// Get return parameters
$return = $_POST['return'] ?? 'admin_edit.php';
$dateParam = isset($_POST['date']) ? '&date=' . urlencode($_POST['date']) : '';
$shiftH = isset($_POST['shift_h']) ? '&shift_h=' . urlencode($_POST['shift_h']) : '';
$shiftM = isset($_POST['shift_m']) ? '&shift_m=' . urlencode($_POST['shift_m']) : '';

// Validate inputs
if(!$id || !$source || !$dest || $source===$dest || $product==='' || $weight<0 || $reason==='') {
    die('Invalid correction data. All fields are required.');
}

// Validate date/time
if(empty($transactionDate) || empty($transactionTime)) {
    die('Transaction date and time are required.');
}

$transactionDateTime = $transactionDate . ' ' . $transactionTime;

// Validate the datetime is valid
$dt = DateTime::createFromFormat('Y-m-d H:i:s', $transactionDateTime);
if(!$dt) {
    die('Invalid date or time format.');
}

$conn = rae_db(); 
$before = rae_fetch_record($conn,$id); 
if(!$before) die('Record not found.');

try{
    sqlsrv_begin_transaction($conn);
    
    // Update TransactionLogs with date/time
    $st = sqlsrv_query($conn, 
        "UPDATE TransactionLogs SET 
            SourceDepartmentId=?, 
            DestinationDepartmentId=?, 
            Weight=?, 
            Remark=?,
            CreatedOn=?
        WHERE Id=?",
        [$source, $dest, $weight, $remark, $transactionDateTime, $id]
    ); 
    if($st===false) {
        $errors = sqlsrv_errors();
        throw new Exception('Transaction update failed: ' . print_r($errors, true));
    }
    
    // Also update the Inventory CreatedOn to match
    $st = sqlsrv_query($conn, 
        "UPDATE Inventories SET 
            ProductName=?, 
            Remark=?,
            CreatedOn=?
        WHERE Id=?",
        [$product, $remark, $transactionDateTime, $before['InventoryId']]
    );
    if($st===false) {
        $errors = sqlsrv_errors();
        throw new Exception('Inventory update failed: ' . print_r($errors, true));
    }
    
    // Fetch the updated record for audit trail
    $after = rae_fetch_record($conn,$id);
    
    // Audit the change - this captures EVERYTHING including date/time
    rae_audit(
        $conn,
        'UPDATE',
        'RAE',
        $before,  // Before data includes old date/time
        $after,   // After data includes new date/time
        $id,
        (int)$before['InventoryId'],
        $reason
    );
    
    if(!sqlsrv_commit($conn)) {
        throw new Exception('Commit failed.');
    }
    
    // Redirect back with success
    header('Location: ' . $return . '?id=' . $id . '&saved=1' . $dateParam . $shiftH . $shiftM);
    exit;
    
} catch(Throwable $e){
    sqlsrv_rollback($conn);
    http_response_code(500);
    die('Correction failed: '.htmlspecialchars($e->getMessage()));
}
?>