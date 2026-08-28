<?php
require_once __DIR__.'/admin_common.php'; 
rae_admin_require_auth();
$conn=rae_db(); 
rae_ensure_audit_table($conn);

// Add filter for date range
$filterDate = $_GET['date'] ?? '';
$filterAction = $_GET['action'] ?? '';
$filterAdmin = $_GET['admin'] ?? '';

// Fetch department names for display
$deptMap = [];
$deptStmt = sqlsrv_query($conn, "SELECT Id, DepartmentName FROM Departments");
if ($deptStmt !== false) {
    while ($dept = sqlsrv_fetch_array($deptStmt, SQLSRV_FETCH_ASSOC)) {
        $deptMap[$dept['Id']] = $dept['DepartmentName'];
    }
}

$sql = "SELECT TOP 500 * FROM RAE_AdminAudit";
$where = [];
$params = [];

if($filterDate) {
    $where[] = "CAST(CreatedOn AS DATE) = ?";
    $params[] = $filterDate;
}
if($filterAction) {
    $where[] = "ActionType = ?";
    $params[] = $filterAction;
}
if($filterAdmin) {
    $where[] = "AdminUser = ?";
    $params[] = $filterAdmin;
}

if(!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY CreatedOn DESC";

$st = sqlsrv_query($conn, $sql, $params);
$totalRecords = $st !== false ? sqlsrv_num_rows($st) : 0;
?>
<!doctype html><html><head><meta charset="utf-8"><title>RAE Audit Trail</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
body{background:#0f172a;color:#fff}
.table{color:#fff;font-size:0.82rem}
.table td,.table th{vertical-align:top}
.filter-card{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:15px;margin-bottom:20px}

.detail-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    background: #0f172a;
    border-radius: 8px;
    padding: 12px;
    border: 1px solid #1e293b;
}
.detail-column {
    background: #1a2332;
    border-radius: 6px;
    padding: 10px;
}
.detail-column-title {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding-bottom: 6px;
    margin-bottom: 8px;
    border-bottom: 2px solid;
}
.detail-column-title.before {
    color: #ef4444;
    border-color: #ef4444;
}
.detail-column-title.after {
    color: #22c55e;
    border-color: #22c55e;
}
.detail-column-title.deleted {
    color: #ef4444;
    border-color: #ef4444;
}
.detail-column-title.created {
    color: #22c55e;
    border-color: #22c55e;
}

.detail-grid {
    display: grid;
    grid-template-columns: 80px 1fr;
    gap: 2px 8px;
    font-size: 0.7rem;
}
.detail-grid .label {
    color: #94a3b8;
    font-weight: 600;
    padding: 2px 0;
}
.detail-grid .value {
    color: #e2e8f0;
    padding: 2px 0;
    word-break: break-word;
}
.detail-grid .value.changed {
    color: #fbbf24;
    font-weight: bold;
}
.diff-arrow {
    color: #64748b;
    font-size: 0.8rem;
    margin: 0 4px;
}
.diff-remove {
    color: #ef4444;
    text-decoration: line-through;
}
.diff-add {
    color: #22c55e;
    font-weight: bold;
}

.badge-change{background:#f59e0b;color:#000}
.badge-delete{background:#ef4444;color:#fff}
.badge-create{background:#22c55e;color:#fff}
.badge-update{background:#3b82f6;color:#fff}

.no-change {
    color: #64748b;
    font-style: italic;
    font-size: 0.7rem;
}

@media (max-width: 768px) {
    .detail-container {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>
<div class="container-fluid py-4">
<div class="d-flex justify-content-between mb-3 align-items-center">
  <h3><i class="bi bi-shield-check me-2 text-info"></i>RAE Admin Audit Trail</h3>
  <div>
    <span class="text-secondary me-3">Total: <?= $totalRecords ?> records</span>
    <a href="admin_edit.php?logout=1" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </div>
</div>

<!-- Filter Section -->
<div class="filter-card">
  <form method="get" class="row g-3 align-items-end">
    <div class="col-md-3">
      <label class="form-label small">Filter by Date</label>
      <input type="date" name="date" class="form-control form-control-sm bg-dark text-white border-secondary" 
             value="<?= htmlspecialchars($filterDate)?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small">Filter by Action</label>
      <select name="action" class="form-select form-select-sm bg-dark text-white border-secondary">
        <option value="">All Actions</option>
        <option value="UPDATE" <?=$filterAction=='UPDATE'?'selected':''?>>UPDATE</option>
        <option value="DELETE" <?=$filterAction=='DELETE'?'selected':''?>>DELETE</option>
        <option value="CREATE" <?=$filterAction=='CREATE'?'selected':''?>>CREATE</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small">Filter by Admin</label>
      <input type="text" name="admin" class="form-control form-control-sm bg-dark text-white border-secondary" 
             placeholder="Admin name..." value="<?= htmlspecialchars($filterAdmin)?>">
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Apply</button>
    </div>
    <div class="col-md-2">
      <a href="audit_trail.php" class="btn btn-secondary btn-sm w-100"><i class="bi bi-arrow-counterclockwise"></i> Clear</a>
    </div>
  </form>
</div>

<div class="table-responsive">
  <table class="table table-dark table-striped table-hover">
    <thead>
      <tr>
        <th style="width:130px;">Time</th>
        <th style="width:70px;">Action</th>
        <th style="width:90px;">Transaction</th>
        <th style="width:110px;">Admin / IP</th>
        <th style="width:130px;">Reason</th>
        <th style="min-width:450px;">Before → After</th>
      </tr>
    </thead>
    <tbody>
      <?php 
      $hasRows = false;
      while($r=sqlsrv_fetch_array($st,SQLSRV_FETCH_ASSOC)): 
        $hasRows = true;
        $beforeData = json_decode($r['BeforeData'], true);
        $afterData = json_decode($r['AfterData'], true);
      ?>
      <tr>
        <td style="white-space:nowrap;font-size:0.7rem;">
          <?= htmlspecialchars($r['CreatedOn']->format('Y-m-d H:i:s')) ?>
        </td>
        <td>
          <span class="badge <?= 
            $r['ActionType']=='UPDATE' ? 'badge-update' : 
            ($r['ActionType']=='DELETE' ? 'badge-delete' : 
            ($r['ActionType']=='CREATE' ? 'badge-create' : 'bg-secondary')) ?>">
            <?= htmlspecialchars($r['ActionType'])?>
          </span>
        </td>
        <td>
          <small>#<?= htmlspecialchars($r['TransactionLogId'])?></small>
          <?php if($r['InventoryId']): ?>
          <br><small class="text-secondary">Inv: #<?= htmlspecialchars($r['InventoryId'])?></small>
          <?php endif; ?>
        </td>
        <td>
          <div><strong><?= htmlspecialchars($r['AdminUser'])?></strong></div>
          <small class="text-secondary"><?= htmlspecialchars($r['IpAddress'])?></small>
        </td>
        <td style="max-width:130px;font-size:0.75rem;">
          <?= htmlspecialchars($r['Reason'])?>
        </td>
        <td>
          <?php 
          if ($r['ActionType'] === 'DELETE'): 
          ?>
            <!-- DELETE: Show Before data only -->
            <div class="detail-container">
              <div class="detail-column">
                <div class="detail-column-title deleted">🗑️ DELETED RECORD</div>
                <?php echo formatRecordDisplay($beforeData, $deptMap); ?>
              </div>
              <div class="detail-column" style="background:#1a1a2e; border:1px dashed #334155;">
                <div class="detail-column-title" style="color:#64748b;border-color:#64748b;">✖️ REMOVED</div>
                <div class="text-secondary" style="font-size:0.7rem;padding:10px 0;text-align:center;">
                  <i class="bi bi-trash3 text-danger"></i> This record was permanently deleted
                </div>
              </div>
            </div>
          <?php elseif ($r['ActionType'] === 'CREATE'): 
          ?>
            <!-- CREATE: Show After data only -->
            <div class="detail-container">
              <div class="detail-column" style="background:#1a2e1a; border:1px dashed #334155;">
                <div class="detail-column-title" style="color:#64748b;border-color:#64748b;">✖️ NEW</div>
                <div class="text-secondary" style="font-size:0.7rem;padding:10px 0;text-align:center;">
                  <i class="bi bi-plus-circle text-success"></i> This record was created
                </div>
              </div>
              <div class="detail-column">
                <div class="detail-column-title created">✅ CREATED RECORD</div>
                <?php echo formatRecordDisplay($afterData, $deptMap); ?>
              </div>
            </div>
          <?php elseif ($r['ActionType'] === 'UPDATE'): 
          ?>
            <!-- UPDATE: Show Before and After side by side -->
            <div class="detail-container">
              <div class="detail-column">
                <div class="detail-column-title before">📄 BEFORE</div>
                <?php echo formatRecordDisplay($beforeData, $deptMap); ?>
              </div>
              <div class="detail-column">
                <div class="detail-column-title after">📄 AFTER</div>
                <?php echo formatRecordDisplay($afterData, $deptMap); ?>
              </div>
            </div>
          <?php else: ?>
            <div class="text-secondary small">No detail data available</div>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
      
      <?php if(!$hasRows): ?>
      <tr><td colspan="6" class="text-center py-5 text-muted">No audit records found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="mt-3">
  <small class="text-secondary">
    <i class="bi bi-info-circle"></i> The audit trail captures all changes including date, time, department, weight, and product information.
  </small>
</div>
</div>

<?php
// ============================================================
// HELPER FUNCTIONS
// ============================================================

function formatRecordDisplay($data, $deptMap) {
    if (!$data) return '<span class="text-secondary">No data</span>';
    
    $html = '<div class="detail-grid">';
    
    // Transaction Date/Time
    if (isset($data['TransactionCreatedOn'])) {
        $html .= '<span class="label">📅 Date/Time:</span>';
        $html .= '<span class="value">' . htmlspecialchars($data['TransactionCreatedOn']) . '</span>';
    }
    
    // Source Department
    if (isset($data['SourceDepartmentId'])) {
        $deptName = $deptMap[$data['SourceDepartmentId']] ?? 'Unknown (ID: ' . $data['SourceDepartmentId'] . ')';
        $html .= '<span class="label">📤 Source:</span>';
        $html .= '<span class="value">' . htmlspecialchars($deptName) . '</span>';
    }
    
    // Destination Department
    if (isset($data['DestinationDepartmentId'])) {
        $deptName = $deptMap[$data['DestinationDepartmentId']] ?? 'Unknown (ID: ' . $data['DestinationDepartmentId'] . ')';
        $html .= '<span class="label">📥 Destination:</span>';
        $html .= '<span class="value">' . htmlspecialchars($deptName) . '</span>';
    }
    
    // Weight
    if (isset($data['TransactionWeight'])) {
        $html .= '<span class="label">⚖️ Weight:</span>';
        $html .= '<span class="value">' . number_format($data['TransactionWeight'], 3) . ' g</span>';
    }
    
    // Product Name
    if (isset($data['ProductName'])) {
        $html .= '<span class="label">🏷️ Product:</span>';
        $html .= '<span class="value">' . htmlspecialchars($data['ProductName']) . '</span>';
    }
    
    // Remark
    if (isset($data['TransactionRemark']) && !empty($data['TransactionRemark'])) {
        $html .= '<span class="label">💬 Remark:</span>';
        $html .= '<span class="value">' . htmlspecialchars($data['TransactionRemark']) . '</span>';
    }
    
    // User
    if (isset($data['TransactionUser'])) {
        $html .= '<span class="label">👤 Operator:</span>';
        $html .= '<span class="value">' . htmlspecialchars($data['TransactionUser']) . '</span>';
    }
    
    // Inventory Weight (if different from TransactionWeight)
    if (isset($data['InventoryWeight']) && isset($data['TransactionWeight']) && $data['InventoryWeight'] != $data['TransactionWeight']) {
        $html .= '<span class="label">📦 Inv Weight:</span>';
        $html .= '<span class="value">' . number_format($data['InventoryWeight'], 3) . ' g</span>';
    }
    
    $html .= '</div>';
    return $html;
}
?>

</body>
</html>