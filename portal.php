<?php
// Arquivo: /var/www/html/portal.php
require_once 'config_global.php';
checkGlobalAuth(); // Bloqueia acesso sem login

$user = $_SESSION['vox_user'];
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal | VoxBlue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #020617; min-height: 100vh; font-family: 'Inter', sans-serif; display: flex; flex-direction: column; }
        .top-nav { background: rgba(15,23,42,0.8); backdrop-filter: blur(10px); border-bottom: 1px solid #1e293b; padding: 15px 30px; }
        .module-card { background: #0f172a; border: 1px solid #1e293b; border-radius: 16px; padding: 30px; text-align: center; transition: all 0.3s ease; cursor: pointer; text-decoration: none; color: inherit; display: block; height: 100%; position: relative; overflow: hidden; }
        .module-card:hover { transform: translateY(-5px); border-color: #3b82f6; background: #1e293b; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.1); }
        .icon-box { width: 80px; height: 80px; background: rgba(59, 130, 246, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2rem; color: #3b82f6; transition: 0.3s; }
        .module-card:hover .icon-box { background: #3b82f6; color: white; transform: scale(1.1); }
        .card-title { font-weight: 700; font-size: 1.2rem; color: #f8fafc; margin-bottom: 5px; }
        .card-desc { color: #94a3b8; font-size: 0.9rem; }
    </style>
</head>
<body>

<nav class="top-nav d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-3">
        <i class="bi bi-grid-3x3-gap-fill text-primary fs-4"></i>
        <span class="fw-bold text-white tracking-wide">VOXBLUE PORTAL</span>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="text-end d-none d-md-block">
            <div class="text-white fw-bold small"><?= htmlspecialchars($user['name']) ?></div>
            <div class="text-secondary small" style="font-size:0.75rem"><?= strtoupper($user['role'] ?? 'User') ?></div>
        </div>
        <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-power"></i></a>
    </div>
</nav>

<div class="container py-5 flex-grow-1 d-flex align-items-center">
    <div class="row g-4 w-100 justify-content-center">
        
        <div class="col-md-6 col-lg-3">
            <a href="realtime/painel.php" class="module-card">
                <div class="icon-box"><i class="bi bi-activity"></i></div>
                <div class="card-title">Monitor Realtime</div>
                <div class="card-desc">Visualização de filas e agentes em tempo real.</div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="voxblue-queue/app.php" class="module-card">
                <div class="icon-box"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="card-title">Relatórios Queue</div>
                <div class="card-desc">Estatísticas, chamadas e KPIs de atendimento.</div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="voxblue-panel/index.php" class="module-card">
                <div class="icon-box"><i class="bi bi-sliders"></i></div>
                <div class="card-title">Painel Admin</div>
                <div class="card-desc">Configuração de troncos, rotas e sistema.</div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="voxblue-cdrs/index.php" class="module-card">
                <div class="icon-box"><i class="bi bi-telephone-inbound"></i></div>
                <div class="card-title">Histórico CDR</div>
                <div class="card-desc">Detalhes completos de chamadas do sistema.</div>
            </a>
        </div>

    </div>
</div>

<footer class="text-center py-3 text-secondary small border-top border-secondary border-opacity-10">
    &copy; <?= date('Y') ?> VoxBlue Call Center Suite. Módulo Agente opera separadamente.
</footer>

</body>
</html>