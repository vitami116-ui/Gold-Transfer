<?php
/**
 * melting Production Report - Independent Shift Summaries
 */

// 1. Connection
$serverName = "localhost\SQLEXPRESS";
$connectionOptions = array("Database" => "21kEuroStar", "Uid" => "sa", "PWD" => "123456", "CharacterSet" => "UTF-8");
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) { die(print_r(sqlsrv_errors(), true)); }

// 2. DYNAMIC VARIABLES
$viewDate    = $_GET['date']  ?? date('Y-m-d');
$shiftHour   = $_GET['shift_h'] ?? 13; 
$shiftMinute = $_GET['shift_m'] ?? 0;
$meltingId     = 12100; 
$safe2Id     = 10085; 

$cutoffMinutes = ($shiftHour * 60) + $shiftMinute;

function executeQuery($conn, $sql, $params = []) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }
    return $stmt;
}

/**
 * Enhanced Helper: Returns Weight AND Count for a specific shift
 */
function getShiftStats($conn, $date, $src, $dest, $isShift2, $cutoff) {
    $operator = $isShift2 ? ">=" : "<";
    $sql = "SELECT SUM(TL.Weight) as TotalWeight, COUNT(TL.Id) as TransCount
            FROM [TransactionLogs] TL 
            JOIN [Inventories] I ON TL.InventoryId = I.Id 
            WHERE CAST(I.[CreatedOn] AS DATE) = ? 
            AND TL.SourceDepartmentId = ? 
            AND TL.DestinationDepartmentId = ?
            AND (DATEPART(HOUR, I.[CreatedOn]) * 60 + DATEPART(MINUTE, I.[CreatedOn])) $operator ?";
            
    $stmt = sqlsrv_query($conn, $sql, [$date, $src, $dest, $cutoff]);
    $res = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return [
        'weight' => $res['TotalWeight'] ?? 0,
        'count'  => $res['TransCount'] ?? 0
    ];
}

// 3. CALCULATE SHIFT 1 ONLY
$s1_Out_Data = getShiftStats($conn, $viewDate, $meltingId, $safe2Id, false, $cutoffMinutes);
$s1_In_Data  = getShiftStats($conn, $viewDate, $safe2Id, $meltingId, false, $cutoffMinutes);
$s1_Wip      = $s1_Out_Data['weight'] - $s1_In_Data['weight'];

// 4. CALCULATE SHIFT 2 ONLY
$s2_Out_Data = getShiftStats($conn, $viewDate, $meltingId, $safe2Id, true, $cutoffMinutes);
$s2_In_Data  = getShiftStats($conn, $viewDate, $safe2Id, $meltingId, true, $cutoffMinutes);
$s2_Wip      = $s2_Out_Data['weight'] - $s2_In_Data['weight'];

// 5. GRAND TOTALS (THE ONLY PLACE THEY MIX)
$totalOut = $s1_Out_Data['weight'] + $s2_Out_Data['weight'];
$totalIn  = $s1_In_Data['weight']  + $s2_In_Data['weight'];
$wip      = $totalOut - $totalIn;
$hasVariance = (abs($wip) > 0.0001); 

