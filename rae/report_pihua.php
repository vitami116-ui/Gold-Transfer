<?php
// 1. Connection
$serverName = "localhost\SQLEXPRESS";
$connectionOptions = array("Database" => "21kEuroStar", "Uid" => "sa", "PWD" => "123456", "CharacterSet" => "UTF-8");
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) { die(print_r(sqlsrv_errors(), true)); }

// 2. Variables
$viewDate = $_GET['date'] ?? date('Y-m-d');
$pihuaId = 10093; 
$safe2Id = 10085; 

function executeQuery($conn, $sql, $params = []) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }
    return $stmt;
}

// 3. TOTALS
$sqlOut = "SELECT SUM(TL.Weight) as TotalOut FROM [TransactionLogs] TL JOIN [Inventories] I ON TL.InventoryId = I.Id WHERE CAST(I.[CreatedOn] AS DATE) = ? AND TL.SourceDepartmentId = ? AND TL.DestinationDepartmentId = ?";
$stmtOut = executeQuery($conn, $sqlOut, [$viewDate, $safe2Id, $pihuaId]);
$totalOut = sqlsrv_fetch_array($stmtOut, SQLSRV_FETCH_ASSOC)['TotalOut'] ?? 0;

$sqlIn = "SELECT SUM(TL.Weight) as TotalIn FROM [TransactionLogs] TL JOIN [Inventories] I ON TL.InventoryId = I.Id WHERE CAST(I.[CreatedOn] AS DATE) = ? AND TL.SourceDepartmentId = ? AND TL.DestinationDepartmentId = ?";
$stmtIn = executeQuery($conn, $sqlIn, [$viewDate, $pihuaId, $safe2Id]);
$totalIn = sqlsrv_fetch_array($stmtIn, SQLSRV_FETCH_ASSOC)['TotalIn'] ?? 0;

// 4. WIP & VARIANCE LOGIC
$wip = $totalOut - $totalIn;
$lossPercent = ($totalOut > 0) ? ($wip / $totalOut) * 100 : 0;
$hasVariance = (abs($wip) > 0.0001); 

