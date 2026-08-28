<?php include 'navbar.php'; ?>
<?php
// 1. Database connection
$serverName = "localhost\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "21kEuroStar",
    "Uid" => "sa",
    "PWD" => "123456",
    "CharacterSet" => "UTF-8"
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die("<div class='alert alert-danger'>Database Connection Failed: " . print_r(sqlsrv_errors(), true) . "</div>");
}

// 2. Fetch department list
$departments = [];
$deptQuery = "SELECT Id, DepartmentName FROM departments";
$deptStmt = sqlsrv_query($conn, $deptQuery);
while ($deptStmt && $row = sqlsrv_fetch_array($deptStmt, SQLSRV_FETCH_ASSOC)) {
    $departments[$row['Id']] = $row['DepartmentName'];
}

// 3. Handle Filters
$filterSource = $_GET['filterSource'] ?? '';
$filterDestination = $_GET['filterDestination'] ?? '';
$filterFrom = $_GET['filterFrom'] ?? '';
$filterTo = $_GET['filterTo'] ?? '';

$params = [];
$whereClauses = [];

if (!empty($filterFrom)) {
    // Force start of day: 2026-05-09 00:00:00
    $params[] = $filterFrom . " 00:00:00";
    $whereClauses[] = "I.[CreatedOn] >= ?";
} 

if (!empty($filterTo)) {
    // Force end of day: 2026-05-09 23:59:59
    $params[] = $filterTo . " 23:59:59";
    $whereClauses[] = "I.[CreatedOn] <= ?";
}
if (empty($filterFrom) && empty($filterTo)) {
    $params[] = date('Y-m-d H:i:s', strtotime('-1 day'));
    $whereClauses[] = "I.[CreatedOn] >= ?";
}

if (!empty($filterSource)) {
    $params[] = $filterSource;
    $whereClauses[] = "TL.[SourceDepartmentId] = ?";
}
if (!empty($filterDestination)) {
    $params[] = $filterDestination;
    $whereClauses[] = "TL.[DestinationDepartmentId] = ?";
}
if (empty($filterFrom) && empty($filterTo)) {
    $params[] = date('Y-m-d 00:00:00'); 
    $whereClauses[] = "I.[CreatedOn] >= ?";
}
$whereSql = !empty($whereClauses) ? " WHERE " . implode(' AND ', $whereClauses) : "";

// 4. SQL Query
$sql = "
SELECT TOP 1000
    TL.[Id],
    TL.[UserId],
    -- Pull the date from the Inventories table (I)
    CONVERT(VARCHAR, I.[CreatedOn], 121) AS [CreatedOnFormatted],
    TL.[SourceDepartmentId],
    TL.[DestinationDepartmentId],
    TL.[Weight],
    TL.[Remark],
    TL.[User],
    I.[ProductName],
    I.[Id] AS [InvID],
    I.[IsStone],
    d1.DepartmentName AS SourceDeptName,
    d2.DepartmentName AS DestDeptName
FROM [21kEuroStar].[dbo].[TransactionLogs] TL
LEFT JOIN [21kEuroStar].[dbo].[Inventories] I ON TL.[InventoryId] = I.[Id]
LEFT JOIN departments d1 ON TL.SourceDepartmentId = d1.Id
LEFT JOIN departments d2 ON TL.DestinationDepartmentId = d2.Id
" . $whereSql . " ORDER BY TL.[CreatedOn] DESC";

// 5. Summary Query
$countSql = "
SELECT COUNT(*) AS TotalCount, SUM(TL.Weight) AS TotalWeight 
FROM [TransactionLogs] TL
LEFT JOIN [Inventories] I ON TL.InventoryId = I.Id" . $whereSql;

$stmt = sqlsrv_query($conn, $sql, $params);
$countStmt = sqlsrv_query($conn, $countSql, $params);

