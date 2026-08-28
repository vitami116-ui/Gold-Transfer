<?php
/**
 * Production Report - With Date Navigation & Smart Settings
 */

// ===============================
// 1. DATABASE CONNECTIONS
// ===============================
$connMySQL = new mysqli("localhost", "root", "", "milano");
$serverName = "localhost\SQLEXPRESS";
$connectionOptions = ["Database" => "21kEuroStar", "Uid" => "sa", "PWD" => "123456", "CharacterSet" => "UTF-8"];
$connMSSQL = sqlsrv_connect($serverName, $connectionOptions);

if (!$connMSSQL) { die(print_r(sqlsrv_errors(), true)); }

// ===============================
// 2. DATE NAVIGATION LOGIC
// ===============================
$sourceId = $_GET['source'] ?? null;
$destId   = $_GET['dest'] ?? null;
$viewDate = $_GET['date'] ?? date('Y-m-d');

if (!$sourceId || !$destId) {
    die("❌ ERROR: Source and Destination IDs required.");
}

// Calculate Prev/Next for buttons
$prevDate = date('Y-m-d', strtotime($viewDate . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($viewDate . ' +1 day'));
$today    = date('Y-m-d');

// ===============================
// 3. SMART SHIFT SETTINGS (MySQL)
// Try specific date -> fallback to the latest setting for this route
// ===============================
$sqlShift = "SELECT shifthour, shiftminute FROM userreportsettings 
             WHERE sourcedepartmentid = ? AND destinationdepartmentid = ?
             ORDER BY ABS(DATEDIFF(viewdate, ?)) ASC LIMIT 1";

$stmt = $connMySQL->prepare($sqlShift);
$stmt->bind_param("iis", $sourceId, $destId, $viewDate);
$stmt->execute();
$shiftRes = $stmt->get_result()->fetch_assoc();

$shiftHour   = $shiftRes['shifthour'] ?? 12;
$shiftMinute = $shiftRes['shiftminute'] ?? 0;
$cutoffMinutes = ($shiftHour * 60) + $shiftMinute;

// ===============================
// 4. MSSQL DATA FUNCTIONS
// ===============================
function getShiftStats($conn, $date, $src, $dest, $isShift2, $cutoff) {
    $operator = $isShift2 ? ">=" : "<";
    $sql = "SELECT SUM(TL.Weight) AS TotalWeight, COUNT(TL.Id) AS TransCount
            FROM [TransactionLogs] TL
            INNER JOIN [Inventories] I ON TL.InventoryId = I.Id
            WHERE CAST(I.CreatedOn AS DATE) = ?
            AND TL.SourceDepartmentId = ? AND TL.DestinationDepartmentId = ?
            AND (DATEPART(HOUR, I.CreatedOn) * 60 + DATEPART(MINUTE, I.CreatedOn)) $operator ?";
    $params = [$date, $src, $dest, $cutoff];
    $stmt = sqlsrv_query($conn, $sql, $params);
    $res = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return ['weight' => $res['TotalWeight'] ?? 0, 'count' => $res['TransCount'] ?? 0];
}

$s1_Out = getShiftStats($connMSSQL, $viewDate, $sourceId, $destId, false, $cutoffMinutes);
$s2_Out = getShiftStats($connMSSQL, $viewDate, $sourceId, $destId, true, $cutoffMinutes);
$s1_In  = getShiftStats($connMSSQL, $viewDate, $destId, $sourceId, false, $cutoffMinutes);
$s2_In  = getShiftStats($connMSSQL, $viewDate, $destId, $sourceId, true, $cutoffMinutes);

$wip = ($s1_Out['weight'] + $s2_Out['weight']) - ($s1_In['weight'] + $s2_In['weight']);

// List Fetch
$sqlList = "SELECT I.CreatedOn, TL.SourceDepartmentId, TL.Weight, I.ProductName, D1.DepartmentName AS SrcName, D2.DepartmentName AS DestName
            FROM [TransactionLogs] TL
            INNER JOIN [Inventories] I ON TL.InventoryId = I.Id
            INNER JOIN [Departments] D1 ON TL.SourceDepartmentId = D1.Id
            INNER JOIN [Departments] D2 ON TL.DestinationDepartmentId = D2.Id
            WHERE CAST(I.CreatedOn AS DATE) = ?
            AND ((TL.SourceDepartmentId = ? AND TL.DestinationDepartmentId = ?) OR (TL.SourceDepartmentId = ? AND TL.DestinationDepartmentId = ?))
            ORDER BY I.CreatedOn ASC";
$resultList = sqlsrv_query($connMSSQL, $sqlList, [$viewDate, $sourceId, $destId, $destId, $sourceId]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Production Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #0f172a; color: #e2e8f0; }
        .nav-container { background: #1e293b; border-bottom: 1px solid #334155; padding: 15px 0; margin-bottom: 30px; }
        .card-custom { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 20px; }
        .btn-nav { background: #334155; color: white; border: none; }
        .btn-nav:hover { background: #475569; color: white; }
    </style>
</head>
<body>

<!-- NAVIGATION BAR -->
<div class="nav-container sticky-top shadow">
    
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-4">
                <h5 class="mb-0 fw-bold"><i class="bi bi-bar-chart-line text-primary me-2"></i>Production Report</h5>
            </div>
            <div class="col-md-8">
                <div class="d-flex justify-content-md-end align-items-center gap-2">
                    <!-- Prev Button -->
                    <a href="?date=<?= $prevDate ?>&source=<?= $sourceId ?>&dest=<?= $destId ?>" class="btn btn-nav btn-sm">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    
                    <!-- Date Picker -->
                    <input type="date" id="dateJump" class="form-control form-control-sm bg-dark text-white border-secondary w-auto" 
                           value="<?= $viewDate ?>" onchange="changeDate(this.value)">
                    
                    <!-- Next Button -->
                    <a href="?date=<?= $nextDate ?>&source=<?= $sourceId ?>&dest=<?= $destId ?>" class="btn btn-nav btn-sm">
                        <i class="bi bi-chevron-right"></i>
                    </a>

                    <a href="?date=<?= $today ?>&source=<?= $sourceId ?>&dest=<?= $destId ?>" class="btn btn-primary btn-sm ms-2">Today</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Header Info -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="p-3 border-start border-primary border-4 bg-dark bg-opacity-25 rounded">
                <span class="text-secondary small">ROUTE:</span> <strong><?= $sourceId ?> → <?= $destId ?></strong>
                <span class="mx-3 text-secondary">|</span>
<!-- Inside your navigation container -->
<form action="save_settings.php" method="POST" class="d-flex gap-2 align-items-center">
    <!-- Keep the current context hidden -->
    <input type="hidden" name="date" value="<?= $viewDate ?>">
    <input type="hidden" name="source" value="<?= $sourceId ?>">
    <input type="hidden" name="dest" value="<?= $destId ?>">

    <div class="input-group input-group-sm">
        <span class="input-group-text bg-dark text-secondary border-secondary">Shift Start</span>
        <select name="hour" class="form-select bg-dark text-white border-secondary">
            <?php for($h=0; $h<24; $h++): ?>
                <option value="<?= $h ?>" <?= ($shiftHour == $h ? 'selected' : '') ?>><?= sprintf("%02d", $h) ?></option>
            <?php endfor; ?>
        </select>
        <select name="minute" class="form-select bg-dark text-white border-secondary">
            <?php for($m=0; $m<60; $m+=15): ?>
                <option value="<?= $m ?>" <?= ($shiftMinute == $m ? 'selected' : '') ?>><?= sprintf("%02d", $m) ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn btn-success btn-sm">
            <i class="bi bi-save"></i> Save
        </button>
    </div>
</form>
            </div>
        </div>
    </div>

    <!-- Summary Row -->
    <div class="row g-4 mb-5 text-center">
        <div class="col-md-4">
            <div class="card-custom">
                <div class="small text-secondary mb-1">TOTAL OUTBOUND</div>
                <h3 class="text-info fw-bold mb-0"><?= number_format($s1_Out['weight'] + $s2_Out['weight'], 3) ?>g</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-custom">
                <div class="small text-secondary mb-1">TOTAL INBOUND</div>
                <h3 class="text-warning fw-bold mb-0"><?= number_format($s1_In['weight'] + $s2_In['weight'], 3) ?>g</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-custom shadow-sm" style="border-top: 3px solid <?= abs($wip) > 0.001 ? '#ef4444' : '#10b981' ?>;">
                <div class="small text-secondary mb-1">VARIANCE (WIP)</div>
                <h3 class="<?= abs($wip) > 0.001 ? 'text-danger' : 'text-success' ?> fw-bold mb-0"><?= number_format($wip, 3) ?>g</h3>
            </div>
        </div>
    </div>

    <!-- Details Table -->
    <div class="card-custom p-0 overflow-hidden">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead class="bg-black bg-opacity-25">
                <tr class="small text-secondary">
                    <th class="ps-4 py-3">TIME</th>
                    <th>PRODUCT</th>
                    <th>DIRECTION</th>
                    <th class="text-end pe-4">WEIGHT</th>
                </tr>
            </thead>
            <tbody>
            <?php while($row = sqlsrv_fetch_array($resultList, SQLSRV_FETCH_ASSOC)): 
                $timeObj = $row['CreatedOn'];
                $isOut = $row['SourceDepartmentId'] == $sourceId;
            ?>
                <tr>
                    <td class="ps-4 small text-secondary"><?= $timeObj->format('H:i:s') ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($row['ProductName']) ?></td>
                    <td>
                        <span class="badge bg-<?= $isOut ? 'info text-dark' : 'warning text-dark' ?> small">
                            <?= $isOut ? 'OUT' : 'IN' ?>
                        </span>
                    </td>
                    <td class="text-end pe-4 fw-bold <?= $isOut ? 'text-info' : 'text-warning' ?>">
                        <?= number_format($row['Weight'], 3) ?>g
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function changeDate(newDate) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('date', newDate);
    window.location.search = urlParams.toString();
}
</script>

</body>
</html>