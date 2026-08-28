<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MILANO UNIVERSAL TRANSFER</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@300;600&display=swap" rel="stylesheet">
    <style>
        body { background: #020617; color: white; font-family: 'Inter', sans-serif; overflow: hidden; }
        .orbitron { font-family: 'Orbitron', sans-serif; }
        .glass-card { 
            background: rgba(255,255,255,0.03); 
            backdrop-filter: blur(15px); 
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
        .online { background: #10b981; box-shadow: 0 0 10px #10b981; }
        .offline { background: #ef4444; box-shadow: 0 0 10px #ef4444; }
        
        /* Pulse for the weight reading to show it's "live" */
        .live-pulse { animation: blink 1.5s infinite; }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
    </style>
</head>
<body class="h-screen flex flex-col items-center justify-center relative px-6">

    <div id="alert-banner" class="w-full max-w-4xl mb-6 transition-all duration-300 hidden">
        <div class="bg-red-950/20 border border-red-500/30 rounded-2xl p-4 flex items-center justify-between animate-pulse">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="orbitron text-xs tracking-wider text-red-400">
                    WARNING: SCALE SERVER OFFLINE (<code class="bg-black/30 px-1 rounded text-red-300">192.168.0.7:5000</code>). BUTTONS REMAIN ACTIVE.
                </span>
            </div>
        </div>
    </div>

    <div class="text-center mb-12">
        <h1 class="orbitron tracking-[0.5em] text-4xl font-bold text-yellow-500 mb-2">MILANO UNIVERSAL TRANSFER</h1>
        
        <div class="flex justify-center gap-6 mt-6">
            <div class="glass-card px-6 py-3 rounded-2xl border border-white/5 flex flex-col items-center min-w-[160px]">
                <div class="flex items-center mb-1">
                    <span id="dot-s1" class="status-dot offline"></span>
                    <span class="orbitron text-[9px] text-slate-500 tracking-widest uppercase">SCALE QC</span>
                </div>
                <div id="read-s1" class="orbitron text-xl font-bold text-slate-700">0.000<span class="text-xs ml-1">g</span></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-4xl">
        <a href="process.php?mode=OUT" id="btn-in" class="glass-card p-12 rounded-[2rem] flex flex-col items-center group hover:border-red-500/50">
            <div class="w-20 h-20 mb-6 rounded-full bg-red-500/10 flex items-center justify-center border border-red-500/20 group-hover:bg-red-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
            </div>
            <h2 class="orbitron text-2xl font-bold tracking-widest text-red-500">OUT</h2>
            <p id="msg-in" class="text-[10px] text-slate-500 orbitron mt-4 tracking-widest">READY</p>
        </a>

        <a href="process.php?mode=IN" id="btn-out" class="glass-card p-12 rounded-[2rem] flex flex-col items-center group hover:border-yellow-500/50">
            <div class="w-20 h-20 mb-6 rounded-full bg-yellow-500/10 flex items-center justify-center border border-yellow-500/20 group-hover:bg-yellow-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
            </div>
            <h2 class="orbitron text-2xl font-bold tracking-widest text-yellow-500">IN</h2>
            <p id="msg-out" class="text-[10px] text-slate-500 orbitron mt-4 tracking-widest">READY</p>
        </a>
    </div>

    <script>
        const config = {
            s1: { url: 'http://192.168.88.16:5000/weight', read: 'read-s1' }
        };

        async function monitorScales() {
            try {
                const ctrl = new AbortController();
                setTimeout(() => ctrl.abort(), 200);
                
                const res = await fetch(config.s1.url, { signal: ctrl.signal });
                const data = await res.json();
                
                updateStatus(true, data.weight);
            } catch (e) {
                updateStatus(false, "0.000");
            }
        }

        function updateStatus(isOnline, weight) {
            const dot = document.getElementById('dot-s1');
            const read = document.getElementById(config.s1.read);
            const banner = document.getElementById('alert-banner');
            const msgIn = document.getElementById('msg-in');
            const msgOut = document.getElementById('msg-out');

            if (isOnline) {
                dot.className = 'status-dot online';
                read.innerHTML = `${weight}<span class="text-xs ml-1">g</span>`;
                read.className = 'orbitron text-xl font-bold text-white live-pulse';
                
                // Hide warning banner
                banner.classList.add('hidden');
                
                msgIn.innerText = 'SYSTEM READY';
                msgIn.className = 'text-[10px] text-emerald-400 orbitron mt-4 opacity-70 tracking-widest';
                msgOut.innerText = 'SYSTEM READY';
                msgOut.className = 'text-[10px] text-emerald-400 orbitron mt-4 opacity-70 tracking-widest';
            } else {
                dot.className = 'status-dot offline';
                read.innerHTML = `OFFLINE`;
                read.className = 'orbitron text-xl font-bold text-slate-600';
                
                // Show warning banner without touching the buttons
                banner.classList.remove('hidden');
                
                msgIn.innerText = 'SCALE OFFLINE';
                msgIn.className = 'text-[10px] text-red-400 orbitron mt-4 tracking-widest';
                msgOut.innerText = 'SCALE OFFLINE';
                msgOut.className = 'text-[10px] text-red-400 orbitron mt-4 tracking-widest';
            }
        }

        setInterval(monitorScales, 500);
        monitorScales();
    </script>
</body>
</html>