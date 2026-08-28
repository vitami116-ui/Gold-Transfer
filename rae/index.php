<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Milano Management Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #020617; color: white; font-family: 'Inter', sans-serif; height: 100vh; display: flex; align-items: center; }
        .portal-card { 
            background: #1e293b; 
            border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 20px; 
            transition: all 0.3s ease; 
            cursor: pointer;
            text-decoration: none;
            color: white;
            display: block;
        }
        .portal-card:hover { 
            transform: translateY(-10px); 
            border-color: #3b82f6; 
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.2);
        }
        .icon-box { font-size: 3rem; margin-bottom: 15px; color: #3b82f6; }
    </style>
</head>
<body>
<div class="container text-center">
    <h1 class="fw-bold mb-2">MILANO GMS</h1>
    <p class="text-secondary mb-5 uppercase tracking-widest" style="letter-spacing: 0.2em;">Select Production Department</p>

<div class="row g-4 justify-content-center">
    <!-- SPARK -->
    <div class="col-md-4">
        <a href="report_spark.php" class="portal-card p-5">
            <div class="icon-box" style="color: #60a5fa;"><i class="bi bi-lightning-charge"></i></div>
            <h3 class="fw-bold">SPARK</h3>
            <p class="text-secondary mb-0">Dept ID: 10089</p>
        </a>
    </div>

    <div class="col-md-4">
        <a href="report_pihua.php" class="portal-card p-5">
            <div class="icon-box"><i class="bi bi-cpu"></i></div>
            <h3 class="fw-bold">PIHUA</h3>
            <p class="text-secondary mb-0">Dept ID: 10093</p>
        </a>
    </div>

    <div class="col-md-4">
        <a href="report_zapmo.php" class="portal-card p-5">
            <div class="icon-box" style="color: #fbbf24;"><i class="bi bi-gpu-card"></i></div>
            <h3 class="fw-bold">ZAPMO</h3>
            <p class="text-secondary mb-0">Dept ID: 10091</p>
        </a>
    </div>

    <!-- MELTING -->
    <div class="col-md-4">
        <a href="report_melting.php" class="portal-card p-5">
            <div class="icon-box" style="color: #ef4444;"><i class="bi bi-fire"></i></div>
            <h3 class="fw-bold">MELTING</h3>
            <p class="text-secondary mb-0">Dept ID: 12100</p>
        </a>
    </div>

    <!-- CASTING -->
    <div class="col-md-4">
        <a href="report_casting.php" class="portal-card p-5">
            <div class="icon-box" style="color: #10b981;"><i class="bi bi-layers"></i></div>
            <h3 class="fw-bold">CASTING</h3>
            <p class="text-secondary mb-0">Dept ID: 10088</p>
        </a>
    </div>
</div>
</div>
</body>
</html>