// 6. LISTING SQL
$sqlList = "SELECT TL.Id AS TransactionLogId, TL.InventoryId, I.CreatedOn as InvDate, TL.SourceDepartmentId, TL.Weight, I.ProductName, 
            D1.DepartmentName as SrcName, D2.DepartmentName as DestName 
            FROM [TransactionLogs] TL 
            INNER JOIN [Inventories] I ON TL.InventoryId = I.Id 
            INNER JOIN [Departments] D1 ON TL.SourceDepartmentId = D1.Id 
            INNER JOIN [Departments] D2 ON TL.DestinationDepartmentId = D2.Id 
            WHERE CAST(I.[CreatedOn] AS DATE) = ? 
            AND ((TL.SourceDepartmentId = ? AND TL.DestinationDepartmentId = ?) 
              OR (TL.SourceDepartmentId = ? AND TL.DestinationDepartmentId = ?)) 
            ORDER BY I.CreatedOn ASC";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>melting Production Report</title>
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
        .badge-out { background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .badge-in { background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .table-custom { background: #1e293b; border-radius: 15px; overflow: hidden; }
        .table-custom thead { background: #334155; }
        .shift-divider { background: rgba(255, 255, 255, 0.03); border-top: 1px dashed rgba(255,255,255,0.1); }
    </style>
</head>
<body>

<div class="container pb-5">
    <div class="dashboard-header mb-5 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-0">PRODUCTION LOG <i class="bi bi-cpu mx-2 text-primary"></i></h4>
            <small class="text-secondary">Shift Cut-off: <strong><?= sprintf('%02d:%02d', $shiftHour, $shiftMinute) ?></strong></small>
        </div>
        <div class="d-flex gap-2">
            <input type="date" id="datePicker" class="form-control w-auto bg-dark text-white border-0 shadow-sm" value="<?= $viewDate ?>">
            <div class="input-group w-auto">
                <select id="hourPicker" class="form-select bg-dark text-white border-0"><?php for($i=0;$i<24;$i++) echo "<option value='$i' ".($shiftHour==$i?'selected':'').">".sprintf('%02d',$i)."</option>"; ?></select>
                <select id="minPicker" class="form-select bg-dark text-white border-0"><?php for($i=0;$i<60;$i+=1) echo "<option value='$i' ".($shiftMinute==$i?'selected':'').">".sprintf('%02d',$i)."</option>"; ?></select>
            </div>
            <button onclick="updateReport()" class="btn btn-primary shadow-sm">Apply</button>
        </div>
    </div>

    <div class="mb-4">
        <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-arrow-left"></i> Portal</a>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card" style="border-left-color: #3b82f6;">
                <span class="text-secondary small text-uppercase fw-bold">Shift 1 Summary</span>
                <div class="d-flex justify-content-between align-items-end mt-2">
                    <div>
                        <h2 class="text-out fw-bold mb-0"><?= number_format($s1_Out_Data['weight'], 3) ?>g</h2>
                        <span class="badge badge-out small"><?= $s1_Out_Data['count'] ?> OUT</span>
                    </div>
                    <div class="text-end">
                        <h2 class="text-in fw-bold mb-0"><?= number_format($s1_In_Data['weight'], 3) ?>g</h2>
                        <span class="badge badge-in small"><?= $s1_In_Data['count'] ?> IN</span>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top border-secondary opacity-50 small d-flex justify-content-between">
                    <span>Shift 1 WIP:</span>
                    <span class="fw-bold"><?= number_format($s1_Wip, 3) ?>g</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card" style="border-left-color: #fbbf24;">
                <span class="text-secondary small text-uppercase fw-bold">Shift 2 Summary</span>
                <div class="d-flex justify-content-between align-items-end mt-2">
                    <div>
                        <h2 class="text-out fw-bold mb-0"><?= number_format($s2_Out_Data['weight'], 3) ?>g</h2>
                        <span class="badge badge-out small"><?= $s2_Out_Data['count'] ?> OUT</span>
                    </div>
                    <div class="text-end">
                        <h2 class="text-in fw-bold mb-0"><?= number_format($s2_In_Data['weight'], 3) ?>g</h2>
                        <span class="badge badge-in small"><?= $s2_In_Data['count'] ?> IN</span>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top border-secondary opacity-50 small d-flex justify-content-between">
                    <span>Shift 2 WIP:</span>
                    <span class="fw-bold"><?= number_format($s2_Wip, 3) ?>g</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card" style="border-left-color: <?= $hasVariance ? '#ef4444' : '#22c55e' ?>;">
                <span class="text-secondary small text-uppercase fw-bold">Final Daily Balance</span>
                <h2 class="<?= $hasVariance ? 'text-variance' : 'text-balanced' ?> fw-bold mt-2">
                    <?= number_format($wip, 3) ?>g
                </h2>
                <div class="progress mt-3" style="height: 6px; background: #334155;">
                    <?php $perc = ($totalOut > 0) ? ($totalIn / $totalOut) * 100 : 0; ?>
                    <div class="progress-bar bg-success" style="width: <?= $perc ?>%"></div>
                </div>
                <div class="d-flex justify-content-between mt-2 small opacity-50">
                    <span>Recovery: <?= number_format($perc, 2) ?>%</span>
                    <span>Loss: <?= number_format(100 - $perc, 2) ?>%</span>
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
                $stmtList = executeQuery($conn, $sqlList, [$viewDate, $safe2Id, $meltingId, $meltingId, $safe2Id]);
                $hasRows = false; $shift2HeaderShown = false;

                while ($row = sqlsrv_fetch_array($stmtList, SQLSRV_FETCH_ASSOC)): 
                    $hasRows = true;
                    $timeObj = $row['InvDate'];
                    $rowMinutes = ((int)$timeObj->format('H') * 60) + (int)$timeObj->format('i');
                    $isOut = ($row['SourceDepartmentId'] == $safe2Id);

                    if ($rowMinutes >= $cutoffMinutes && !$shift2HeaderShown): 
                        $shift2HeaderShown = true;
                ?>
                    <tr class="shift-divider text-warning">
                        <td colspan="4" class="text-center py-2 small fw-bold">--- SHIFT 2 LOGS STARTING <?= sprintf('%02d:%02d', $shiftHour, $shiftMinute) ?> ---</td>
                    </tr>
                <?php endif; ?>

                    <tr>
                        <td class="ps-4 small opacity-50"><?= $timeObj->format('H:i:s') ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($row['ProductName'] ?? 'N/A') ?></div>
                            <div class="badge <?= $isOut ? 'badge-out' : 'badge-in' ?> px-2" style="font-size: 0.65rem;">
                                <?= $isOut ? 'SAFE 2 OUT' : 'SAFE 2 IN' ?>
                            </div>
                        </td>
                        <td class="small opacity-50"><?= $row['SrcName'] ?> <i class="bi bi-chevron-right mx-1"></i> <?= $row['DestName'] ?></td>
                        <td class="text-end pe-4 fw-bold <?= $isOut ? 'text-out' : 'text-in' ?>">
                            <?= number_format($row['Weight'], 3) ?>g
                        </td>
                        <td class="text-end pe-3"><a class="btn btn-outline-warning btn-sm" href="admin_edit.php?id=<?= (int)$row['TransactionLogId'] ?>&return=<?= urlencode(basename(__FILE__)) ?>&date=<?= urlencode($viewDate) ?>">Edit</a></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function updateReport() {
    const d = document.getElementById('datePicker').value;
    const h = document.getElementById('hourPicker').value;
    const m = document.getElementById('minPicker').value;
    window.location.href = `?date=${d}&shift_h=${h}&shift_m=${m}`;
}
</script>
</body>
</html>