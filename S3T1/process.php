<?php
// process.php – full file with modal edit and fixed input focus
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MILANO GOLD TRANSFER</title>
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

    .glass { 
        background: var(--glass-bg); 
        backdrop-filter: blur(16px); 
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid var(--glass-border); 
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37), inset 0 1px 0 0 rgba(255, 255, 255, 0.1);
    }

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
        opacity: 1 !important; 
        filter: grayscale(0) !important;
        transform: scale(1);
        border-color: #10b981 !important; 
        box-shadow: 0 0 25px var(--glow-green) !important; 
        background: rgba(16, 185, 129, 0.05);
    }

    /* Enhanced department card styling – with vivid colors */
    .dept-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 1.5rem;
        padding: 1rem 1.5rem;
        transition: all 0.3s ease;
    }
    .dept-card .dept-name {
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        transition: color 0.3s ease;
    }
    /* Source card name – bright blue */
    .source-card .dept-name {
        color: #60a5fa;
    }
    .source-card.verified .dept-name {
        color: #93c5fd;
        text-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
    }
    /* Destination card name – gold/yellow */
    .dest-card .dept-name {
        color: #fbbf24;
    }
    .dest-card.verified .dept-name {
        color: #fcd34d;
        text-shadow: 0 0 20px rgba(234, 179, 8, 0.3);
    }

    .dept-card .dept-id {
        font-size: 0.7rem;
        font-weight: 600;
        color: #94a3b8;
        font-family: monospace;
        margin-top: 2px;
    }
    .dept-card.source-card {
        border-left: 4px solid #3b82f6;
        background: rgba(59, 130, 246, 0.05);
    }
    .dept-card.dest-card {
        border-left: 4px solid #eab308;
        background: rgba(234, 179, 8, 0.05);
    }
    .dept-card.verified {
        border-color: #10b981 !important;
        box-shadow: 0 0 30px rgba(16, 185, 129, 0.15);
        background: rgba(16, 185, 129, 0.05);
    }
    .dept-card .label {
        font-size: 0.6rem;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: #94a3b8;
        font-weight: 600;
    }
    .dept-card .user-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: #cbd5e1;
    }

    /* Edit remark button: hidden by default, shown after QR scan */
    #editRemarkBtn {
        display: none;
    }

    #weight_display {
        text-shadow: 0 0 30px rgba(255, 255, 255, 0.25), 0 0 60px rgba(59, 130, 246, 0.15);
        letter-spacing: -0.02em;
        transition: all 0.3s ease;
    }
    #weight_display.has-weight {
        color: #fbbf24;
        text-shadow: 0 0 40px rgba(251, 191, 36, 0.3), 0 0 80px rgba(251, 191, 36, 0.1);
    }

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

    #input_monitor_box {
        background: rgba(15, 23, 42, 0.9);
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
    }

    @keyframes subtle-pulse {
        0%, 100% { opacity: 0.9; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(0.98); }
    }
    .animate-pulse {
        animation: subtle-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    .remark-display {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 12px 16px;
        margin-top: 8px;
        text-align: center;
        position: relative;
    }
    .remark-display .label {
        font-size: 9px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        font-weight: 600;
    }
    .remark-display .value {
        font-size: 18px;
        font-weight: 700;
        color: #fbbf24;
        margin-top: 2px;
        font-family: monospace;
        word-break: break-all;
    }
    .remark-display .edit-btn {
        position: absolute;
        top: 8px;
        right: 8px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        padding: 4px 8px;
        font-size: 10px;
        color: #94a3b8;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
    }
    .remark-display .edit-btn:hover {
        background: rgba(251, 191, 36, 0.15);
        border-color: #fbbf24;
        color: #fbbf24;
        transform: scale(1.05);
    }

    .scan-mode-indicator {
        font-size: 10px;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 700;
        letter-spacing: 0.05em;
    }
    .scan-mode-card {
        background: rgba(59, 130, 246, 0.2);
        color: #60a5fa;
        border: 1px solid rgba(59, 130, 246, 0.3);
    }
    .scan-mode-qr {
        background: rgba(139, 92, 246, 0.2);
        color: #a78bfa;
        border: 1px solid rgba(139, 92, 246, 0.3);
    }
    .status-waiting {
        background: rgba(234, 179, 8, 0.15);
        color: #fbbf24;
        border: 1px solid rgba(234, 179, 8, 0.3);
    }
    .status-ready {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .status-card-only {
        background: rgba(59, 130, 246, 0.15);
        color: #60a5fa;
        border: 1px solid rgba(59, 130, 246, 0.3);
    }
    .status-weight-captured {
        background: rgba(251, 191, 36, 0.2);
        color: #fbbf24;
        border: 1px solid rgba(251, 191, 36, 0.4);
        animation: pulse-gold 1.5s ease-in-out infinite;
    }
    .status-in-transit {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.4);
    }
    @keyframes pulse-gold {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.7; transform: scale(1.02); }
    }

    .mode-toggle-btn {
        transition: all 0.3s ease;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.03);
        cursor: pointer;
    }
    .mode-toggle-btn.active-card {
        border-color: #3b82f6;
        background: rgba(59, 130, 246, 0.15);
        box-shadow: 0 0 30px rgba(59, 130, 246, 0.15);
    }
    .mode-toggle-btn.active-qr {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.15);
        box-shadow: 0 0 30px rgba(139, 92, 246, 0.15);
    }
    .mode-toggle-btn:hover {
        transform: translateY(-2px);
    }

    .weight-captured-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.05em;
        background: rgba(251, 191, 36, 0.15);
        color: #fbbf24;
        border: 1px solid rgba(251, 191, 36, 0.3);
        animation: pulse-gold 1.5s ease-in-out infinite;
    }
    
    .qr-status-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 9px;
        font-weight: 600;
        margin-top: 4px;
    }
    .qr-status-active {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .qr-status-transit {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
        animation: pulse-gold 1.5s ease-in-out infinite;
    }
    .qr-status-returned {
        background: rgba(59, 130, 246, 0.15);
        color: #60a5fa;
        border: 1px solid rgba(59, 130, 246, 0.3);
    }

    .qr-remark-box {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 12px 16px;
        margin-top: 8px;
        text-align: center;
        display: none;
    }
    .qr-remark-box.visible {
        display: block;
    }
    .qr-remark-box .label {
        font-size: 9px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        font-weight: 600;
    }
    .qr-remark-box .value {
        font-size: 18px;
        font-weight: 700;
        color: #fbbf24;
        margin-top: 2px;
        font-family: monospace;
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
        <!-- Mode Switcher -->
        <div class="glass rounded-xl p-4 border border-white/10">
            <p class="orbitron text-[9px] text-slate-500 tracking-[0.2em] mb-3 uppercase text-center">Scan Mode</p>
            <div class="grid grid-cols-2 gap-3">
                <button id="btnCardMode" onclick="setScanMode('card')" class="mode-toggle-btn active-card rounded-2xl p-3 text-center transition-all">
                    <div class="text-2xl mb-1 text-blue-400">🪪</div>
                    <div class="orbitron text-[10px] font-bold text-blue-400">CARD MODE</div>
                    <div class="text-[8px] text-slate-600">Scan card → path</div>
                </button>
                <button id="btnQRMode" onclick="setScanMode('qr')" class="mode-toggle-btn rounded-2xl p-3 text-center transition-all">
                    <div class="text-2xl mb-1 text-purple-400">📱</div>
                    <div class="orbitron text-[10px] font-bold text-purple-400">QR MODE</div>
                    <div class="text-[8px] text-slate-600">QR → card → path</div>
                </button>
            </div>
            <div class="mt-3 text-center">
                <span id="scanModeIndicator" class="scan-mode-indicator scan-mode-card">CARD MODE</span>
            </div>
        </div>

        <!-- Source Card - Enhanced Visibility with color -->
        <div id="user_card_source" class="id-card dept-card source-card glass p-4 rounded-xl flex flex-col border border-transparent">
            <div class="flex items-center gap-4">
                <img id="source_avatar" src="https://ui-avatars.com/api/?name=User&background=3b82f6&color=fff" class="w-12 h-12 rounded-lg">
                <div class="flex-1">
                    <p id="sender_label_color" class="label text-white">Source</p>
                    <h2 id="source_name" class="dept-name">---</h2>
                    <div class="flex items-center gap-2 mt-1">
                        <span id="source_id_display" class="dept-id">ID: ---</span>
                        <span id="source_status" class="text-[10px] text-white italic">Wait for scan...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Destination Card - Enhanced Visibility with color -->
        <div id="user_card_dest" class="id-card dept-card dest-card glass p-4 rounded-xl flex flex-col border border-transparent">
            <div class="flex items-center gap-4">
                <img id="dest_avatar" src="https://ui-avatars.com/api/?name=Dept&background=eab308&color=000" class="w-12 h-12 rounded-lg">
                <div class="flex-1">
                    <p id="receiver_label_color" class="label text-white">Destination</p>
                    <h2 id="dest_dept_name" class="dept-name">---</h2>
                    <div class="flex items-center gap-2 mt-1">
                        <span id="dest_id_display" class="dept-id">ID: ---</span>
                        <span id="dest_status" class="text-[10px] text-white italic">Waiting...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Remark Display with Edit Button (modal) -->
        <div id="qr_remark_box" class="hidden glass p-4 rounded-xl border border-purple-500/30">
            <div class="remark-display" id="remarkDisplay">
                <div class="label">📋 QR Remark / Customer Code</div>
                <div class="value" id="qr_remark_value">---</div>
                <!-- Edit button: hidden by default, shown after QR scan, opens modal -->
                <button id="editRemarkBtn" class="edit-btn" onclick="editRemark()">✎ Edit</button>
            </div>
            <div class="mt-2 text-center">
                <span id="qr_status_text" class="scan-mode-indicator status-waiting">⏳ Awaiting QR Scan</span>
            </div>
            <div id="qr_tracking_status" class="hidden mt-2 text-center">
                <span id="qr_tracking_badge" class="qr-status-badge qr-status-active">✅ Active</span>
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
            <div id="meta_status_row" class="flex justify-between hidden">
                <span class="text-slate-500">QR STATUS:</span>
                <span id="meta_status" class="orbitron text-yellow-400 font-bold">---</span>
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
            <div id="weight_display" class="orbitron text-[110px] font-black text-white leading-none">0.000<span class="text-3xl text-slate-600 ml-2">g</span></div>
            
            <div id="weight_status_badge" class="hidden mt-2">
                <span class="weight-captured-badge">⚖️ WEIGHT CAPTURED - Review & Submit</span>
            </div>
            
            <div id="variance_display" class="hidden mt-2 orbitron text-sm font-bold px-4 py-1 rounded-full border border-slate-700">
                VARIANCE: <span id="variance_val">0.000g</span>
            </div>
        </div>

        <div id="submit_area" class="hidden flex flex-col items-center w-full max-w-md">
            <div id="mode_toggle_container" class="hidden mb-4">
                <button id="state_btn" onclick="cycleState()" class="border border-slate-700 px-8 py-2 rounded-full orbitron text-[10px] tracking-widest transition-all text-slate-400">
                    STATUS: DEFAULT
                </button>
            </div>

            <div class="w-full flex flex-col gap-3">
                <button id="main_submit_btn" onclick="executeBackEnd()" class="orbitron w-full py-5 rounded-2xl font-black text-xl tracking-[0.2em] transition-all">
                    ✅ CONFIRM & SUBMIT
                </button>
                <button onclick="resetWeight()" class="text-[10px] orbitron text-slate-500 hover:text-white uppercase tracking-widest">
                    ↻ Reset Weight & Re-weigh
                </button>
            </div>
        </div>

        <div id="scan_prompt" class="orbitron text-sm animate-pulse uppercase tracking-widest">
            SCAN CARD OR QR
        </div>
        
        <!-- Scan Step Indicator -->
        <div class="mt-4 flex gap-3">
            <span id="scan_mode_indicator" class="scan-mode-indicator scan-mode-card">🪪 CARD MODE</span>
            <span id="scan_step_indicator" class="scan-mode-indicator status-card-only">⏳ Scan Card</span>
        </div>
    </div>
</div>

<script>
const urlParams = new URLSearchParams(window.location.search);
const currentMode = (urlParams.get('mode') || 'IN').toUpperCase();

// ─────────────────────────────────────────────────────────
// SCALE_CONFIG
// ─────────────────────────────────────────────────────────
const SCALE_CONFIG = {
    baudRate: 1200,
    dataBits: 8,
    parity: 'none',
    stopBits: 1,
    flowControl: 'hardware'
};

// Scan Mode: 'card' or 'qr'
let scanMode = 'card';

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
let currentWeight = 0.000;
let capturedWeight = 0.000;
let hasCapturedWeight = false;
let dbOutboundWeight = null;
let weightVariance = 0.000;
let itemStatus = 0;
let scanBuffer = "";
let timer = null;
let senderData = null;
let barcodeData = null;
let qrRemark = null;
let qrRandomCode = null;
let qrTrackingStatus = null;
let isQRScanned = false;
let isCardScanned = false;
let isCardOnlyMode = true;
let pendingData = null;
let deptMap = {};

// ============================================================
// GET DEPARTMENT NAME
// ============================================================
function getDeptName(id) {
    if (deptMap && deptMap[id] !== undefined && deptMap[id] !== null) {
        return deptMap[id];
    }
    return id;
}

// ============================================================
// LOAD DEPARTMENTS
// ============================================================
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

// ============================================================
// REMARK EDIT FUNCTIONS – Modal version with auto‑focus
// ============================================================
function editRemark() {
    const currentValue = document.getElementById('qr_remark_value').textContent;
    Swal.fire({
        title: 'Edit Remark',
        html: `
            <label for="swal-input" class="text-slate-300 text-sm block text-left mb-1">Enter new remark / customer code:</label>
            <input id="swal-input" class="swal2-input" type="text" maxlength="50" value="${currentValue !== '---' ? currentValue : ''}" placeholder="Type new remark...">
        `,
        background: '#020617',
        color: '#fff',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#ef4444',
        showCancelButton: true,
        confirmButtonText: '✓ Save',
        cancelButtonText: '✕ Cancel',
        didOpen: () => {
            const input = document.getElementById('swal-input');
            if (input) {
                setTimeout(() => input.focus(), 100);
            }
        },
        preConfirm: () => {
            const val = document.getElementById('swal-input').value.trim();
            if (val === '') {
                Swal.showValidationMessage('Remark cannot be empty');
                return false;
            }
            return val;
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const newValue = result.value;
            document.getElementById('qr_remark_value').textContent = newValue;
            document.getElementById('meta_remark').textContent = newValue;
            
            if (pendingData) {
                if (pendingData.remark.includes('| Authorized:')) {
                    pendingData.remark = `QR: ${newValue} | Authorized: ${pendingData.authorizedBy}`;
                } else {
                    pendingData.remark = `QR: ${newValue} | Authorized: ${pendingData.authorizedBy}`;
                }
                console.log("📝 Remark updated to:", pendingData.remark);
            }
            qrRemark = newValue;
            
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Remark Updated',
                text: `New remark: ${newValue}`,
                showConfirmButton: false,
                timer: 2000,
                background: '#020617',
                color: '#fff'
            });
        }
    });
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

// Keyboard scanner listener – skip when SweetAlert is open
window.addEventListener('keydown', (e) => {
    // If a SweetAlert modal is open, ignore scanner input so you can type freely
    if (document.body.classList.contains('swal2-shown')) return;
    
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

// ============================================================
// SET SCAN MODE
// ============================================================
function setScanMode(mode) {
    scanMode = mode;
    const cardBtn = document.getElementById('btnCardMode');
    const qrBtn = document.getElementById('btnQRMode');
    const indicator = document.getElementById('scanModeIndicator');
    const modeIndicator = document.getElementById('scan_mode_indicator');
    const qrBox = document.getElementById('qr_remark_box');
    
    resetSession();
    
    if (mode === 'card') {
        cardBtn.classList.add('active-card');
        cardBtn.classList.remove('active-qr');
        qrBtn.classList.remove('active-card', 'active-qr');
        indicator.className = 'scan-mode-indicator scan-mode-card';
        indicator.textContent = 'CARD MODE';
        modeIndicator.className = 'scan-mode-indicator scan-mode-card';
        modeIndicator.textContent = '🪪 CARD MODE';
        scanPrompt.textContent = 'SCAN CARD';
        qrBox.classList.add('hidden');
        isCardOnlyMode = true;
        document.getElementById('scan_step_indicator').className = 'scan-mode-indicator status-card-only';
        document.getElementById('scan_step_indicator').textContent = '⏳ Scan Card';
    } else {
        qrBtn.classList.add('active-qr');
        qrBtn.classList.remove('active-card');
        cardBtn.classList.remove('active-card', 'active-qr');
        indicator.className = 'scan-mode-indicator scan-mode-qr';
        indicator.textContent = 'QR MODE';
        modeIndicator.className = 'scan-mode-indicator scan-mode-qr';
        modeIndicator.textContent = '📱 QR MODE';
        scanPrompt.textContent = 'SCAN QR FIRST';
        qrBox.classList.remove('hidden');
        isCardOnlyMode = false;
        document.getElementById('qr_tracking_status').classList.add('hidden');
        document.getElementById('scan_step_indicator').className = 'scan-mode-indicator status-waiting';
        document.getElementById('scan_step_indicator').textContent = '📱 Step 1: Scan QR';
    }
    
    document.getElementById('editRemarkBtn').style.display = 'none';
    
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'info',
        title: `${mode.toUpperCase()} MODE`,
        text: mode === 'card' ? 'Scan card to determine path' : 'Scan QR → then card for path',
        showConfirmButton: false,
        timer: 2000,
        background: '#020617',
        color: '#fff'
    });
    
    barcodeInput.value = '';
    setTimeout(() => { barcodeInput.focus(); }, 100);
}

// ============================================================
// RESET SESSION
// ============================================================
function resetSession() {
    senderData = null;
    barcodeData = null;
    qrRemark = null;
    qrRandomCode = null;
    qrTrackingStatus = null;
    isQRScanned = false;
    isCardScanned = false;
    pendingData = null;
    hasCapturedWeight = false;
    capturedWeight = 0.000;
    currentWeight = 0.000;
    
    document.getElementById('editRemarkBtn').style.display = 'none';
    
    document.getElementById('weight_display').innerHTML = `0.000<span class="text-3xl text-slate-600 ml-2">g</span>`;
    document.getElementById('weight_display').classList.remove('has-weight');
    document.getElementById('submit_area').classList.add('hidden');
    document.getElementById('weight_status_badge').classList.add('hidden');
    document.getElementById('variance_display').classList.add('hidden');
    document.getElementById('qr_tracking_status').classList.add('hidden');
    document.getElementById('qr_remark_box').classList.add('hidden');
    document.getElementById('qr_remark_value').textContent = '---';
    
    document.getElementById('source_name').textContent = '---';
    document.getElementById('source_id_display').textContent = 'ID: ---';
    document.getElementById('source_status').textContent = 'Wait for scan...';
    document.getElementById('dest_dept_name').textContent = '---';
    document.getElementById('dest_id_display').textContent = 'ID: ---';
    document.getElementById('dest_status').textContent = 'Waiting...';
    document.getElementById('user_card_source').className = 'id-card dept-card source-card glass p-4 rounded-xl flex flex-col border border-transparent';
    document.getElementById('user_card_dest').className = 'id-card dept-card dest-card glass p-4 rounded-xl flex flex-col border border-transparent';
    document.getElementById('tag_meta_box').classList.add('hidden');
    
    if (isCardOnlyMode) {
        document.getElementById('scan_step_indicator').className = 'scan-mode-indicator status-card-only';
        document.getElementById('scan_step_indicator').textContent = '⏳ Scan Card';
        scanPrompt.textContent = 'SCAN CARD';
    } else {
        document.getElementById('scan_step_indicator').className = 'scan-mode-indicator status-waiting';
        document.getElementById('scan_step_indicator').textContent = '📱 Step 1: Scan QR';
        scanPrompt.textContent = 'SCAN QR FIRST';
        document.getElementById('qr_status_text').textContent = '⏳ Awaiting QR Scan';
        document.getElementById('qr_status_text').className = 'scan-mode-indicator status-waiting';
    }
}

// ============================================================
// RESET WEIGHT ONLY (keep session data)
// ============================================================
function resetWeight() {
    hasCapturedWeight = false;
    capturedWeight = 0.000;
    currentWeight = 0.000;
    
    document.getElementById('weight_display').innerHTML = `0.000<span class="text-3xl text-slate-600 ml-2">g</span>`;
    document.getElementById('weight_display').classList.remove('has-weight');
    document.getElementById('submit_area').classList.add('hidden');
    document.getElementById('weight_status_badge').classList.add('hidden');
    document.getElementById('variance_display').classList.add('hidden');
    
    scanPrompt.textContent = 'PRESS PRINT ON SCALE FOR NEW WEIGHT';
    scanPrompt.className = 'orbitron text-sm animate-pulse uppercase tracking-widest text-emerald-400';
    
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'info',
        title: 'WEIGHT RESET',
        text: 'Press PRINT on scale to capture new weight.',
        showConfirmButton: false,
        timer: 2000,
        background: '#020617',
        color: '#fff'
    });
}

