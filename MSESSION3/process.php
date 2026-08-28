<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MILANO UNIVERSAL TRANSFER</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@300;600&display=swap" rel="stylesheet">
<style>
    :root {
        --bg-dark: #020617;
        --glass-bg: rgba(15, 23, 42, 0.65);
        --glass-border: rgba(255, 255, 255, 0.08);
        --glow-blue: rgba(59, 130, 246, 0.4);
        --glow-yellow: rgba(234, 179, 8, 0.45);
        --glow-red: rgba(239, 68, 68, 0.45);
        --glow-green: rgba(16, 185, 129, 0.4);
    }

    /* Ambient Industrial Background Grid */
    body { 
        background-color: var(--bg-dark);
        background-image: 
            radial-gradient(at 0% 0%, rgba(30, 58, 138, 0.25) 0px, transparent 50%),
            radial-gradient(at 100% 100%, rgba(88, 28, 135, 0.15) 0px, transparent 50%),
            linear-gradient(rgba(255, 255, 255, 0.015) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.015) 1px, transparent 1px);
        background-size: 100% 100%, 100% 100%, 30px 30px, 30px 30px;
        color: #f8fafc; 
        font-family: 'Inter', sans-serif; 
        overflow: hidden; 
    }

    .orbitron { font-family: 'Orbitron', sans-serif; }

    /* Ultra-Sleek Glassmorphism Panels */
    .glass { 
        background: var(--glass-bg); 
        backdrop-filter: blur(16px); 
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid var(--glass-border); 
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37), inset 0 1px 0 0 rgba(255, 255, 255, 0.1);
    }

    /* ID Cards & Scan Highlights */
    .id-card { 
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
        opacity: 0.2; 
        filter: grayscale(1); 
        transform: scale(0.98);
    }
    .id-card.active { 
        opacity: 1; 
        filter: grayscale(0); 
        border-color: rgba(59, 130, 246, 0.6); 
        transform: scale(1);
        box-shadow: 0 0 25px rgba(59, 130, 246, 0.15);
    }
    .id-card.verified { 
        border-color: #10b981 !important; 
        box-shadow: 0 0 25px var(--glow-green) !important; 
        background: rgba(16, 185, 129, 0.05);
    }

    /* Digital Scale Weight Display Glow */
    #weight_display {
        text-shadow: 0 0 30px rgba(255, 255, 255, 0.25), 0 0 60px rgba(59, 130, 246, 0.15);
        letter-spacing: -0.02em;
    }

    /* Button Glows & Micro-interactions */
    .glow-btn-yellow { 
        box-shadow: 0 0 25px var(--glow-yellow); 
        transition: all 0.3s ease;
    }
    .glow-btn-yellow:hover { 
        box-shadow: 0 0 45px var(--glow-yellow); 
        transform: translateY(-2px) scale(1.01); 
        filter: brightness(1.1);
    }

    .glow-btn-red { 
        box-shadow: 0 0 25px var(--glow-red); 
        transition: all 0.3s ease;
    }
    .glow-btn-red:hover { 
        box-shadow: 0 0 45px var(--glow-red); 
        transform: translateY(-2px) scale(1.01); 
        filter: brightness(1.1);
    }

    /* Reset & Action Buttons */
    .reset-btn { 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        border: 1px solid rgba(239, 68, 68, 0.25); 
        background: rgba(239, 68, 68, 0.05);
    }
    .reset-btn:hover {
        background: rgba(239, 68, 68, 0.2);
        border-color: rgba(239, 68, 68, 0.6);
        color: #ef4444;
        box-shadow: 0 0 20px rgba(239, 68, 68, 0.2);
    }

    /* Status State Cycling Button */
    #state_btn {
        transition: all 0.3s ease;
        backdrop-filter: blur(8px);
    }
    .state-not-ready { 
        background: #eab308 !important; 
        color: #000 !important; 
        border-color: #eab308 !important; 
        font-weight: 800;
        box-shadow: 0 0 20px rgba(234, 179, 8, 0.5);
    }
    .state-powder { 
        background: #ef4444 !important; 
        color: #fff !important; 
        border-color: #ef4444 !important; 
        font-weight: 800;
        box-shadow: 0 0 20px rgba(239, 68, 68, 0.6);
    }

    /* Custom Input Barcode Monitor */
    #input_monitor_box {
        background: rgba(15, 23, 42, 0.9);
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
    }

    /* Smooth Pulse Effects */
    @keyframes subtle-pulse {
        0%, 100% { opacity: 0.9; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(0.98); }
    }
    .animate-pulse {
        animation: subtle-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
</style>
</head>
<body class="h-screen flex flex-col items-center justify-center p-4">

<div class="w-full max-w-5xl flex justify-between items-center mb-8 px-4">
    <div class="flex items-center gap-4">
        <a href="index.php" id="logo_link" class="orbitron tracking-[0.2em] text-xl font-bold hover:opacity-80">MILANO GMS</a>
        <div id="status_pill" class="orbitron border border-slate-700 px-4 py-1 rounded-full text-[20px] tracking-widest uppercase">
            Detecting Mode...
        </div>
    </div>
    <div class="orbitron text-[10px] text-slate-500 uppercase flex items-center gap-3">
        <span>Scale: <span class="text-white" id="scale_status_text">Checking...</span></span>
        <button id="scale_connect_btn" onclick="connectScale()" class="hidden border border-slate-600 rounded px-3 py-1 text-[9px] tracking-widest hover:bg-slate-700 hover:border-slate-400 transition-colors">
            🔌 CONNECT
        </button>
    </div>
</div>

<div class="w-full max-w-5xl grid grid-cols-12 gap-8">
    <div class="col-span-4 space-y-6">
        <!-- Source Card -->
        <div id="user_card_source" class="id-card glass p-4 rounded-xl flex gap-4 items-center border border-transparent">
            <img id="source_avatar" src="https://ui-avatars.com/api/?name=User&background=3b82f6&color=fff" class="w-14 h-14 rounded-lg">
            <div>
                <p id="sender_label_color" class="text-[10px] font-bold uppercase text-slate-500">Source</p>
                <h2 id="source_name" class="font-bold text-lg">---</h2>
                <p id="source_status" class="text-[10px] text-slate-500 italic">Wait for scan...</p>
            </div>
        </div>

        <!-- Destination Card -->
        <div id="user_card_dest" class="id-card glass p-4 rounded-xl flex gap-4 items-center border border-transparent">
            <img id="dest_avatar" src="https://ui-avatars.com/api/?name=Dept&background=eab308&color=000" class="w-14 h-14 rounded-lg">
            <div>
                <p id="receiver_label_color" class="text-[10px] font-bold uppercase text-slate-500">Destination</p>
                <h2 id="dest_dept_name" class="font-bold text-lg">---</h2>
                <p id="dest_status" class="text-[10px] text-slate-500 italic">Waiting...</p>
            </div>
        </div>

        <!-- 1:1 Tag Metadata Panel -->
        <div id="tag_meta_box" class="hidden glass p-4 rounded-xl text-xs space-y-2 border border-slate-700/50">
            <div class="text-[10px] orbitron text-slate-400 font-bold border-b border-slate-800 pb-1">1:1 TRACKING TAG</div>
            <div class="flex justify-between">
                <span class="text-slate-500">CODE:</span>
                <span id="meta_code" class="orbitron text-yellow-400 font-bold">---</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">REMARK:</span>
                <span id="meta_remark" class="text-white">---</span>
            </div>
            <div id="meta_out_weight_row" class="flex justify-between hidden">
                <span class="text-slate-500">DB OUT WEIGHT:</span>
                <span id="meta_out_weight" class="orbitron text-emerald-400 font-bold">Fetching...</span>
            </div>
        </div>
        
        <button onclick="location.href='index.php'" class="reset-btn w-full orbitron text-[10px] py-3 rounded-xl text-slate-500 uppercase tracking-widest">
            ✕ Abort & Go Back
        </button>
    </div>

    <div class="col-span-8 glass rounded-[2.5rem] p-10 flex flex-col items-center justify-center relative border-t border-white/10 shadow-2xl">
        <div id="monitor_ui" class="absolute top-10 opacity-0 transition-opacity">
            <div id="input_monitor_box" class="border px-4 py-1 rounded orbitron text-xs">
                INPUT: <span id="live_text" class="text-white ml-2 tracking-widest"></span>
            </div>
        </div>

        <div class="flex flex-col items-center mb-6">
            <p id="weight_label" class="orbitron text-[10px] text-slate-500 tracking-[0.3em] mb-2 uppercase text-center">Current Weight</p>
            <div id="weight_display" class="orbitron text-[110px] font-black text-white leading-none">0.000<span class="text-3xl text-slate-700 ml-2">g</span></div>
            
            <!-- Real-time Variance (Loss/Gain) indicator for Returns -->
            <div id="variance_display" class="hidden mt-2 orbitron text-sm font-bold px-4 py-1 rounded-full border border-slate-700">
                VARIANCE: <span id="variance_val">0.000g</span>
            </div>
        </div>

        <div id="submit_area" class="hidden flex flex-col items-center">
            <div id="mode_toggle_container" class="hidden mb-6">
                <button id="state_btn" onclick="cycleState()" class="border border-slate-700 px-8 py-2 rounded-full orbitron text-[10px] tracking-widest transition-all text-slate-400">
                    STATUS: DEFAULT
                </button>
            </div>

            <button id="main_submit_btn" onclick="executeBackEnd()" class="orbitron px-16 py-6 rounded-2xl font-black text-xl tracking-[0.2em] transition-all">
                CONFIRM & SUBMIT
            </button>
            <button onclick="location.reload()" class="mt-6 text-[10px] orbitron text-slate-500 hover:text-white uppercase tracking-widest">↻ Reset & Re-weigh</button>
        </div>

        <div id="scan_prompt" class="orbitron text-sm animate-pulse uppercase tracking-widest">
            PLEASE SCAN CARD OR ITEM TAG
        </div>
    </div>
</div>

<script>
const urlParams = new URLSearchParams(window.location.search);
const currentMode = (urlParams.get('mode') || 'IN').toUpperCase();

// ─────────────────────────────────────────────────────────
// SCALE_CONFIG — the only place port settings should be edited.
// These must match the Shimadzu TX3202L's own COM.SET menu exactly.
// Current values were read directly off the balance's COM.SET screen.
// ─────────────────────────────────────────────────────────
const SCALE_CONFIG = {
    baudRate: 1200,          // BPS on the balance
    dataBits: 8,
    parity: 'none',          // P.None
    stopBits: 1,             // S.1
    flowControl: 'hardware'  // HS.HW — set to 'none' if you change the balance to HS.None
};

// Dynamic UI Elements
const statusPill = document.getElementById('status_pill');
const submitBtn = document.getElementById('main_submit_btn');
const logo = document.getElementById('logo_link');
const scanPrompt = document.getElementById('scan_prompt');
const inputMonitor = document.getElementById('input_monitor_box');
const senderLbl = document.getElementById('sender_label_color');
const receiverLbl = document.getElementById('receiver_label_color');

if (currentMode === 'IN') {
    statusPill.innerText = `MODE: INBOUND (RETURN)`;
    statusPill.classList.add('text-yellow-400');
    logo.classList.add('text-yellow-500');
    submitBtn.classList.add('bg-yellow-500', 'text-black', 'glow-btn-yellow');
    scanPrompt.classList.add('text-yellow-400');
    inputMonitor.classList.add('bg-yellow-500/20', 'border-yellow-500/50', 'text-yellow-400');
    senderLbl.classList.add('text-yellow-500');
    receiverLbl.classList.add('text-yellow-500');
    document.getElementById('mode_toggle_container').classList.remove('hidden');
} else {
    statusPill.innerText = `MODE: OUTBOUND (ISSUE)`;
    statusPill.classList.add('text-red-500');
    logo.classList.add('text-red-600');
    submitBtn.classList.add('bg-red-600', 'text-white', 'glow-btn-red');
    scanPrompt.classList.add('text-red-500');
    inputMonitor.classList.add('bg-red-500/20', 'border-red-500/50', 'text-red-400');
    senderLbl.classList.add('text-red-500');
    receiverLbl.classList.add('text-red-500');
}

// State Variables
let finalWeight = 0.000;
let dbOutboundWeight = null;
let weightVariance = 0.000;
let itemStatus = 0;
let scanBuffer = "";
let timer = null;
let senderData = null;
let barcodeData = null;

let deptMap = {};

async function loadDepartments() {
    try {
        const response = await fetch("get_departments.php");

        if (!response.ok) {
            throw new Error(`HTTP error: ${response.status}`);
        }

        deptMap = await response.json();

        console.log("Departments loaded:", deptMap);
    } catch (error) {
        console.error("Failed to load departments:", error);
    }
}

loadDepartments();
tryAutoConnectScale();

function cycleState() {
    itemStatus = (itemStatus + 1) % 3;
    const btn = document.getElementById('state_btn');
    btn.classList.remove('state-not-ready', 'state-powder', 'text-slate-400');
    
    if (itemStatus === 0) {
        btn.innerText = "STATUS: DEFAULT";
        btn.classList.add('text-slate-400');
    } else if (itemStatus === 1) {
        btn.innerText = "STATUS: NOT READY";
        btn.classList.add('state-not-ready');
    } else if (itemStatus === 2) {
        btn.innerText = "STATUS: POWDER";
        btn.classList.add('state-powder');
    }
}

window.addEventListener('keydown', (e) => {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
    if (e.key === 'Enter') {
        handleScan(scanBuffer.trim());
        scanBuffer = "";
        document.getElementById('monitor_ui').classList.add('opacity-0');
    } else if (e.key.length === 1) {
        scanBuffer += e.key;
        document.getElementById('live_text').innerText = scanBuffer;
        document.getElementById('monitor_ui').classList.remove('opacity-0');
        clearTimeout(timer);
        timer = setTimeout(() => { 
            scanBuffer = ""; 
            document.getElementById('monitor_ui').classList.add('opacity-0'); 
        }, 2000);
    }
});

function getDeptName(id) {
    return deptMap[id] || id;
}

// ============================================================
// CENTRAL ROUTER FOR SCANS - QR CODE vs CARD SEPARATE LOGIC
// ============================================================
function handleScan(code) {
    console.log(`[Scanner] Mode: ${currentMode}, Code: ${code}`);

    // 1. MASTER CARD SWITCH CHECK
    if( code === '0005508764' || code === '0005525546' || code === '0005542541' || code === '0005508437' || code === '0005523764' ) {
        Swal.fire({ title: 'SWITCHING TO SESSION MODE', text: 'Master batch card recognized. Redirecting...', icon: 'info', background: '#020617', color: '#fff', timer: 1200, showConfirmButton: false });
        setTimeout(() => {
            window.location.href = `../msession3/index.php`;
        }, 1200);
        return;
    }

    // 2. CHECK IF IT IS THE NEW BARCODE FORMAT (QR CODE)
    if (code.startsWith('}@sdept@') || code.includes('@randomcode@')) {
        const parsedData = parseCustomBarcode(code);
        if (parsedData && parsedData.random_code) {
            handleParsedBarcode(parsedData);
            return;
        } else {
            Swal.fire({ icon: 'error', title: 'PARSE ERROR', text: 'Could not extract valid fields from barcode format.', background: '#020617', color: '#fff' });
            return;
        }
    }

    // 3. IF QR CODE IS ALREADY SCANNED, NOW EXPECT USER ID CARD
    if (barcodeData && !senderData) {
        handleUserCard(code);
        return;
    }

    // Fallback if user taps card before scanning barcode
    if (!barcodeData) {
        Swal.fire({
            icon: 'warning',
            title: 'SCAN BARCODE FIRST',
            text: 'Please scan the transfer barcode before tapping your user ID card.',
            background: '#020617', color: '#fff'
        });
        return;
    }

    // ============================================================
    // USER CARD SCAN - CARD LOGIC (different from QR)
    // ============================================================
    fetch(`../check_card.php?code=${code}`)
        .then(res => res.json())
        .then(data => {
            if (data.status !== "authorized") {
                Swal.fire({
                    icon: 'error',
                    title: 'ACCESS DENIED',
                    text: `Card ${code} not recognized`,
                    background: '#020617', color: '#fff'
                });
                return;
            }

            if (senderData) return;
            senderData = data;

            // ============================================================
            // CARD LOGIC: SWAPPED (different from QR)
            // Card has: sDept=SAFE3, dDept=SAFE2
            // OUTBOUND: SAFE3 → SAFE2
            // INBOUND: SAFE2 → SAFE3
            // ============================================================
            let sDept, dDept;

            if (currentMode === 'OUT') {
                // CARD OUTBOUND: REVERSE (dDept → sDept)
                sDept = senderData.dDept;  // SAFE2
                dDept = senderData.sDept;  // SAFE3
            } else {
                // CARD INBOUND: Use as-is (sDept → dDept)
                sDept = senderData.sDept;  // SAFE3
                dDept = senderData.dDept;  // SAFE2
            }

            // Log the mapping for debugging
            console.log(`Mode: ${currentMode}, sDept: ${sDept}, dDept: ${dDept}`);
            console.log(`Card data: sDept: ${senderData.sDept}, dDept: ${senderData.dDept}`);

            const sourceName = getDeptName(sDept);
            const destName = getDeptName(dDept);

            // Store for later use
            senderData.activeSource = sDept;
            senderData.activeDest = dDept;

            // -----------------------------
            // SOURCE CARD
            // -----------------------------
            document.getElementById('source_status').innerText = `Account: ${sourceName}`;
            document.getElementById('source_name').innerText = sourceName;
            document.getElementById('source_avatar').src =
                `https://ui-avatars.com/api/?name=${encodeURIComponent(data.owner)}&background=3b82f6&color=fff`;
            document.getElementById('user_card_source').classList.add('active', 'verified');

            // -----------------------------
            // DEST CARD (AUTO MODE)
            // -----------------------------
            document.getElementById('dest_dept_name').innerText = destName;
            document.getElementById('dest_status').innerText = `AUTO ROUTED`;
            document.getElementById('dest_avatar').src =
                `https://ui-avatars.com/api/?name=${encodeURIComponent(destName)}&background=eab308&color=000`;
            document.getElementById('user_card_dest').classList.add('active', 'verified');

            // Show what mode is being used
            const modeLabel = currentMode === 'OUT' ? 'OUTBOUND (ISSUE)' : 'INBOUND (RETURN)';
            document.getElementById('scan_prompt').innerText = `${modeLabel} - WEIGHT READY`;
            
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'System failure while scanning.', 'error');
        });
}

