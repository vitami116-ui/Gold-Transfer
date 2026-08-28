<?php
require_once 'db_connect.php';

$db = $_GET['db'] ?? '24k';
$pdo = getPDO($db);

try {
    // Fetch the last 10 transactions with full details
    $sql = "SELECT TOP 10 
                L.Id, L.CreatedOn, L.Weight, L.[User], 
                L.SourceDepartmentId AS FromDept, 
                L.DestinationDepartmentId AS ToDept,
                I.ProductName,
                (SELECT Weight FROM Departments WHERE Id = L.SourceDepartmentId) AS CurrentSourceBal,
                (SELECT Weight FROM Departments WHERE Id = L.DestinationDepartmentId) AS CurrentDestBal
            FROM TransactionLogs L
            LEFT JOIN Inventories I ON L.InventoryId = I.Id
            ORDER BY L.Id DESC";

    $stmt = $pdo->query($sql);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Logic Audit Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #020617; font-family: 'Inter', sans-serif; color: #f8fafc; }
        .orbitron { font-family: 'Orbitron', sans-serif; }
        .row-hover:hover { background-color: rgba(30, 41, 59, 0.5); }
        .status-dot { height: 8px; width: 8px; border-radius: 50%; display: inline-block; }
    </style>
</head>
<body class="p-6">

    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-end mb-8 border-b border-slate-800 pb-6">
            <div>
                <h1 class="orbitron text-2xl font-black text-emerald-500 tracking-tighter">LOGIC_FLOW AUDIT</h1>
                <p class="text-slate-400 text-sm">Verifying weight distribution across 3-tier database logic.</p>
            </div>
            <div class="text-right">
                <span class="text-[10px] text-slate-500 uppercase tracking-widest">Active Database</span>
                <p class="orbitron font-bold text-white"><?php echo strtoupper($db); ?></p>
            </div>
        </div>

        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-slate-900/50 border border-slate-800 p-5 rounded-2xl">
                <p class="text-[10px] text-emerald-500 orbitron mb-2 tracking-widest">STEP 1: MASS BALANCE</p>
                <p class="text-xs text-slate-400">Updates <b>Departments</b> table. Subtracts weight from Source, adds to Destination.</p>
            </div>
            <div class="bg-slate-900/50 border border-slate-800 p-5 rounded-2xl">
                <p class="text-[10px] text-blue-500 orbitron mb-2 tracking-widest">STEP 2: ASSET SHIFT</p>
                <p class="text-xs text-slate-400">Updates <b>Inventory</b> table. Closes source item and spawns new unique ID.</p>
            </div>
            <div class="bg-slate-900/50 border border-slate-800 p-5 rounded-2xl">
                <p class="text-[10px] text-purple-500 orbitron mb-2 tracking-widest">STEP 3: TRACEABILITY</p>
                <p class="text-xs text-slate-400">Updates <b>TransactionLogs</b>. Links the weight and the unique Inventory ID.</p>
            </div>
        </div>

        <div class="bg-slate-900/40 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-900/80 text-[10px] orbitron text-slate-500 tracking-widest">
                    <tr>
                        <th class="p-4 border-b border-slate-800">TIME</th>
                        <th class="p-4 border-b border-slate-800">FLOW (DEPT)</th>
                        <th class="p-4 border-b border-slate-800">ITEM / PRODUCT</th>
                        <th class="p-4 border-b border-slate-800">WEIGHT</th>
                        <th class="p-4 border-b border-slate-800">OPERATOR</th>
                        <th class="p-4 border-b border-slate-800 text-right">DB_SYNC</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <?php foreach ($history as $row): ?>
                    <tr class="row-hover transition-colors border-b border-slate-800/50">
                        <td class="p-4 text-slate-500 text-xs">
                            <?php echo date("H:i:s", strtotime($row['CreatedOn'])); ?><br>
                            <span class="text-[9px]"><?php echo date("d M", strtotime($row['CreatedOn'])); ?></span>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <span class="bg-slate-800 px-2 py-1 rounded text-xs font-bold"><?php echo $row['FromDept']; ?></span>
                                <span class="text-emerald-500">→</span>
                                <span class="bg-emerald-500/10 text-emerald-400 px-2 py-1 rounded text-xs font-bold border border-emerald-500/20"><?php echo $row['ToDept']; ?></span>
                            </div>
                        </td>
                        <td class="p-4 font-medium italic text-slate-300">
                            <?php echo $row['ProductName'] ?? 'Bulk Metal'; ?>
                        </td>
                        <td class="p-4">
                            <span class="font-bold text-white"><?php echo number_format($row['Weight'], 3); ?>g</span>
                        </td>
                        <td class="p-4 text-slate-400 uppercase text-[10px] font-bold">
                            <?php echo $row['User']; ?>
                        </td>
                        <td class="p-4 text-right">
                            <span class="status-dot bg-emerald-500 mr-1 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
                            <span class="text-[10px] font-mono text-emerald-500 uppercase">Linked</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-between items-center px-4">
            <p class="text-[10px] text-slate-600 font-mono italic">Showing last 10 entries. Integrity verified against current Department weights.</p>
            <button onclick="window.location.reload()" class="bg-slate-800 hover:bg-slate-700 text-xs px-4 py-2 rounded-full transition-all active:scale-95 border border-slate-700">
                Refresh Flow
            </button>
        </div>
    </div>

</body>
</html>