// ============================================================
// UPDATE SCAN STEP INDICATOR (QR Mode)
// ============================================================
function updateQRStep(step) {
    const indicator = document.getElementById('scan_step_indicator');
    const qrBox = document.getElementById('qr_remark_box');
    
    if (step === 'qr') {
        indicator.textContent = '📱 Step 1: Scan QR';
        indicator.className = 'scan-mode-indicator status-waiting';
        qrBox.classList.remove('hidden');
        document.getElementById('qr_status_text').textContent = '⏳ Awaiting QR Scan';
        document.getElementById('qr_status_text').className = 'scan-mode-indicator status-waiting';
        scanPrompt.textContent = 'SCAN QR FIRST';
    } else if (step === 'card') {
        indicator.textContent = '🪪 Step 2: Scan Card';
        indicator.className = 'scan-mode-indicator status-waiting';
        document.getElementById('qr_status_text').textContent = '✅ QR Scanned - Scan Card';
        document.getElementById('qr_status_text').className = 'scan-mode-indicator status-ready';
        scanPrompt.textContent = 'SCAN CARD FOR PATH';
    } else if (step === 'ready') {
        indicator.textContent = '⚡ Press PRINT';
        indicator.className = 'scan-mode-indicator status-ready';
        document.getElementById('qr_status_text').textContent = '✅ Ready for Weight';
        document.getElementById('qr_status_text').className = 'scan-mode-indicator status-ready';
        scanPrompt.textContent = 'PRESS PRINT ON SCALE';
    } else if (step === 'captured') {
        indicator.textContent = '✅ Weight Captured';
        indicator.className = 'scan-mode-indicator status-weight-captured';
        document.getElementById('qr_status_text').textContent = '✅ Weight Captured - Review & Submit';
        document.getElementById('qr_status_text').className = 'scan-mode-indicator status-weight-captured';
        scanPrompt.textContent = 'WEIGHT CAPTURED - REVIEW & SUBMIT';
        scanPrompt.className = 'orbitron text-sm uppercase tracking-widest text-yellow-400 font-bold';
    }
}