function parseCustomBarcode(rawCode) {
    try {
        const tokens = rawCode.split(',@');
        
        let parsed = {
            s_dept: null,
            d_dept: null,
            random_code: null,
            remark: 'N/A'
        };

        tokens.forEach(token => {
            let cleanToken = token.replace(/\|/g, '');

            if (cleanToken.includes('sdept@')) {
                let noQuotes = cleanToken.replace(/'/g, '');
                let match = noQuotes.match(/sdept@([^,]+)/);
                parsed.s_dept = match ? match[1].trim() : null;
            } else if (cleanToken.includes('ddept@')) {
                let noQuotes = cleanToken.replace(/'/g, '');
                let match = noQuotes.match(/ddept@([^,]+)/);
                parsed.d_dept = match ? match[1].trim() : null;
            } else if (cleanToken.includes('randomcode@')) {
                let match = cleanToken.match(/@'?([^@']+)'?/);
                parsed.random_code = match ? match[1].replace(/@/g, '').trim() : null;
            } else if (cleanToken.includes('remark@')) {
                let match = cleanToken.match(/@'?([^@']+)'?/);
                parsed.remark = match ? match[1].replace(/@/g, '').trim() : 'N/A';
            }
        });

        // Fallback regex matching ensuring no single quotes are captured
        if (!parsed.s_dept) {
            let m = rawCode.match(/@sdept@'([^']+)'/) || rawCode.match(/@sdept@([^,]+)/);
            if (m) parsed.s_dept = m[1].replace(/'/g, '').trim();
        }
        if (!parsed.d_dept) {
            let m = rawCode.match(/@ddept@'([^']+)'/) || rawCode.match(/@ddept@([^,]+)/);
            if (m) parsed.d_dept = m[1].replace(/'/g, '').trim();
        }
        if (!parsed.random_code) {
            let m = rawCode.match(/@randomcode@'@?([^@'|]+)@?'/);
            if (m) parsed.random_code = m[1].replace(/[@']/g, '').trim();
        }
        if (parsed.remark === 'N/A' || !parsed.remark) {
            let m = rawCode.match(/@remark@'@?([^'@|]+)@?'/);
            if (m) parsed.remark = m[1].replace(/[@']/g, '').trim();
        }

        return parsed;
    } catch (err) {
        console.error("Barcode parsing failed:", err);
        return null;
    }
}

// STEP A: HANDLE PARSED CUSTOM BARCODE (QR CODE)
async function handleParsedBarcode(data) {
    const tagCode = data.random_code;

    if (!tagCode) {
        Swal.fire({ icon: 'error', title: 'INVALID BARCODE', text: 'Barcode missing random code parameter.', background: '#020617', color: '#fff' });
        return;
    }

    Swal.fire({ title: 'VERIFYING TAG...', background: '#020617', color: '#fff', showConfirmButton: false, didOpen: () => Swal.showLoading() });

    try {
        const url = `../get_tag_info.php?code=${encodeURIComponent(tagCode)}&mode=${currentMode}`;
        const res = await fetch(url);
        const tagInfo = await res.json();
        Swal.close();

        // --- INBOUND VALIDATION ---
        if (currentMode === 'IN') {
            if (!tagInfo.success || !tagInfo.outbound_weight) {
                Swal.fire({
                    icon: 'error',
                    title: 'INVALID BARCODE',
                    text: tagInfo.message || `No active outbound transfer record found for Tag [${tagCode}] within the past 24 hours.`,
                    background: '#020617', color: '#fff'
                });
                return;
            }

            if (tagInfo.is_expired || tagInfo.hours_diff > 24) {
                Swal.fire({
                    icon: 'error',
                    title: 'EXPIRED BARCODE',
                    text: `Tag [${tagCode}] was issued >24 hours ago. Return window expired.`,
                    background: '#020617', color: '#fff'
                });
                return;
            }

            if (tagInfo.already_returned) {
                Swal.fire({
                    icon: 'error',
                    title: 'ALREADY RETURNED',
                    text: `Tag [${tagCode}] has already been marked as returned!`,
                    background: '#020617', color: '#fff'
                });
                return;
            }

            barcodeData = data;
            dbOutboundWeight = parseFloat(tagInfo.outbound_weight);

            // ============================================================
            // QR CODE INBOUND: REVERSE (d_dept → s_dept)
            // QR has: s_dept=SAFE2, d_dept=SAFE2_POLISHING
            // INBOUND: SAFE2_POLISHING → SAFE2
            // ============================================================
            const sourceDeptId = data.d_dept;  // SAFE2_POLISHING
            const destDeptId   = data.s_dept;  // SAFE2

            document.getElementById('source_name').innerText = getDeptName(sourceDeptId);
            document.getElementById('source_status').innerText = `DEPT ID: ${sourceDeptId}`;
            document.getElementById('user_card_source').classList.add('active');

            document.getElementById('dest_dept_name').innerText = getDeptName(destDeptId);
            document.getElementById('dest_status').innerText = `DEPT ID: ${destDeptId}`;
            document.getElementById('user_card_dest').classList.add('active');

            document.getElementById('tag_meta_box').classList.remove('hidden');
            document.getElementById('meta_code').innerText = tagCode;
            document.getElementById('meta_remark').innerText = data.remark || tagInfo.remark || 'N/A';
            document.getElementById('meta_out_weight_row').classList.remove('hidden');
            document.getElementById('meta_out_weight').innerText = `${dbOutboundWeight.toFixed(3)}g`;

            barcodeData.s_dept = sourceDeptId;
            barcodeData.d_dept = destDeptId;
        } 
        // --- OUTBOUND VALIDATION ---
        else {
            if (tagInfo.exists || tagInfo.already_issued) {
                Swal.fire({
                    icon: 'error',
                    title: 'TAG ALREADY USED',
                    text: `Unique ID [${tagCode}] has already been issued!`,
                    background: '#020617', color: '#fff'
                });
                return;
            }

            barcodeData = data;
            dbOutboundWeight = null;

            // ============================================================
            // QR CODE OUTBOUND: Use as-is (s_dept → d_dept)
            // QR has: s_dept=SAFE2, d_dept=SAFE2_POLISHING
            // OUTBOUND: SAFE2 → SAFE2_POLISHING
            // ============================================================
            const sourceDeptId = data.s_dept;  // SAFE2
            const destDeptId   = data.d_dept;  // SAFE2_POLISHING

            if (sourceDeptId) {
                document.getElementById('source_name').innerText = getDeptName(sourceDeptId);
                document.getElementById('source_status').innerText = `DEPT ID: ${sourceDeptId}`;
                document.getElementById('user_card_source').classList.add('active');
            }

            if (destDeptId) {
                document.getElementById('dest_dept_name').innerText = getDeptName(destDeptId);
                document.getElementById('dest_status').innerText = `DEPT ID: ${destDeptId}`;
                document.getElementById('dest_avatar').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(getDeptName(destDeptId))}&background=eab308&color=000`;
                document.getElementById('user_card_dest').classList.add('active');
            }

            document.getElementById('tag_meta_box').classList.remove('hidden');
            document.getElementById('meta_code').innerText = tagCode;
            document.getElementById('meta_remark').innerText = data.remark || 'N/A';
            document.getElementById('meta_out_weight_row').classList.add('hidden');
        }

        Swal.fire({
            toast: true, position: 'top-end', icon: 'success',
            title: `BARCODE SCANNED`,
            text: `Now please tap your ID card.`,
            showConfirmButton: false, timer: 2000, background: '#020617', color: '#fff'
        });

        document.getElementById('scan_prompt').innerText = "PLEASE TAP USER ID CARD";

    } catch (err) {
        Swal.fire({ icon: 'error', title: 'VERIFICATION ERROR', text: 'Failed to communicate with database server.', background: '#020617', color: '#fff' });
    }
}

// STEP B: HANDLE USER ID CARD SCAN (After Barcode is verified)
function handleUserCard(code) {
    // ============================================================
    // CARD LOGIC: SWAPPED (different from QR)
    // ============================================================
    fetch(`../check_card.php?code=${code}`)
        .then(res => res.json())
        .then(data => {
            if (data.status !== "authorized") {
                Swal.fire({ icon: 'error', title: 'ACCESS DENIED', text: `Card ${code} not recognized`, background: '#020617', color: '#fff' });
                return;
            }

            senderData = data;

            let sDept, dDept;

            if (currentMode === 'OUT') {
                // CARD OUTBOUND: REVERSE (dDept → sDept)
                sDept = senderData.dDept;
                dDept = senderData.sDept;
            } else {
                // CARD INBOUND: Use as-is (sDept → dDept)
                sDept = senderData.sDept;
                dDept = senderData.dDept;
            }

            // Store for later use
            senderData.activeSource = sDept;
            senderData.activeDest = dDept;

            const sourceName = getDeptName(sDept);
            const destName = getDeptName(dDept);

            document.getElementById('source_name').innerText = sourceName;
            document.getElementById('source_status').innerText = `User: ${data.owner}`;
            document.getElementById('source_avatar').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(data.owner)}&background=3b82f6&color=fff`;
            document.getElementById('user_card_source').classList.add('verified');

            document.getElementById('dest_dept_name').innerText = destName;
            document.getElementById('dest_status').innerText = `AUTO ROUTED`;
            document.getElementById('user_card_dest').classList.add('verified');

            Swal.fire({
                toast: true, position: 'top-end', icon: 'success',
                title: `USER AUTHORIZED`,
                text: `Welcome, ${data.owner}`,
                showConfirmButton: false, timer: 1500, background: '#020617', color: '#fff'
            });

            document.getElementById('scan_prompt').innerText = "STABILIZING WEIGHT...";
            
        })
        .catch(err => Swal.fire('Error', 'System failure verifying user card.', 'error'));
}

// ═════════════════════════════════════════════════════════
// STEP C: SCALE CONNECTION (Web Serial — Shimadzu TX3202L, COM3)
// Replaces the old HTTP-polling scale service. The balance only
// transmits when PRINT is pressed on the unit itself, so this
// listens continuously and reacts each time a line comes in —
// there's no polling interval to manage.
// ═════════════════════════════════════════════════════════

let scalePort, scaleReader, scaleKeepReading = false, scaleReadableClosed;
let scaleLineBuffer = '';

function setScaleStatus(text, connected){
    const el = document.getElementById('scale_status_text');
    if (el) el.innerText = text;
    const btn = document.getElementById('scale_connect_btn');
    if (btn) btn.classList.toggle('hidden', !!connected);
}

// Parses a raw line from the scale into a weight value.
// Handles common formats like "+   12.500 g", "-21.6865g", "12.50".
function parseScaleLine(line){
    const cleaned = line.trim();
    if (!cleaned) return null;
    const match = cleaned.match(/([+-]?\s*\d+(?:\.\d+)?)\s*([a-zA-Z]{0,3})\s*$/);
    if (!match) return null;
    const weight = parseFloat(match[1].replace(/\s+/g, ''));
    return isNaN(weight) ? null : weight;
}

// Manual connect — only needed the very first time (one click),
// after which the browser remembers the port and reconnects silently.
async function connectScale(){
    if (!('serial' in navigator)){
        setScaleStatus('Unsupported — use Chrome/Edge over https:// or localhost', false);
        Swal.fire({
            icon: 'error',
            title: 'SCALE CONNECTION UNAVAILABLE',
            html: `Web Serial isn't available in this browser context.<br><br>
                   This page must be opened in <b>Chrome</b> or <b>Edge</b>,
                   and served over <b>https://</b> or <b>http://localhost</b>
                   (not a plain LAN IP address like http://192.168.x.x).`,
            background: '#020617', color: '#fff'
        });
        return;
    }
    try {
        scalePort = await navigator.serial.requestPort();
        await openScalePort();
    } catch (err){
        console.error(err);
        setScaleStatus('Connection failed', false);
    }
}

async function openScalePort(){
    await scalePort.open({
        baudRate: SCALE_CONFIG.baudRate,
        dataBits: SCALE_CONFIG.dataBits,
        parity: SCALE_CONFIG.parity,
        stopBits: SCALE_CONFIG.stopBits,
        flowControl: SCALE_CONFIG.flowControl
    });
    // Some USB-to-serial adapters won't drive the line properly unless
    // DTR/RTS are explicitly asserted, regardless of the flowControl mode.
    try {
        await scalePort.setSignals({ dataTerminalReady: true, requestToSend: true });
    } catch (sigErr){
        console.warn('setSignals not supported/failed:', sigErr);
    }

    setScaleStatus('Connected', true);
    scaleKeepReading = true;
    scaleReadLoop();
}

async function scaleReadLoop(){
    // Outer loop recreates the reader after recoverable line errors
    // (BreakError / FramingError / ParityError) instead of dying —
    // those usually mean the port settings drifted from SCALE_CONFIG.
    while (scaleKeepReading){
        const textDecoder = new TextDecoderStream();
        scaleReadableClosed = scalePort.readable.pipeTo(textDecoder.writable).catch(() => {});
        scaleReader = textDecoder.readable.getReader();

        try {
            while (scaleKeepReading){
                const { value, done } = await scaleReader.read();
                if (done){ scaleKeepReading = false; break; }
                if (value){
                    scaleLineBuffer += value;
                    let idx;
                    while ((idx = scaleLineBuffer.search(/[\r\n]/)) >= 0){
                        const line = scaleLineBuffer.slice(0, idx);
                        scaleLineBuffer = scaleLineBuffer.slice(idx + 1);
                        if (line.trim().length > 0){
                            const w = parseScaleLine(line);
                            if (w !== null) onScaleWeight(w);
                        }
                    }
                }
            }
        } catch (err){
            console.warn('Scale read error (recovering):', err);
            const name = err && err.name ? err.name : '';
            if (name === 'BreakError' || name === 'FramingError' || name === 'ParityError'){
                setScaleStatus(`Connected — line noise (${name})`, true);
            } else {
                setScaleStatus('Connected — read error', true);
            }
        } finally {
            try { scaleReader.releaseLock(); } catch(e){}
        }

        if (scaleKeepReading){
            await new Promise(r => setTimeout(r, 150));
        }
    }
}

// Called on page load — silently reconnects to a previously-granted
// port with no click needed. If nothing was granted yet, shows the
// CONNECT button for the one-time setup click.
async function tryAutoConnectScale(){
    if (!('serial' in navigator)) {
        setScaleStatus('Web Serial unsupported (use Chrome/Edge)', false);
        return;
    }
    try {
        const ports = await navigator.serial.getPorts();
        if (ports.length > 0){
            scalePort = ports[0];
            await openScalePort();
        } else {
            setScaleStatus('Not connected', false);
        }
    } catch (err){
        console.warn('Auto-connect failed:', err);
        setScaleStatus('Not connected', false);
    }
}

navigator.serial?.addEventListener?.('disconnect', () => {
    scaleKeepReading = false;
    setScaleStatus('Disconnected', false);
});
navigator.serial?.addEventListener?.('connect', async () => {
    if (!scaleKeepReading) await tryAutoConnectScale();
});

// Fires every time the balance sends a reading (i.e. every PRINT press).
// This is the direct replacement for the old fetch-success branch.
function onScaleWeight(w){
    if (w <= 0.001){
        document.getElementById('weight_display').innerHTML = `WAITING...<span class="text-3xl text-slate-600 ml-2">g</span>`;
        return;
    }

    finalWeight = w;
    document.getElementById('weight_display').innerHTML = `${w.toFixed(3)}<span class="text-3xl text-slate-600 ml-2">g</span>`;

    if (dbOutboundWeight !== null) {
        weightVariance = finalWeight - dbOutboundWeight;
        const varBox = document.getElementById('variance_display');
        const varVal = document.getElementById('variance_val');

        varBox.classList.remove('hidden');
        if (weightVariance < -0.0001) {
            varVal.innerText = `${weightVariance.toFixed(3)}g (LOSS)`;
            varBox.className = "mt-2 orbitron text-sm font-bold px-4 py-1 rounded-full border border-red-500/50 bg-red-500/10 text-red-400";
        } else if (weightVariance > 0.0001) {
            varVal.innerText = `+${weightVariance.toFixed(3)}g (GAIN)`;
            varBox.className = "mt-2 orbitron text-sm font-bold px-4 py-1 rounded-full border border-yellow-500/50 bg-yellow-500/10 text-yellow-400";
        } else {
            varVal.innerText = `0.000g (EXACT MATCH)`;
            varBox.className = "mt-2 orbitron text-sm font-bold px-4 py-1 rounded-full border border-emerald-500/50 bg-emerald-500/10 text-emerald-400";
        }
    }

    document.getElementById('submit_area').classList.remove('hidden');
    const prompt = document.getElementById('scan_prompt');
    prompt.innerText = "WEIGHT CAPTURED";
    prompt.style.color = (currentMode === 'IN') ? '#fbbf24' : '#ef4444';
}

// STEP D: SUBMIT PAYLOAD
async function executeBackEnd() {
    if (finalWeight <= 0) {
        Swal.fire({ icon: 'error', title: 'INVALID WEIGHT', text: 'Weight must be greater than 0.000g.', background: '#020617', color: '#fff' });
        return;
    }

    if (!senderData) {
        Swal.fire({ icon: 'error', title: 'USER ID MISSING', text: 'Please tap your user ID card before confirming.', background: '#020617', color: '#fff' });
        return;
    }

    let finalSource = senderData?.activeSource ?? barcodeData?.s_dept;
    let finalDest   = senderData?.activeDest ?? barcodeData?.d_dept;

    // ============================================================
    // PRODUCT NAME: Use ISSUE_TRANS for OUTBOUND, RETURN_TRANS for INBOUND
    // ============================================================
    let productNamePayload = (currentMode === 'IN') ? "RETURN_TRANS" : "ISSUE_TRANS";

    let statusLabel = "";
    if (itemStatus === 1) statusLabel = "[NOT READY]";
    else if (itemStatus === 2) statusLabel = "[POWDER]";

    let remarkText = `Handover ${currentMode}bound ${statusLabel}`.trim();
    if (barcodeData && barcodeData.remark) {
        remarkText += ` - ${barcodeData.remark}`;
    }

    const payload = {
        sourceDept: finalSource,
        destinationDept: finalDest,
        sender: senderData ? senderData.owner : "UNKNOWN",
        random_code: barcodeData ? barcodeData.random_code : null,
        outbound_weight: dbOutboundWeight,
        returned_weight: finalWeight,
        variance_weight: weightVariance,
        transfers: [{
            productname: productNamePayload,
            amount: finalWeight,
            remark: remarkText,
            sourceDept: finalSource,
            destinationDept: finalDest
        }]
    };

    Swal.fire({ title: 'RECORDING...', background: '#020617', color: '#fff', showConfirmButton: false, didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(`save_handover.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });

        const result = await res.json();

        if (result.success) {
            Swal.fire({ 
                icon: 'success', 
                title: 'TRANSFER COMPLETE', 
                text: (dbOutboundWeight !== null && Math.abs(weightVariance) > 0.0001) ? `Variance: ${weightVariance.toFixed(3)}g` : '',
                background: '#020617', 
                color: '#fff', 
                timer: 1200, 
                showConfirmButton: false 
            }).then(() => location.href = 'index.php');
        } else {
            Swal.fire({ icon: 'error', title: 'SAVE FAILED', text: result.message || 'Unknown database error', background: '#020617', color: '#fff' });
        }
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'NETWORK FAILURE', text: err.message, background: '#020617', color: '#fff' });
    }
}
</script>
</body>
</html>