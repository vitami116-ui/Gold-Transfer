<?php
require_once __DIR__.'/admin_common.php';
if (isset($_GET['logout'])) { rae_admin_logout(); header('Location: admin_edit.php'); exit; }
$error=''; $record=null; $departments=[];
try {
  $conn=rae_db();
  if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='login') {
    if (rae_admin_login((string)($_POST['password']??''))) {
      $id=(int)($_POST['id']??0);
      $return = urlencode($_POST['return'] ?? '');
      $date = urlencode($_POST['date'] ?? '');
      $shift_h = urlencode($_POST['shift_h'] ?? '');
      $shift_m = urlencode($_POST['shift_m'] ?? '');
      header('Location: admin_edit.php?id='.$id.'&return='.$return.'&date='.$date.'&shift_h='.$shift_h.'&shift_m='.$shift_m);
      exit;
    }
    $error='Incorrect admin password.';
  }
  if (rae_admin_is_authenticated()) {
    rae_admin_require_auth();
    $id=(int)($_GET['id']??$_POST['id']??0);
    if($id>0){
      $record=rae_fetch_record($conn,$id);
      if(!$record) $error='Record not found.';
      $st=sqlsrv_query($conn,"SELECT Id,DepartmentName FROM Departments ORDER BY DepartmentName");
      if($st) while($d=sqlsrv_fetch_array($st,SQLSRV_FETCH_ASSOC)) $departments[]=$d;
    }
  }
} catch(Throwable $e){ $error=$e->getMessage(); }
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html><head><meta charset="utf-8"><title>RAE Admin Edit</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#0f172a;color:#fff}
.card{background:#1e293b;border:1px solid #334155}
.form-control,.form-select{background:#0f172a;color:#fff;border-color:#475569}
.muted{color:#94a3b8}
</style>
</head>
<body>
<div class="container py-5" style="max-width:900px">
<div class="d-flex justify-content-between mb-4">
  <h3>RAE Admin Correction</h3>
  <div>
    <a href="audit_trail.php" class="btn btn-outline-info">Audit Trail</a>
    <?php if(isset($_GET['return']) && $_GET['return']): ?>
    <a href="<?=h($_GET['return'])?>?date=<?=h($_GET['date']??'')?>&shift_h=<?=h($_GET['shift_h']??'')?>&shift_m=<?=h($_GET['shift_m']??'')?>" class="btn btn-outline-secondary">Back to Report</a>
    <?php endif; ?>
  </div>
</div>
<?php if($error):?><div class="alert alert-danger"><?=h($error)?></div><?php endif;?>

<?php if(isset($_GET['saved'])):?>
<div class="alert alert-success">✅ Correction saved successfully! View the <a href="audit_trail.php" class="alert-link">Audit Trail</a> for details.</div>
<?php endif;?>

<?php if(!rae_admin_is_authenticated()):?>
<div class="card p-4">
  <h5>Admin authorization required</h5>
  <p class="muted">Enter the administrator password to edit this record.</p>
  <form method="post">
    <input type="hidden" name="action" value="login">
    <input type="hidden" name="id" value="<?=h($_GET['id']??'')?>">
    <input type="hidden" name="return" value="<?=h($_GET['return']??'')?>">
    <input type="hidden" name="date" value="<?=h($_GET['date']??'')?>">
    <input type="hidden" name="shift_h" value="<?=h($_GET['shift_h']??'')?>">
    <input type="hidden" name="shift_m" value="<?=h($_GET['shift_m']??'')?>">
    <label class="form-label">Admin Password</label>
    <input class="form-control mb-3" type="password" name="password" required autofocus>
    <button class="btn btn-primary">Authorize Edit</button>
  </form>
</div>
<?php elseif($record):?>
<div class="card p-4">
  <div class="mb-3">
    <span class="muted">Transaction #<?=h($record['TransactionLogId'])?> · Inventory #<?=h($record['InventoryId'])?></span>
    <?php if($record['TransactionCreatedOn']): ?>
    <span class="muted ms-3">Current: <?=h($record['TransactionCreatedOn'] instanceof DateTimeInterface ? $record['TransactionCreatedOn']->format('Y-m-d H:i:s') : $record['TransactionCreatedOn'])?></span>
    <?php endif; ?>
  </div>
  
  <form method="post" action="admin_update.php">
    <input type="hidden" name="id" value="<?=h($record['TransactionLogId'])?>">
    <input type="hidden" name="return" value="<?=h($_GET['return']??'')?>">
    <input type="hidden" name="date" value="<?=h($_GET['date']??'')?>">
    <input type="hidden" name="shift_h" value="<?=h($_GET['shift_h']??'')?>">
    <input type="hidden" name="shift_m" value="<?=h($_GET['shift_m']??'')?>">
    
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Source</label>
        <select class="form-select" name="source_id" required>
          <?php foreach($departments as $d):?>
            <option value="<?=h($d['Id'])?>" <?=$d['Id']==$record['SourceDepartmentId']?'selected':''?>>
              <?=h($d['DepartmentName'])?>
            </option>
          <?php endforeach;?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Destination</label>
        <select class="form-select" name="destination_id" required>
          <?php foreach($departments as $d):?>
            <option value="<?=h($d['Id'])?>" <?=$d['Id']==$record['DestinationDepartmentId']?'selected':''?>>
              <?=h($d['DepartmentName'])?>
            </option>
          <?php endforeach;?>
        </select>
      </div>
      
      <div class="col-md-6">
        <label class="form-label">Transaction Date <span class="text-warning">*</span></label>
        <?php 
          $dateVal = '';
          if($record['TransactionCreatedOn'] instanceof DateTimeInterface) {
            $dateVal = $record['TransactionCreatedOn']->format('Y-m-d');
          } elseif($record['TransactionCreatedOn']) {
            $dateVal = date('Y-m-d', strtotime($record['TransactionCreatedOn']));
          }
        ?>
        <input class="form-control" type="date" name="transaction_date" 
               value="<?=h($dateVal)?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Transaction Time <span class="text-warning">*</span></label>
        <?php 
          $timeVal = '';
          if($record['TransactionCreatedOn'] instanceof DateTimeInterface) {
            $timeVal = $record['TransactionCreatedOn']->format('H:i:s');
          } elseif($record['TransactionCreatedOn']) {
            $timeVal = date('H:i:s', strtotime($record['TransactionCreatedOn']));
          }
        ?>
        <input class="form-control" type="time" name="transaction_time" step="1"
               value="<?=h($timeVal)?>" required>
      </div>
      
      <div class="col-md-6">
        <label class="form-label">Card / Product</label>
        <input class="form-control" name="product_name" value="<?=h($record['ProductName'])?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Weight (g)</label>
        <input class="form-control" type="number" step="0.001" min="0" name="weight" 
               value="<?=h($record['TransactionWeight'])?>" required>
      </div>
      <div class="col-12">
        <label class="form-label">Remark</label>
        <textarea class="form-control" name="remark" rows="2"><?=h($record['TransactionRemark'])?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Reason for correction <span class="text-warning">*</span></label>
        <textarea class="form-control" name="reason" rows="2" required 
                  placeholder="e.g. Wrong date/time entered, Wrong card scanned, Incorrect weight"></textarea>
        <small class="muted">This reason will be recorded in the audit trail for accountability.</small>
      </div>
    </div>
    
    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-warning">Commit Correction</button>
      <a class="btn btn-outline-secondary" href="javascript:history.back()">Cancel</a>
    </div>
  </form>
  
  <form method="post" action="admin_delete.php" class="mt-4" onsubmit="return confirm('Delete this transaction? The audit trail will remain.');">
    <input type="hidden" name="id" value="<?=h($record['TransactionLogId'])?>">
    <input type="hidden" name="return" value="<?=h($_GET['return']??'')?>">
    <input type="hidden" name="date" value="<?=h($_GET['date']??'')?>">
    <input type="hidden" name="shift_h" value="<?=h($_GET['shift_h']??'')?>">
    <input type="hidden" name="shift_m" value="<?=h($_GET['shift_m']??'')?>">
    <input type="hidden" name="reason" id="deleteReason">
    <button type="button" class="btn btn-outline-danger" onclick="const r=prompt('Reason for deletion:');if(r){document.getElementById('deleteReason').value=r;this.form.submit();}">
      Delete This Scan
    </button>
  </form>
</div>
<?php endif;?>
</div>
</body>
</html>