// ============================================================
// CENTRAL ROUTER FOR SCANS
// ============================================================
function handleScan(code) {
    console.log(`[Scanner] Mode: ${currentMode}, ScanMode: ${scanMode}, Code: ${code}`);

    if( code === '0005508764' || code === '0005525546' || code === '0005542541' || code === '0005508437' || code === '0005523764' ) {
        Swal.fire({ 
            title: 'SWITCHING TO SESSION MODE', 
            text: 'Master batch card recognized. Redirecting...', 
            icon: 'info', 
            background: '#020617', 
            color: '#fff', 
            timer: 1200, 
            showConfirmButton: false 
        });
        setTimeout(() => {
            window.location.href = `../msession3/index.php`;
        }, 1200);
        return;
    }

    if (scanMode === 'qr' && (code.startsWith('}@sdept@') || code.includes('@randomcode@') || code.includes('{"random_code"'))) {
        const parsedData = parseQRCode(code);
        if (parsedData && parsedData.random_code) {
            handleQRScan(parsedData);
            return;
        } else {
            Swal.fire({ 
                icon: 'error', 
                title: 'PARSE ERROR', 
                text: 'Could not extract valid fields from QR code.', 
                background: '#020617', 
                color: '#fff' 
            });
            return;
        }
    }

    if (scanMode === 'qr' && isQRScanned && !isCardScanned) {
        handleCardScan(code);
        return;
    }

    if (scanMode === 'card' || (scanMode === 'qr' && !isQRScanned)) {
        handleCardScan(code);
        return;
    }

    if (scanMode === 'qr' && !isQRScanned) {
        Swal.fire({
            icon: 'warning',
            title: 'SCAN QR FIRST',
            text: 'In QR mode, please scan the QR code first, then your card.',
            background: '#020617',
            color: '#fff'
        });
        return;
    }
}

