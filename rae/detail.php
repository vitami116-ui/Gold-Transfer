<?php
// 1. Connection & Configuration
$serverName = "localhost\SQLEXPRESS";
$connectionOptions = array("Database" => "21kEuroStar", "Uid" => "sa", "PWD" => "123456", "CharacterSet" => "UTF-8");
$conn = sqlsrv_connect($serverName, $connectionOptions);

// 2. Date and Department Handling
$viewDate = $_GET['date'] ?? date('Y-m-d');
$focusDeptId = $_GET['deptId'] ?? null;

// Calculate Nav Dates
$prevDate = date('Y-m-d', strtotime($viewDate . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($viewDate . ' +1 day'));
$todayDate = date('Y-m-d');

// 3. Fetch Department Names
$depts = [];
$deptStmt = sqlsrv_query($conn, "SELECT Id, DepartmentName FROM departments");
while ($r = sqlsrv_fetch_array($deptStmt, SQLSRV_FETCH_ASSOC)) { 
    $depts[$r['Id']] = $r['DepartmentName']; 
}

// 4. Fetch Logs with IsStone property
$params = [$viewDate];
$where = "WHERE CAST(I.[CreatedOn] AS DATE) = ?";

if ($focusDeptId) {
    $where .= " AND (TL.SourceDepartmentId = ? OR TL.DestinationDepartmentId = ?)";
    array_push($params, $focusDeptId, $focusDeptId);
}

$sql = "SELECT TL.[Id], I.[CreatedOn] as MoveTime, TL.[SourceDepartmentId], TL.[DestinationDepartmentId], 
               TL.[Weight], TL.[InventoryId], I.[ProductName], I.[IsStone]
        FROM [TransactionLogs] TL
        LEFT JOIN [Inventories] I ON TL.InventoryId = I.Id
        $where ORDER BY I.[CreatedOn] DESC";

$stmt = sqlsrv_query($conn, $sql, $params);

// 5. Calculate Loss Totals & Prepare Rows
$totalIn = 0;
$totalOut = 0;
$rows = [];
while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    if ($focusDeptId) {
        if ($r['DestinationDepartmentId'] == $focusDeptId) $totalIn += $r['Weight'];
        if ($r['SourceDepartmentId'] == $focusDeptId) $totalOut += $r['Weight'];
    }
    $rows[] = $r;
}
$netLoss = $totalIn - $totalOut;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Flow & Loss Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .nav-card { border-radius: 12px; border: none; }
        .arrow-in { color: #198754; font-size: 1.5rem; font-weight: bold; }
        .arrow-out { color: #dc3545; font-size: 1.5rem; font-weight: bold; }
        .stone-highlight { background-color: #fffbeb !important; border-left: 4px solid #f59e0b !important; }
        .bell-icon { color: #f59e0b; animation: ring 2s ease-in-out infinite; display: inline-block; }
        @keyframes ring { 0% {transform:rotate(0)} 10% {transform:rotate(15deg)} 20% {transform:rotate(-15deg)} 30% {transform:rotate(0)} }
        .reschedule-tag { font-size: 0.72rem; color: #92400e; font-weight: 600; }
        .table thead th { background-color: #212529; color: white; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
    </style>
</head>
<body>

<div class="container-fluid py-4 px-4">
    
    <div class="card nav-card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <h5 class="m-0 fw-bold"><i class="bi bi-intersect me-2 text-primary"></i>Factory Monitor</h5>
                </div>
                
                <div class="col-md-5 d-flex justify-content-center align-items-center gap-2">
                    <a href="?date=<?= $prevDate ?>&deptId=<?= $focusDeptId ?>" class="btn btn-light btn-sm border">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    
                    <form class="d-flex m-0 align-items-center">
                        <input type="hidden" name="deptId" value="<?= $focusDeptId ?>">
                        <input type="date" name="date" class="form-control form-control-sm text-center fw-bold shadow-none" 
                               value="<?= $viewDate ?>" onchange="this.form.submit()" style="width: 160px;">
                    </form>

                    <a href="?date=<?= $nextDate ?>&deptId=<?= $focusDeptId ?>" class="btn btn-light btn-sm border">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    
                    <?php if ($viewDate != $todayDate): ?>
                        <a href="?date=<?= $todayDate ?>&deptId=<?= $focusDeptId ?>" class="btn btn-primary btn-sm ms-2">Today</a>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <form class="m-0">
                        <input type="hidden" name="date" value="<?= $viewDate ?>">
                        <select name="deptId" class="form-select form-select-sm fw-bold border-primary shadow-none" onchange="this.form.submit()">
                            <option value="">-- All Departments --</option>
                            <?php foreach($depts as $id => $name): ?>
                                <option value="<?= $id ?>" <?= $focusDeptId == $id ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($focusDeptId): ?>
    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-3 border-top border-success border-4">
                <small class="text-muted fw-bold">TOTAL INBOUND (←)</small>
                <h2 class="text-success m-0 fw-bold"><?= number_format($totalIn, 3) ?>g</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-3 border-top border-danger border-4">
                <small class="text-muted fw-bold">TOTAL OUTBOUND (→)</small>
                <h2 class="text-danger m-0 fw-bold"><?= number_format($totalOut, 3) ?>g</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-3 bg-dark text-white">
                <small class="text-secondary fw-bold">NET DEPARTMENT LOSS</small>
                <h2 class="m-0 fw-bold"><?= number_format($netLoss, 3) ?>g</h2>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center" width="80">Flow</th>
                        <th>Time / Status</th>
                        <th>Product / ID</th>
                        <th>Logistics Route</th>
                        <th class="text-end px-4">Weight</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): 
                        $isStone = ($r['IsStone'] == 1);
                        
                        // Arrow Logic
                        $arrow = '<span class="text-muted opacity-25">—</span>';
                        if ($focusDeptId) {
                            if ($r['DestinationDepartmentId'] == $focusDeptId) $arrow = '<span class="arrow-in">←</span>';
                            if ($r['SourceDepartmentId'] == $focusDeptId) $arrow = '<span class="arrow-out">→</span>';
                        }
                    ?>
                    <tr class="<?= $isStone ? 'stone-highlight' : '' ?>">
                        <td class="text-center"><?= $arrow ?></td>
                        <td>
                            <div class="fw-bold"><?= $r['MoveTime']->format('H:i') ?></div>
                            <?php if ($isStone): ?>
                                <div class="reschedule-tag">
                                    <i class="bi bi-bell-fill bell-icon"></i> 
                                    Rescheduled: 06:00
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">
                                <?= htmlspecialchars($r['ProductName'] ?? 'Item Deleted') ?>
                                <?= $isStone ? '💎' : '' ?>
                            </div>
                            <small class="text-muted fw-bold">ID: #<?= $r['InventoryId'] ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border small fw-normal"><?= $depts[$r['SourceDepartmentId']] ?? 'Vendor' ?></span>
                            <i class="bi bi-arrow-right mx-1 text-secondary"></i>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle small fw-bold"><?= $depts[$r['DestinationDepartmentId']] ?? 'Customer' ?></span>
                        </td>
                        <td class="text-end px-4 fw-bold font-monospace text-dark" style="font-size: 1.1rem;">
                            <?= number_format($r['Weight'], 3) ?>g
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No transactions recorded for this day.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>