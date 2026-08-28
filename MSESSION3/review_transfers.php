<?php
// Establish connection to your targeted database
$myConn = new mysqli("localhost", "root", "", "session_mode");
if ($myConn->connect_error) {
    die("Database link offline: " . $myConn->connect_error);
}

// Fetch all recorded outbound transfers grouped by their distinct batch tokens
$query = "SELECT * FROM weight_transfers ORDER BY recorded_at DESC";
$result = $myConn->query($query);

$transfers = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $transfers[] = $row;
    }
}
$myConn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MILANO SECURITY FRAMEWORK - TRANSFER REVIEW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@300;600&display=swap" rel="stylesheet">
    <style>
        body { background: #020617; color: white; font-family: 'Inter', sans-serif; }
        .orbitron { font-family: 'Orbitron', sans-serif; }
        .glass { background: rgba(255,255,255,0.03); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.01); }
        ::-webkit-scrollbar-thumb { background: rgba(239, 68, 68, 0.3); border-radius: 10px; }
    </style>
</head>
<body class="min-h-screen p-8 flex flex-col justify-between">

<div class="w-full max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-8 border-b border-white/5 pb-6">
        <div>
            <div class="flex items-center gap-3">
                <span class="orbitron tracking-[0.2em] text-xl font-bold text-red-600">MILANO GMS</span>
                <span class="bg-red-500/10 border border-red-500/30 text-red-400 text-[10px] uppercase font-bold tracking-widest px-3 py-0.5 rounded-full">
                    Review Matrix
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1 uppercase tracking-wider">Staged logs awaiting operational authorization management</p>
        </div>
        
        <div class="flex gap-3">
            <button onclick="location.reload()" class="orbitron text-[11px] px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-300 transition-all border border-white/5 uppercase tracking-wider">
                ↻ Refresh Matrix
            </button>
            <button onclick="window.open('', '_self', ''); window.close();" class="orbitron text-[11px] px-4 py-2 rounded-xl bg-red-950/20 hover:bg-red-900/40 text-red-400 transition-all border border-red-500/20 uppercase tracking-wider">
                ✕ Close Window
            </button>
        </div>
    </div>

    <div class="glass rounded-3xl p-6 shadow-2xl border border-white/10">
        <?php if (empty($transfers)): ?>
            <div class="text-center py-20 text-slate-500 orbitron text-xs tracking-widest uppercase">
                [ No staged records detected inside session_mode database ]
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/10 orbitron text-[10px] uppercase tracking-widest text-slate-400">
                            <th class="py-4 px-4">Batch Token / Date</th>
                            <th class="py-4 px-4">Dept ID</th>
                            <th class="py-4 px-4">Department Name</th>
                            <th class="py-4 px-4">Tracking Code / Remark</th>
                            <th class="py-4 px-4 text-right">Captured Weight</th>
                            <th class="py-4 px-4 text-center">Authorization Control</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-sm">
                        <?php foreach ($transfers as $row): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="py-4 px-4 font-mono">
                                    <span class="text-red-400 font-bold tracking-wider block"><?php echo htmlspecialchars($row['batch_token']); ?></span>
                                    <span class="text-[10px] text-slate-500 block mt-0.5"><?php echo $row['recorded_at']; ?></span>
                                </td>
                                
                                <td class="py-4 px-4 font-mono text-slate-400">
                                    #<?php echo htmlspecialchars($row['department_id']); ?>
                                </td>
                                
                                <td class="py-4 px-4 font-semibold text-white">
                                    <?php echo htmlspecialchars($row['department_name']); ?>
                                </td>
                                
                                <td class="py-4 px-4">
                                    <span class="text-xs font-mono bg-white/5 border border-white/5 px-2 py-0.5 rounded text-slate-300"><?php echo htmlspecialchars($row['random_code']); ?></span>
                                    <p class="text-xs text-slate-500 italic mt-1"><?php echo htmlspecialchars($row['customer_remark'] ?: 'No remark provided'); ?></p>
                                </td>
                                
                                <td class="py-4 px-4 text-right orbitron font-black text-emerald-400 text-base tracking-wide">
                                    <?php echo number_format($row['weight'], 3); ?><span class="text-[10px] font-normal text-slate-600 ml-0.5">g</span>
                                </td>
                                
                                <td class="py-4 px-4">
                                    <div class="flex justify-center items-center gap-2">
                                        <button onclick="handleApproveStub('<?php echo $row['id']; ?>')" class="orbitron text-[9px] font-bold tracking-widest px-3 py-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500 text-emerald-400 hover:text-black transition-all uppercase">
                                            Approve
                                        </button>
                                        <button onclick="handleRejectStub('<?php echo $row['id']; ?>')" class="orbitron text-[9px] font-bold tracking-widest px-3 py-1.5 rounded-lg bg-red-500/10 hover:bg-red-500 text-red-400 hover:text-white transition-all uppercase">
                                            Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="w-full text-center orbitron text-[9px] text-slate-600 uppercase tracking-widest py-8">
    Milano Universal Automation Architecture &bull; Transfer Auditing Interface
</div>

<script>
// --- TEMPORARY INTERFACE BUTTON HOOKS ---
function handleApproveStub(rowId) {
    Swal.fire({
        title: 'APPROVE LOGGED RECORD',
        text: `Row ID #${rowId} handler placeholder clicked. We will connect the logic loop here later.`,
        icon: 'info',
        background: '#020617',
        color: '#fff',
        confirmButtonColor: '#10b981'
    });
}

function handleRejectStub(rowId) {
    Swal.fire({
        title: 'REJECT LOGGED RECORD',
        text: `Row ID #${rowId} handler placeholder clicked. We will connect the logic loop here later.`,
        icon: 'warning',
        background: '#020617',
        color: '#fff',
        confirmButtonColor: '#ef4444'
    });
}
</script>
</body>
</html>