// ============================================================
// PARSE QR CODE - Handles multiple formats
// ============================================================
function parseQRCode(rawCode) {
    console.log("🔍 Parsing QR Code:", rawCode);
    
    try {
        let parsed = JSON.parse(rawCode);
        if (parsed.random_code) {
            return {
                random_code: parsed.random_code,
                remark: parsed.remark || 'N/A'
            };
        }
    } catch (e) {}

    let parsed = {
        random_code: null,
        remark: 'N/A'
    };

    let rMatch = rawCode.match(/@randomcode@'@([^@]+)@/);
    if (rMatch) {
        parsed.random_code = rMatch[1].replace(/[@']/g, '').trim();
        console.log("✅ Extracted random_code (format 1):", parsed.random_code);
    }
    
    let rmMatch = rawCode.match(/@remark@'@([^@]+)@/);
    if (rmMatch) {
        parsed.remark = rmMatch[1].replace(/[@']/g, '').trim();
        console.log("✅ Extracted remark (format 1):", parsed.remark);
    }

    if (!parsed.random_code) {
        let rMatch2 = rawCode.match(/@randomcode@'@?([^@'|]+)@?'/);
        if (rMatch2) {
            parsed.random_code = rMatch2[1].replace(/[@']/g, '').trim();
            console.log("✅ Extracted random_code (format 2):", parsed.random_code);
        }
    }
    
    if (parsed.remark === 'N/A' || !parsed.remark) {
        let rmMatch2 = rawCode.match(/@remark@'@?([^'@|]+)@?'/);
        if (rmMatch2) {
            parsed.remark = rmMatch2[1].replace(/[@']/g, '').trim();
            console.log("✅ Extracted remark (format 2):", parsed.remark);
        }
    }

    if (!parsed.random_code) {
        let hexMatch = rawCode.match(/([A-F0-9]{12})/);
        if (hexMatch) {
            parsed.random_code = hexMatch[1];
            console.log("✅ Extracted hex random_code:", parsed.random_code);
        }
    }

    if (parsed.remark === 'N/A' || !parsed.remark) {
        let rmMatch3 = rawCode.match(/@remark@'@([^@]+)@/);
        if (rmMatch3) {
            parsed.remark = rmMatch3[1].trim();
            console.log("✅ Extracted remark (final):", parsed.remark);
        }
    }

    console.log("📊 Final parsed result:", parsed);
    
    if (parsed.random_code) {
        return parsed;
    }
    return null;
}

// ============================================================
// HANDLE QR SCAN (QR Mode only) - Checks QR status
// ============================================================
async function handleQRScan(parsedData) {
    const tagCode = parsedData.random_code;
    qrRemark = parsedData.remark || 'N/A';
    qrRandomCode = tagCode;

    console.log("📱 QR Scanned:", tagCode, "Remark:", qrRemark);

    document.getElementById('qr_remark_value').textContent = qrRemark;
    document.getElementById('qr_remark_box').classList.remove('hidden');
    document.getElementById('editRemarkBtn').style.display = 'block';

    Swal.fire({ 
        title: 'VERIFYING QR STATUS...', 
        background: '#020617', 
        color: '#fff', 
        showConfirmButton: false, 
        didOpen: () => Swal.showLoading() 
    });

    try {
        const url = `get_tag_info.php?code=${encodeURIComponent(tagCode)}&mode=${currentMode}`;
        const res = await fetch(url);
        const tagInfo = await res.json();
        Swal.close();

        console.log("📊 QR Status Response:", tagInfo);

        if (currentMode === 'OUT') {
            if (tagInfo.success === false && tagInfo.in_transit === true) {
                Swal.fire({
                    icon: 'error',
                    title: 'QR IN TRANSIT',
                    html: `Tag <strong>${tagCode}</strong> is currently <span style="color:#ef4444;font-weight:bold;">IN TRANSIT</span>.<br><br>
                           This QR must be returned (INBOUND) before it can be used for OUTBOUND again.`,
                    background: '#020617',
                    color: '#fff',
                    confirmButtonColor: '#ef4444'
                });
                return;
            }

            if (tagInfo.exists && tagInfo.already_issued && tagInfo.in_transit) {
                Swal.fire({
                    icon: 'error',
                    title: 'QR IN TRANSIT',
                    text: `Tag ${tagCode} is currently in transit. Must be returned first.`,
                    background: '#020617',
                    color: '#fff'
                });
                return;
            }
        }

        if (currentMode === 'IN') {
            if (tagInfo.record_count !== undefined && tagInfo.record_count % 2 === 0) {
                if (tagInfo.record_count === 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'INVALID QR',
                        text: `No outbound transfer record found for Tag [${tagCode}]. Use OUTBOUND mode first.`,
                        background: '#020617', 
                        color: '#fff'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'ALREADY RETURNED',
                        text: `Tag [${tagCode}] has already been returned (${tagInfo.record_count} transactions). Use OUTBOUND mode to reuse it.`,
                        background: '#020617', 
                        color: '#fff'
                    });
                }
                return;
            }

            if (!tagInfo.success || !tagInfo.outbound_weight) {
                Swal.fire({
                    icon: 'error',
                    title: 'INVALID QR',
                    text: tagInfo.message || `No active outbound transfer record found for Tag [${tagCode}]`,
                    background: '#020617', 
                    color: '#fff'
                });
                return;
            }

            dbOutboundWeight = parseFloat(tagInfo.outbound_weight);
            document.getElementById('meta_out_weight_row').classList.remove('hidden');
            document.getElementById('meta_out_weight').innerText = `${dbOutboundWeight.toFixed(3)}g`;
        }

        qrTrackingStatus = tagInfo;

        document.getElementById('qr_status_text').textContent = '✅ QR Verified - Scan Card';
        document.getElementById('qr_status_text').className = 'scan-mode-indicator status-ready';
        
        document.getElementById('qr_tracking_status').classList.remove('hidden');
        const statusBadge = document.getElementById('qr_tracking_badge');
        if (currentMode === 'OUT') {
            if (tagInfo.in_transit) {
                statusBadge.className = 'qr-status-badge qr-status-transit';
                statusBadge.textContent = '🚫 IN TRANSIT - Cannot Issue';
            } else {
                statusBadge.className = 'qr-status-badge qr-status-active';
                statusBadge.textContent = '✅ Available for Issue';
            }
        } else {
            if (tagInfo.in_transit) {
                statusBadge.className = 'qr-status-badge qr-status-active';
                statusBadge.textContent = '🔄 Ready for Return';
            } else {
                statusBadge.className = 'qr-status-badge qr-status-returned';
                statusBadge.textContent = '✅ Already Returned';
            }
        }

        barcodeData = parsedData;
        isQRScanned = true;
        isCardScanned = false;

        updateQRStep('card');

        document.getElementById('tag_meta_box').classList.remove('hidden');
        document.getElementById('meta_code').textContent = tagCode;
        document.getElementById('meta_remark').textContent = qrRemark;
        document.getElementById('meta_status_row').classList.remove('hidden');
        document.getElementById('meta_status').textContent = currentMode === 'OUT' ? 
            (tagInfo.in_transit ? '⚠️ IN TRANSIT' : '✅ AVAILABLE') : 
            (tagInfo.in_transit ? '🔄 READY FOR RETURN' : '✅ RETURNED');
        document.getElementById('meta_status').className = 'orbitron ' + 
            (currentMode === 'OUT' && tagInfo.in_transit ? 'text-red-400' : 'text-emerald-400');

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'QR VERIFIED',
            text: `Remark: ${qrRemark} | ${currentMode === 'OUT' ? 'Ready to issue' : 'Ready to return'}`,
            showConfirmButton: false,
            timer: 2500,
            background: '#020617',
            color: '#fff'
        });

        barcodeInput.value = '';
        setTimeout(() => { barcodeInput.focus(); }, 100);

    } catch (err) {
        Swal.close();
        console.error("QR verification error:", err);
        Swal.fire({ 
            icon: 'error', 
            title: 'VERIFICATION ERROR', 
            text: 'Failed to communicate with database server.', 
            background: '#020617', 
            color: '#fff' 
        });
    }
}

// ============================================================
// HANDLE CARD SCAN - Card determines the path
// ============================================================
function handleCardScan(code) {
    console.log("🪪 Card scanned:", code);

    fetch(`../check_card.php?code=${code}`)
        .then(res => res.json())
        .then(data => {
            if (data.status !== "authorized") {
                Swal.fire({
                    icon: 'error',
                    title: 'ACCESS DENIED',
                    text: `Card ${code} not recognized`,
                    background: '#020617',
                    color: '#fff'
                });
                return;
            }

            if (senderData) return;
            senderData = data;
            isCardScanned = true;

            let sourceId, destId;
            let sourceName, destName;

            if (currentMode === 'OUT') {
                sourceId = data.dDept;
                destId = data.sDept;
                sourceName = getDeptName(data.dDept) || data.dDept;
                destName = getDeptName(data.sDept) || data.sDept;
            } else {
                sourceId = data.sDept;
                destId = data.dDept;
                sourceName = getDeptName(data.sDept) || data.sDept;
                destName = getDeptName(data.dDept) || data.dDept;
            }

            senderData.activeSource = sourceId;
            senderData.activeDest = destId;

            console.log("📌 Card Path - Source:", sourceId, "Dest:", destId);

            document.getElementById('source_name').textContent = sourceName;
            document.getElementById('source_id_display').textContent = `ID: ${sourceId}`;
            document.getElementById('source_status').textContent = `User: ${data.owner}`;
            document.getElementById('source_avatar').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(data.owner)}&background=3b82f6&color=fff`;
            document.getElementById('user_card_source').classList.add('verified');

            document.getElementById('dest_dept_name').textContent = destName;
            document.getElementById('dest_id_display').textContent = `ID: ${destId}`;
            document.getElementById('dest_status').textContent = `AUTO ROUTED`;
            document.getElementById('dest_avatar').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(destName)}&background=eab308&color=000`;
            document.getElementById('user_card_dest').classList.add('verified');

            let finalRemark = '';
            if (scanMode === 'qr' && qrRemark) {
                finalRemark = `QR: ${qrRemark} | Authorized: ${data.owner}`;
                document.getElementById('qr_status_text').textContent = '✅ Ready for Weight';
                document.getElementById('qr_status_text').className = 'scan-mode-indicator status-ready';
                updateQRStep('ready');
            } else {
                finalRemark = `Card: ${data.owner}`;
                document.getElementById('scan_step_indicator').className = 'scan-mode-indicator status-ready';
                document.getElementById('scan_step_indicator').textContent = '⚡ Press PRINT';
                scanPrompt.textContent = 'PRESS PRINT ON SCALE';
                scanPrompt.className = 'orbitron text-sm animate-pulse uppercase tracking-widest text-emerald-400';
            }

            pendingData = {
                sourceId: sourceId,
                sourceName: sourceName,
                destId: destId,
                destName: destName,
                randomCode: scanMode === 'qr' ? qrRandomCode : 'CARD_TRANSFER',
                remark: finalRemark,
                authorizedBy: data.owner,
                authorizedCard: data.cardCode,
                mode: scanMode,
                qrStatus: qrTrackingStatus
            };

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'USER AUTHORIZED',
                text: `${data.owner} - Path: ${sourceName} ➔ ${destName}`,
                showConfirmButton: false,
                timer: 2500,
                background: '#020617',
                color: '#fff'
            });

            barcodeInput.value = '';
            setTimeout(() => { barcodeInput.focus(); }, 100);
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'System failure verifying user card.', 'error');
        });
}

// ============================================================
// SCALE CONNECTION (unchanged)
// ============================================================
let scalePort, scaleReader, scaleKeepReading = false;
let scaleLineBuffer = '';

function setScaleStatus(text, connected){
    const el = document.getElementById('scale_status_text');
    if (el) el.innerText = text;
    const btn = document.getElementById('scale_connect_btn');
    if (btn) btn.classList.toggle('hidden', !!connected);
}

function parseScaleLine(line){
    const cleaned = line.trim();
    if (!cleaned) return null;
    const match = cleaned.match(/([+-]?\s*\d+(?:\.\d+)?)\s*([a-zA-Z]{0,3})\s*$/);
    if (!match) return null;
    const weight = parseFloat(match[1].replace(/\s+/g, ''));
    return isNaN(weight) ? null : weight;
}

async function connectScale(){
    if (!('serial' in navigator)){
        setScaleStatus('Unsupported — use Chrome/Edge over https:// or localhost', false);
        Swal.fire({
            icon: 'error',
            title: 'SCALE CONNECTION UNAVAILABLE',
            html: `Web Serial isn't available in this browser context.<br><br>
                   This page must be opened in <b>Chrome</b> or <b>Edge</b>,
                   and served over <b>https://</b> or <b>http://localhost</b>`,
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
    while (scaleKeepReading){
        const textDecoder = new TextDecoderStream();
        scalePort.readable.pipeTo(textDecoder.writable).catch(() => {});
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

// ============================================================
// WEIGHT HANDLER - Just captures and displays
// ============================================================
function onScaleWeight(w){
    if (w <= 0.001){
        document.getElementById('weight_display').innerHTML = `WAITING...<span class="text-3xl text-slate-600 ml-2">g</span>`;
        document.getElementById('weight_display').classList.remove('has-weight');
        return;
    }

    currentWeight = w;
    
    document.getElementById('weight_display').innerHTML = `${w.toFixed(3)}<span class="text-3xl text-slate-600 ml-2">g</span>`;
    document.getElementById('weight_display').classList.add('has-weight');
    
    if (!pendingData) {
        console.log("⚠️ No pending data - need card scan first");
        return;
    }

    capturedWeight = w;
    hasCapturedWeight = true;
    
    document.getElementById('submit_area').classList.remove('hidden');
    document.getElementById('weight_status_badge').classList.remove('hidden');
    
    if (scanMode === 'qr') {
        updateQRStep('captured');
    } else {
        document.getElementById('scan_step_indicator').className = 'scan-mode-indicator status-weight-captured';
        document.getElementById('scan_step_indicator').textContent = '✅ Weight Captured';
        scanPrompt.textContent = 'WEIGHT CAPTURED - REVIEW & SUBMIT';
        scanPrompt.className = 'orbitron text-sm uppercase tracking-widest text-yellow-400 font-bold';
    }
    
    if (dbOutboundWeight !== null) {
        weightVariance = capturedWeight - dbOutboundWeight;
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
    
    console.log("⚖️ Weight captured:", capturedWeight, "Ready for user to submit");
}

// ============================================================
// SUBMIT PAYLOAD - User clicks confirm to submit
// ============================================================
async function executeBackEnd() {
    console.log("📤 executeBackEnd called");
    
    if (!pendingData) {
        Swal.fire({ 
            icon: 'error', 
            title: 'NO DATA', 
            text: 'Please scan a card first.', 
            background: '#020617', 
            color: '#fff' 
        });
        return;
    }
    
    if (!hasCapturedWeight || capturedWeight <= 0.001) {
        Swal.fire({ 
            icon: 'error', 
            title: 'NO WEIGHT', 
            text: 'Please press PRINT on the scale to capture a weight first.', 
            background: '#020617', 
            color: '#fff' 
        });
        return;
    }

    const data = pendingData;
    console.log("📊 Pending Data:", data);
    
    let statusLabel = "";
    if (itemStatus === 1) statusLabel = "[NOT READY]";
    else if (itemStatus === 2) statusLabel = "[POWDER]";

    const payload = {
        sourceDept: data.sourceId,
        destinationDept: data.destId,
        sender: data.authorizedBy || "UNKNOWN",
        random_code: data.randomCode || 'CARD_TRANSFER',
        mode: currentMode,
        transfers: [{
            productname: (currentMode === 'IN') ? "RETURN_TRANS" : "ISSUE_TRANS",
            amount: capturedWeight,
             remark: `HandOver ${currentMode === 'IN' ? 'Inbound' : 'Outbound'}`,
            sourceDept: data.sourceId,
            destinationDept: data.destId
        }]
    };

    console.log("📤 Submitting payload:", payload);

    Swal.fire({ 
        title: 'RECORDING...', 
        background: '#020617', 
        color: '#fff', 
        showConfirmButton: false, 
        didOpen: () => Swal.showLoading() 
    });

    try {
        const res = await fetch(`http://192.168.88.88:81/s3t1/save_handover.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });

        const result = await res.json();
        console.log("📥 Response:", result);

        if (result.success && result.rows_inserted > 0) {
            Swal.fire({ 
                icon: 'success', 
                title: 'TRANSFER COMPLETE', 
                text: `${result.rows_inserted} item(s) recorded successfully.`,
                background: '#020617', 
                color: '#fff', 
                timer: 2000, 
                showConfirmButton: false 
            }).then(() => {
                resetSession();
                barcodeInput.value = '';
                setTimeout(() => { barcodeInput.focus(); }, 100);
            });
        } else {
            Swal.fire({ 
                icon: 'error', 
                title: 'SAVE FAILED', 
                text: result.message || 'Unknown database error', 
                background: '#020617', 
                color: '#fff' 
            });
        }
    } catch (err) {
        console.error("❌ Network error:", err);
        Swal.fire({ 
            icon: 'error', 
            title: 'NETWORK FAILURE', 
            text: err.message, 
            background: '#020617', 
            color: '#fff' 
        });
    }
}

// Hidden barcode input
const barcodeInput = document.createElement('input');
barcodeInput.id = 'barcodeInput';
barcodeInput.type = 'text';
barcodeInput.autocomplete = 'off';
barcodeInput.autofocus = true;
barcodeInput.style.cssText = 'position:absolute; left:-9999px; opacity:0;';
document.body.appendChild(barcodeInput);

barcodeInput.focus();

// Fix: prevent barcode input from stealing focus when a SweetAlert modal is open
document.addEventListener('click', () => {
    // Only refocus if no SweetAlert modal is active
    if (!document.body.classList.contains('swal2-shown')) {
        barcodeInput.focus();
    }
});

barcodeInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        const barcode = this.value.trim();
        if (barcode) {
            handleScan(barcode);
            this.value = '';
        }
    }
});

// Initialize - start in Card Mode
setScanMode('card');

console.log("✅ Gold Transfer loaded - QR acts as virtual basket, card determines path");
console.log("✅ QR Tracking: OUTBOUND → IN TRANSIT → INBOUND → COMPLETE → REUSABLE");
console.log("✅ Edit button opens modal with auto‑focus for smooth typing");
</script>
</body>
</html>