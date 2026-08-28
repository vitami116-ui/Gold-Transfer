<?php
require_once __DIR__.'/admin_common.php'; rae_admin_require_auth();
$id=(int)($_POST['id']??0); $reason=trim((string)($_POST['reason']??''));
if(!$id||$reason==='') die('Transaction ID and deletion reason are required.');
$conn=rae_db(); $before=rae_fetch_record($conn,$id); if(!$before) die('Record not found.');
try{
 sqlsrv_begin_transaction($conn);
 $st=sqlsrv_query($conn,"DELETE FROM TransactionLogs WHERE Id=?",[$id]); if($st===false) throw new Exception('Transaction delete failed.');
 $after=null;
 rae_audit($conn,'DELETE','RAE',$before,null,$id,(int)$before['InventoryId'],$reason);
 if(!sqlsrv_commit($conn)) throw new Exception('Commit failed.');
 header('Location: audit_trail.php?deleted=1'); exit;
}catch(Throwable $e){sqlsrv_rollback($conn);http_response_code(500);die('Delete failed: '.htmlspecialchars($e->getMessage()));}
?>
