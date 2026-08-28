<?php
// Database connection
$serverName = "localhost\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "21kEuroStar",
    "Uid" => "sa",
    "PWD" => "123456",
    "CharacterSet" => "UTF-8" // Ensures Chinese characters display correctly
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die("<div class='alert alert-danger'>Database Connection Failed: " . print_r(sqlsrv_errors(), true) . "</div>");
}

// Fetch department list for the filter dropdown
$departments = [];
$deptQuery = "SELECT Id, DepartmentName FROM departments";
$deptStmt = sqlsrv_query($conn, $deptQuery);

if ($deptStmt === false) {
    die("<div class='alert alert-danger'>Department Query Failed: " . print_r(sqlsrv_errors(), true) . "</div>");
}

while ($row = sqlsrv_fetch_array($deptStmt, SQLSRV_FETCH_ASSOC)) {
    $departments[$row['Id']] = $row['DepartmentName'];
}

// Get filter value
$filterSource = isset($_GET['filterSource']) ? $_GET['filterSource'] : '';
$filterDestination = isset($_GET['filterDestination']) ? $_GET['filterDestination'] : '';
$filterFrom = isset($_GET['filterFrom']) ? $_GET['filterFrom'] : ''; // datetime-local format: 'YYYY-MM-DDTHH:MM'
$filterTo = isset($_GET['filterTo']) ? $_GET['filterTo'] : ''; // datetime-local format: 'YYYY-MM-DDTHH:MM'

// Convert datetime-local format to SQL compatible format (YYYY-MM-DD HH:MM:SS)
if (!empty($filterFrom)) {
    $filterFrom = str_replace("T", " ", $filterFrom) . ":00"; // Add seconds (SS) part
}

if (!empty($filterTo)) {
    $filterTo = str_replace("T", " ", $filterTo) . ":00"; // Add seconds (SS) part
}
// Base SQL query
$sql = "     
    SELECT          
        t.Id,          
        d1.DepartmentName AS SourceDepartment,          
        d2.DepartmentName AS DestinationDepartment,          
        t.Weight,          
        t.Remark,         
        CONVERT(VARCHAR, t.CreatedOn, 120) AS CreatedOn          
    FROM TransactionLogs t     
    LEFT JOIN departments d1 ON t.SourceDepartmentId = d1.Id     
    LEFT JOIN departments d2 ON t.DestinationDepartmentId = d2.Id";
// Base Count
$countSql = "     
    SELECT COUNT(*) AS TotalCount,
	SUM(t.Weight) AS TotalWeight
    FROM TransactionLogs t     
    LEFT JOIN departments d1 ON t.SourceDepartmentId = d1.Id     
    LEFT JOIN departments d2 ON t.DestinationDepartmentId = d2.Id";

// Apply filters if set
$params = [];
$whereClauses = [];

if (!empty($filterSource)) {
    $whereClauses[] = "t.SourceDepartmentId = ?";
    $params[] = $filterSource;
}

if (!empty($filterDestination)) {
    $whereClauses[] = "t.DestinationDepartmentId = ?";
    $params[] = $filterDestination;
}

// Apply the "From" and "To" date filters if set
if (!empty($filterFrom) && !empty($filterTo)) {
    $whereClauses[] = "CONVERT(date, t.CreatedOn) BETWEEN ? AND ?";
    $params[] = $filterFrom;
    $params[] = $filterTo;
} elseif (!empty($filterFrom)) {
    $whereClauses[] = "CONVERT(date, t.CreatedOn) >= ?";
    $params[] = $filterFrom;
} elseif (!empty($filterTo)) {
    $whereClauses[] = "CONVERT(date, t.CreatedOn) <= ?";
    $params[] = $filterTo;
}

// Apply the WHERE clause only if there are filter conditions
if (count($whereClauses) > 0) {
    $sql .= " WHERE " . implode(' AND ', $whereClauses);
	$countSql .= " WHERE " . implode(' AND ', $whereClauses);
}

// Execute query
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die("<div class='alert alert-danger'>Transaction Query Failed: " . print_r(sqlsrv_errors(), true) . "</div>");
}
$countStmt = sqlsrv_query($conn, $countSql, $params);
$totalCount = 0;
$totalWeight = 0;

if ($countStmt !== false) {
    $row = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
    $totalCount = $row['TotalCount'];
	$totalWeight = $row['TotalWeight'];
}
// Store results in an array
$data = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data[] = $row;
}

// Close connection
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
form .row {
    margin-bottom: 1rem;
}

form .form-select,
form .form-control {
    width: 100%;
}

form .col-md-2 .btn {
    width: 100%;
    margin-top: 10px; /* Ensures that the buttons don't overlap */
}

</style>
<body class="bg-light">

<div class="container mt-4">
    <h2 class="text-center mb-4">Transaction Logs</h2>
    <div class="mb-3">
        <h4>Total Transactions: <?= $totalCount ?></h4>
		<h4>Total Weight: <?= number_format($totalWeight, 2) ?> g</h4> <!-- Format the weight to 2 decimal places -->
    </div>
    <!-- Filter Dropdown -->
<form method="GET" class="mb-3">
    <div class="row">
        <div class="col-md-3">
            <select name="filterSource" class="form-select">
                <option value="">-- Source --</option>
                <?php foreach ($departments as $id => $name) : ?>
                    <option value="<?= htmlspecialchars($id) ?>" <?= ($id == $filterSource) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <select name="filterDestination" class="form-select">
                <option value="">-- Destination --</option>
                <?php foreach ($departments as $id => $name) : ?>
                    <option value="<?= htmlspecialchars($id) ?>" <?= ($id == $filterDestination) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

       <div class="col-md-2">
            <!-- Date and Time From Filter -->
            <input type="datetime-local" name="filterFrom" class="form-control" value="<?= htmlspecialchars($filterFrom) ?>">
        </div>

        <div class="col-md-2">
            <!-- Date and Time To Filter -->
            <input type="datetime-local" name="filterTo" class="form-control" value="<?= htmlspecialchars($filterTo) ?>">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <a href="index.php" class="btn btn-secondary w-100">Reset</a>
        </div>
    </div>
</form>


    <!-- Transactions Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Source Department</th>
                    <th>Destination Department</th>
                    <th>Weight</th>
                    <th>Remark</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data)) : ?>
                    <?php foreach ($data as $row) : ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Id']) ?></td>
                            <td><?= htmlspecialchars($row['SourceDepartment']) ?></td>
                            <td><?= htmlspecialchars($row['DestinationDepartment']) ?></td>
                            <td><?= htmlspecialchars($row['Weight']) ?></td>
                            <td><?= htmlspecialchars($row['Remark']) ?></td>
                            <td><?= htmlspecialchars($row['CreatedOn']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No transactions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
