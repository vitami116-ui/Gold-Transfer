<?php
require_once 'db_connect.php';

// 1. DATA FETCHING LOGIC
$db = $_GET['db'] ?? '24k';
$pdo = getPDO($db);

try {
    $sql = "SELECT TOP 1 
                L.Id AS LogId,
                L.CreatedOn,
                L.Weight AS LogWeight,
                L.[User] AS Operator,
                L.SourceDepartmentId AS FromDept,
                L.DestinationDepartmentId AS ToDept,
                L.InventoryId,
                L.Remark,
                (SELECT Weight FROM Departments WHERE Id = L.SourceDepartmentId) AS SourceBal,
                (SELECT Weight FROM Departments WHERE Id = L.DestinationDepartmentId) AS DestBal
            FROM TransactionLogs L
            ORDER BY L.Id DESC";

    $stmt = $pdo->query($sql);
    $audit = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Audit Error: " . $e->getMessage());
}

if (!$audit) {
    die("No transaction records found to audit.");
}

// 2. VISUAL PRESENTATION (HTML/CSS)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;900&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #020617; font-family: 'Inter', sans-serif; color: white; }
        .orbitron { font-family: 'Orbitron', sans-serif; }
        .glass { background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.05); }
        .glow-green { box-shadow: 0 0 20px rgba(16, 185, 129, 0.1); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

    <div class="glass w-full max-w-md rounded-3xl overflow-hidden shadow-2xl glow-green border-t-4 border-emerald-500">
        
        <div class="p-6 bg-emerald-500/5 flex justify-between items-center border-b border-white/5">
            <div>
                <h2 class="orbitron text-emerald-400 text-[10px] tracking-[0.3em] font-black uppercase">Audit Status</h2>
                <p class="text-xl font-bold text-white">VERIFIED</p>
            </div>
            <div class="text-right">
                <p class="text-[9px] text-slate-500 font-mono">LOG_ID: #<?php echo $audit['LogId']; ?></p>
                <p class="text-[9px] text-slate-500 font-mono"><?php echo date("Y.m.d H:i", strtotime($audit['CreatedOn'])); ?></p>
            </div>
        </div>

        
        <div class="p-8">
            <div class="flex items-center justify-between gap-4 mb-10">
                <div class="flex flex-col items-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-800 flex items-center justify-center border border-white/10 mb-2">
                        <span class="text-lg font-bold"><?php echo $audit['FromDept']; ?></span>
                    </div>
                    <span class="text-[9px] text-slate-500 uppercase tracking-widest">Source</span>
                </div>

                <div class="flex-1 flex flex-col items-center">
                    <span class="orbitron text-2xl font-black text-yellow-500 drop-shadow-lg">
                        <?php echo number_format($audit['LogWeight'], 3); ?>g
                    </span>
                    <div class="w-full h-[1px] bg-gradient-to-r from-emerald-500/0 via-emerald-500 to-emerald-500/0 relative mt-2">
                        <div class="absolute -right-1 -top-1 text-[8px] text-emerald-500">▶</div>
                    </div>
                </div>

                <div class="flex flex-col items-center">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 flex items-center justify-center border border-emerald-500/30 mb-2">
                        <span class="text-lg font-bold text-emerald-400"><?php echo $audit['ToDept']; ?></span>
                    </div>
                    <span class="text-[9px] text-slate-500 uppercase tracking-widest">Destination</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-black/40 p-4 rounded-2xl border border-white/5">
                    <p class="text-[8px] text-slate-500 uppercase mb-1">Source Balance</p>
                    <p class="font-mono text-sm"><?php echo number_format($audit['SourceBal'], 3); ?>g</p>
                </div>
                <div class="bg-black/40 p-4 rounded-2xl border border-white/5">
                    <p class="text-[8px] text-slate-500 uppercase mb-1">Dest. Balance</p>
                    <p class="font-mono text-sm text-emerald-400"><?php echo number_format($audit['DestBal'], 3); ?>g</p>
                </div>
            </div>

            <div class="space-y-3 pt-6 border-t border-white/5">
                <div class="flex justify-between text-xs">
                    <span class="text-slate-500">Operator:</span>
                    <span class="font-bold text-slate-200"><?php echo strtoupper($audit['Operator']); ?></span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-slate-500">Inventory ID:</span>
                    <span class="font-mono text-slate-200">#<?php echo $audit['InventoryId'] ?? 'N/A'; ?></span>
                </div>
                <?php if($audit['Remark']): ?>
                <div class="flex flex-col gap-1 text-xs">
                    <span class="text-slate-500">Remark:</span>
                    <span class="italic text-slate-400 bg-slate-800/50 p-2 rounded-lg"><?php echo $audit['Remark']; ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-4 bg-emerald-500 text-center">
            <p class="orbitron text-[10px] text-emerald-950 font-black tracking-widest">TRANSACTION INTEGRITY SECURED</p>
        </div>
    </div>

</body>
</html>