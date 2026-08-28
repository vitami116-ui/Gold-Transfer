<?php
require_once 'db_connect.php';
$db = $_GET['db'] ?? '24k';
$pdo = getPDO($db);

try {
    // JOINing Departments twice and corrected table name to Inventories
    $sql = "SELECT 
                L.Id, L.CreatedOn, L.Weight, L.[User], 
                L.SourceDepartmentId, L.DestinationDepartmentId,
                D1.DepartmentName AS SourceName, 
                D2.DepartmentName AS DestName,
                I.ProductName, I.Remark
            FROM TransactionLogs L
            LEFT JOIN Inventories I ON L.InventoryId = I.Id
            LEFT JOIN Departments D1 ON L.SourceDepartmentId = D1.Id
            LEFT JOIN Departments D2 ON L.DestinationDepartmentId = D2.Id
            WHERE CAST(L.CreatedOn AS DATE) = CAST(GETDATE() AS DATE)
            ORDER BY L.Id DESC";
            
    $history = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $totalWeightToday = 0;
    foreach($history as $row) { $totalWeightToday += $row['Weight']; }

} catch (Exception $e) { die("Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; color: #1e293b; }
        .receipt-card { background: white; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); }
        .arrow-flow {
            position: relative;
            height: 2px;
            background: #cbd5e1;
            width: 100%;
        }
        .arrow-flow::after {
            content: '';
            position: absolute;
            right: -2px;
            top: -4px;
            width: 0; 
            height: 0; 
            border-top: 5px solid transparent;
            border-bottom: 5px solid transparent;
            border-left: 8px solid #10b981;
        }
    </style>
</head>
<body class="p-4 md:p-12">
    <div class="max-w-4xl mx-auto">
        
        <div class="flex justify-between items-center mb-10 bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Today's Transactions</h1>
                <p class="text-slate-500 font-semibold uppercase text-xs tracking-widest mt-1">
                    Database: <span class="text-blue-600"><?php echo strtoupper($db); ?></span>
                </p>
            </div>
            <div class="text-right">
                <p class="text-[10px] text-slate-400 font-bold uppercase">Total Volume Today</p>
                <p class="text-4xl font-black text-emerald-600 tracking-tighter">
                    <?php echo number_format($totalWeightToday, 3); ?><span class="text-xl ml-1">g</span>
                </p>
            </div>
        </div>

        <?php if (empty($history)): ?>
            <div class="bg-white p-20 rounded-[3rem] text-center border-2 border-dashed border-slate-200">
                <p class="text-slate-400 text-lg font-medium">No movement recorded yet today.</p>
            </div>
        <?php else: ?>

        <div class="space-y-4">
            <?php foreach ($history as $row): ?>
            <div class="receipt-card rounded-2xl p-6 border border-slate-200 transition-all hover:border-emerald-300">
                
                <div class="grid grid-cols-12 items-center">
                    
                    <div class="col-span-3">
                        <span class="text-[9px] font-bold text-red-500 uppercase tracking-widest">From</span>
                        <h3 class="text-lg font-bold text-slate-800 leading-tight"><?php echo $row['SourceName'] ?: 'Unknown'; ?></h3>
                        <p class="text-[10px] font-mono text-slate-400 mt-1">Dept ID: <?php echo $row['SourceDepartmentId']; ?></p>
                    </div>

                    <div class="col-span-6 px-8 flex flex-col items-center">
                        <div class="text-center mb-2">
                            <p class="text-2xl font-black text-slate-900 leading-none">
                                <?php echo number_format($row['Weight'], 3); ?>g
                            </p>
                            <p class="text-[10px] font-bold text-emerald-600 mt-1 uppercase"><?php echo $row['ProductName'] ?: 'Pure Metal'; ?></p>
                        </div>
                        
                        <div class="arrow-flow">
                            <div class="absolute inset-0 bg-emerald-500 w-full animate-pulse opacity-20"></div>
                        </div>

                        <div class="mt-2 flex gap-4">
                             <span class="text-[9px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded"><?php echo date("H:i", strtotime($row['CreatedOn'])); ?></span>
                             <span class="text-[9px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded">USER: <?php echo strtoupper($row['User']); ?></span>
                        </div>
                    </div>

                    <div class="col-span-3 text-right">
                        <span class="text-[9px] font-bold text-emerald-600 uppercase tracking-widest">To</span>
                        <h3 class="text-lg font-bold text-slate-800 leading-tight"><?php echo $row['DestName'] ?: 'Unknown'; ?></h3>
                        <p class="text-[10px] font-mono text-slate-400 mt-1">Dept ID: <?php echo $row['DestinationDepartmentId']; ?></p>
                    </div>

                </div>

                <?php if($row['Remark']): ?>
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-[10px] text-slate-500 italic font-serif">Note: "<?php echo $row['Remark']; ?>"</p>
                </div>
                <?php endif; ?>
                
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>