$totalCount = 0; $totalWeight = 0;
if ($countStmt && $row = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC)) {
    $totalCount = $row['TotalCount'] ?? 0;
    $totalWeight = $row['TotalWeight'] ?? 0;
}

$data = [];
while ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data[] = $row;
}
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transaction History</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .bell-icon {
    color: grey;
}
    /* Gray/Black for inactive */
.stone-inactive { 
    color: #6c757d !important; 
} 

/* Gold for active */
.stone-active { 
    color: #ffc107 !important; 
}

.bell-toggle { 
    cursor: pointer; 
    transition: transform 0.2s, color 0.2s; 
    margin-right: 5px; 
    display: inline-block;
}

.bell-toggle:hover { 
    transform: scale(1.2); 
}
        .stats-card { border-left: 4px solid #0dcaf0; border-radius: 8px; background: white; }
        .table thead { background-color: #212529; color: white; }
        .match-found { color: #198754; font-weight: bold; }
        .no-match { color: #dc3545; font-size: 0.85em; font-style: italic; }
        .time-text { font-family: monospace; font-size: 0.85em; }
        .inv-col { font-family: monospace; color: #0d6efd; font-weight: bold; display: flex; align-items: center; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Transaction History</h2>
        <span class="badge bg-dark"><?= (empty($filterFrom) && empty($filterTo)) ? 'Default: Last 24h' : 'Filtered' ?></span>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card p-3 shadow-sm stats-card">
                <div class="text-secondary small">Total Records</div>
                <h4 class="mb-0"><?= number_format($totalCount) ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm stats-card" style="border-left-color: #ffc107;">
                <div class="text-secondary small">Total Weight</div>
                <h4 class="mb-0"><?= number_format($totalWeight, 3) ?> g</h4>
            </div>
        </div>
    </div>

    <form method="GET" class="card p-3 shadow-sm mb-4 border-0">
        <div class="row g-2">
            <div class="col-md-2">
                <select name="filterSource" class="form-select">
                    <option value="">-- Source --</option>
                    <?php foreach ($departments as $id => $name): ?>
                        <option value="<?= $id ?>" <?= ($id == $filterSource) ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="filterDestination" class="form-select">
                    <option value="">-- Dest --</option>
                    <?php foreach ($departments as $id => $name): ?>
                        <option value="<?= $id ?>" <?= ($id == $filterDestination) ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="filterFrom" class="form-control" value="<?= $filterFrom ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="filterTo" class="form-control" value="<?= $filterTo ?>">
            </div>
            <div class="col-md-2 d-flex gap-1">
    <button type="submit" class="btn btn-primary w-100">Filter</button>
    <a href="index.php" class="btn btn-outline-secondary">Reset</a>
    
</div>
        </div>
    </form>

    <div class="table-responsive shadow-sm rounded">
        <table class="table table-white table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Inventory ID</th> 
                    <th>Product Name</th>
                    <th>Source ➔ Dest</th>
                    <th>Weight</th>
                    <th>User / Remark</th>
                    <th>Created On</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data)): foreach ($data as $row): ?>
                <tr>
                    <td>#<?= $row['Id'] ?></td>
                    <td class="inv-col">
                        <?php if ($row['InvID']): ?>
                           <span class="bell-toggle <?= $row['IsStone'] ? 'stone-active' : 'stone-inactive' ?>" 
      onclick="toggleStone(this, <?= $row['InvID'] ?>)">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2m.995-14.901a1 1 0 1 0-1.99 0A5 5 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901"/>
    </svg>
</span>
                            #<?= $row['InvID'] ?>
                        <?php else: ?>
                            <span class="text-muted small">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $row['ProductName'] ? '<span class="match-found">'.$row['ProductName'].'</span>' : '<span class="no-match">No Match</span>' ?>
                    </td>
                    <td>
                        <div class="small"><?= htmlspecialchars($row['SourceDeptName'] ?? 'ID: '.$row['SourceDepartmentId']) ?></div>
                        <div class="text-muted" style="font-size: 0.75rem;">➔ <?= htmlspecialchars($row['DestDeptName'] ?? 'ID: '.$row['DestinationDepartmentId']) ?></div>
                    </td>
                    <td><strong><?= number_format($row['Weight'], 3) ?></strong></td>
                    <td>
                        <div class="small fw-bold text-primary"><?= htmlspecialchars($row['User'] ?? 'System') ?></div>
                        <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($row['Remark'] ?? '') ?></div>
                    </td>
                    <td class="time-text text-muted"><?= $row['CreatedOnFormatted'] ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="7" class="text-center p-5 text-muted">No transactions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function openCommand() {
    const overlay = document.getElementById('commandOverlay');
    overlay.classList.remove('d-none');
    overlay.classList.add('animate__animated', 'animate__fadeIn');
}

function closeCommand() {
    const overlay = document.getElementById('commandOverlay');
    overlay.classList.add('d-none');
}

// Optional: Close on ESC key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeCommand();
});
function toggleStone(element, invId) {
    fetch('toggle_schedule.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'invId=' + invId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            element.classList.toggle('stone-active');
            element.classList.toggle('stone-inactive');
        } else {
            alert(data.message || "Update failed.");
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert("Server error. Please check toggle_stone.php");
    });
}
</script>
<div id="commandOverlay" class="fixed-top h-100 w-100 d-none d-flex align-items-center justify-content-center" style="background: rgba(12, 14, 18, 0.95); backdrop-filter: blur(10px); z-index: 9999;">
    <button onclick="closeCommand()" class="btn-close btn-close-white position-absolute top-0 end-0 m-5" aria-label="Close"></button>
    
    <div class="row w-100 px-5 justify-content-center text-center">
        <div class="col-12 mb-5">
            <p class="text-amber-500 font-monospace small mb-0 tracking-widest uppercase">System Handover</p>
            <h1 class="text-white fw-black display-4 italic tracking-tighter">Select Module</h1>
        </div>
        
        <div class="col-md-4 mb-4">
            <a href="/config" class="text-decoration-none group">
                <div class="p-5 rounded-4 border border-white/10 bg-white/5 hover:bg-white/10 transition-all shadow-2xl h-100 d-flex flex-column align-items-center justify-content-center" style="border: 1px solid rgba(255,255,255,0.05);">
                    <div class="mb-4 p-4 rounded-circle bg-primary/10">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#3b82f6" class="bi bi-gear-fill" viewBox="0 0 16 16">
                            <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"/>
                        </svg>
                    </div>
                    <h3 class="text-white fw-bold mb-2">SYSTEM CONFIG</h3>
                    <p class="text-secondary small">Master parameters, Dept Management, and Global settings.</p>
                </div>
            </a>
        </div>

        

        <div class="col-md-4 mb-4">
            <a href="/MS" class="text-decoration-none">
                <div class="p-5 rounded-4 border border-white/10 bg-white/5 hover:bg-white/10 transition-all shadow-2xl h-100 d-flex flex-column align-items-center justify-content-center" style="border: 1px solid rgba(255,255,255,0.05);">
                    <div class="mb-4 p-4 rounded-circle bg-amber-500/10">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#f59e0b" class="bi bi-safe2-fill" viewBox="0 0 16 16">
                            <path d="M12 11h2v1h-2v-1z"/>
                            <path d="M12 9a1 1 0 0 1 1-1h2v3h-2a1 1 0 0 1-1-1V9z"/>
                            <path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zM2 4v8h12V4H2z"/>
                        </svg>
                    </div>
                    <h3 class="text-white fw-bold mb-2">VAULT TERMINAL</h3>
                    <p class="text-secondary small">Outbound logistics, Acceptance protocols, and Gold Audit.</p>
                </div>
            </a>
        </div>

        

        
    </div>
</div>
</body>
</html>