// 5. LISTING SQL (Restored for the loop below)
$sqlList = "SELECT TL.Id AS TransactionLogId, TL.InventoryId, I.CreatedOn as InvDate, TL.SourceDepartmentId, TL.Weight, I.ProductName, D1.DepartmentName as SrcName, D2.DepartmentName as DestName FROM [TransactionLogs] TL INNER JOIN [Inventories] I ON TL.InventoryId = I.Id INNER JOIN [Departments] D1 ON TL.SourceDepartmentId = D1.Id INNER JOIN [Departments] D2 ON TL.DestinationDepartmentId = D2.Id WHERE CAST(I.[CreatedOn] AS DATE) = ? AND ((TL.SourceDepartmentId = ? AND TL.DestinationDepartmentId = ?) OR (TL.SourceDepartmentId = ? AND TL.DestinationDepartmentId = ?)) ORDER BY I.CreatedOn ASC";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pihua Production Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #0f172a; color: white; font-family: 'Inter', sans-serif; }
        .dashboard-header { background: rgba(30, 41, 59, 0.8); padding: 20px; border-radius: 0 0 20px 20px; border: 1px solid rgba(255,255,255,0.05); }
        .stat-card { background: #1e293b; border-radius: 15px; padding: 20px; border-left: 6px solid; transition: 0.2s; height: 100%; }
        
        .text-out { color: #3b82f6 !important; } 
        .text-in { color: #fbbf24 !important; }  
        .text-variance { color: #ef4444 !important; } 
        .text-balanced { color: #22c55e !important; } 
        
        .badge-out { background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); }
        .badge-in { background: rgba(251, 191, 36, 0.15); border: 1px solid rgba(251, 191, 36, 0.3); }

        .table-custom { background: #1e293b; border-radius: 15px; overflow: hidden; }
        .table-custom thead { background: #334155; }
    </style>
</head>
<body>

<div class="container pb-5">
    <div class="dashboard-header mb-5 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-0">SAFE 2 (10085) <i class="bi bi-arrow-left-right mx-2 text-muted"></i> PIHUA (10093)</h4>
            <small class="text-secondary">Inventory Movement Tracking</small>
        </div>
        <input type="date" class="form-control w-auto bg-dark text-white border-0 shadow-sm" 
               value="<?= $viewDate ?>" onchange="location.href='?date='+this.value">
    </div>

        <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left"></i> Portal
        </a>
        

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card" style="border-left-color: #3b82f6;">
                <span class="text-secondary small text-uppercase fw-bold">Total Out (Safe 2 → Pihua)</span>
                <h2 class="text-out fw-bold mt-2"><?= number_format($totalOut, 3) ?>g</h2>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card" style="border-left-color: #fbbf24;">
                <span class="text-secondary small text-uppercase fw-bold">Total In (Pihua → Safe 2)</span>
                <h2 class="text-in fw-bold mt-2"><?= number_format($totalIn, 3) ?>g</h2>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card" style="border-left-color: <?= $hasVariance ? '#ef4444' : '#22c55e' ?>;">
                <span class="text-secondary small text-uppercase fw-bold">WIP / Loss Balance</span>
                <h2 class="<?= $hasVariance ? 'text-variance' : 'text-balanced' ?> fw-bold mt-2">
                    <?= number_format($wip, 3) ?>g
                </h2>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="badge <?= $hasVariance ? 'bg-danger' : 'bg-success' ?> text-white">
                        <?php 
                            if ($wip > 0) echo number_format($lossPercent, 2) . '% LOSS';
                            elseif ($wip < 0) echo 'WEIGHT GAIN';
                            else echo 'BALANCED';
                        ?>
                    </span>
                    <span class="small opacity-50">Prod. Variance</span>
                </div>
            </div>
        </div>
    </div>

    <div class="table-custom p-1 shadow-lg">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead>
                <tr class="text-secondary small uppercase">
                    <th class="ps-4">Inv Time</th>
                    <th>Product Details</th>
                    <th>Route</th>
                    <th class="text-end pe-4">Weight</th>
                <th class="text-end">Admin</th></tr>
            </thead>
            <tbody>
                <?php 
                $stmtList = executeQuery($conn, $sqlList, [$viewDate, $safe2Id, $pihuaId, $pihuaId, $safe2Id]);
                $hasRows = false;
                while ($row = sqlsrv_fetch_array($stmtList, SQLSRV_FETCH_ASSOC)): 
                    $hasRows = true;
                    $isOut = ($row['SourceDepartmentId'] == $safe2Id); 
                ?>
                    <tr>
                        <td class="ps-4 small opacity-50"><?= $row['InvDate']->format('H:i:s') ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($row['ProductName'] ?? 'NO PRODUCT NAME') ?></div>
                            <div class="badge <?= $isOut ? 'badge-out text-out' : 'badge-in text-in' ?> px-3" style="font-size: 0.7rem;">
                                <?= $isOut ? 'SAFE 2 OUT' : 'SAFE 2 IN' ?>
                            </div>
                        </td>
                        <td class="small opacity-50">
                            <?= $row['SrcName'] ?> <i class="bi bi-arrow-right mx-1"></i> <?= $row['DestName'] ?>
                        </td>
                        <td class="text-end pe-4 fw-bold <?= $isOut ? 'text-out' : 'text-in' ?>">
                            <?= number_format($row['Weight'], 3) ?>g
                        </td>
                        <td class="text-end pe-3"><a class="btn btn-outline-warning btn-sm" href="admin_edit.php?id=<?= (int)$row['TransactionLogId'] ?>&return=<?= urlencode(basename(__FILE__)) ?>&date=<?= urlencode($viewDate) ?>">Edit</a></td>
                    </tr>
                <?php endwhile; ?>
                
                <?php if(!$hasRows): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">No records found for <?= $viewDate ?>.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>