<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>VoxBlue | Monitor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-body: #0f172a;
            --bg-surface: #1e293b;
            --bg-surface-hover: #334155;
            --border-color: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            
            --st-free: #22c55e;
            --st-busy: #ef4444;
            --st-ring: #f59e0b;
            --st-offline: #475569;
            --color-timer: #fbbf24;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            font-size: 0.9rem;
        }

        /* NAVBAR */
        .top-nav {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            padding: 10px 25px;
            position: sticky; top: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
        }

        .brand { font-weight: 700; font-size: 1.1rem; color: #fff; display: flex; align-items: center; gap: 8px; }
        
        .search-wrapper { position: relative; width: 300px; }
        .search-input {
            background: var(--bg-surface); border: 1px solid var(--border-color); color: #fff;
            border-radius: 6px; padding: 5px 12px 5px 35px; width: 100%; font-size: 0.85rem;
        }
        .search-input:focus { outline: none; border-color: #64748b; background: #263346; }
        .search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-size: 0.8rem; }

        .nav-actions a { color: var(--text-secondary); margin-left: 15px; font-size: 1.1rem; transition: 0.2s; }
        .nav-actions a:hover { color: #fff; }

        /* FILAS */
        .queues-wrapper {
            padding: 15px 25px; border-bottom: 1px solid var(--border-color);
            display: flex; gap: 10px; overflow-x: auto; background: rgba(30, 41, 59, 0.3);
        }
        .kpi-card {
            background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 6px;
            min-width: 140px; padding: 8px 12px; display: flex; flex-direction: column;
        }
        .kpi-head { font-size: 0.7rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; margin-bottom: 2px; }
        .kpi-val { font-family: 'JetBrains Mono', monospace; font-size: 1.1rem; font-weight: 700; color: #fff; }
        .kpi-body { display: flex; justify-content: space-between; align-items: flex-end; }
        .kpi-tag { font-size: 0.6rem; padding: 1px 4px; border-radius: 3px; background: rgba(255,255,255,0.05); color: var(--text-secondary); }

        /* LEGENDA */
        .monitor-bar { padding: 15px 25px 5px 25px; display: flex; justify-content: space-between; align-items: center; }
        .legend { display: flex; gap: 15px; }
        .l-item { display: flex; align-items: center; gap: 5px; font-size: 0.7rem; color: var(--text-secondary); }
        .dot { width: 6px; height: 6px; border-radius: 50%; }
        .dot.free { background: var(--st-free); box-shadow: 0 0 5px var(--st-free); }
        .dot.busy { background: var(--st-busy); box-shadow: 0 0 5px var(--st-busy); }
        .dot.ring { background: var(--st-ring); animation: blink 1s infinite; }
        .dot.off { background: var(--st-offline); }

        /* GRID RAMAIS */
        .content-area { padding: 10px 25px 40px 25px; }

        .ramal-grid {
            display: grid;
            /* Cards médios para caber o nome do lado */
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); 
            gap: 10px;
        }

        .mini-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 8px 12px; /* Padding reduzido */
            position: relative;
            transition: all 0.2s;
            cursor: default;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 50px; /* Altura mínima menor */
        }

        .mini-card:hover { transform: translateY(-2px); border-color: #64748b; background: var(--bg-surface-hover); }

        /* Barra Lateral Status */
        .mini-card::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
            border-radius: 6px 0 0 6px; background: var(--st-offline);
        }
        .mini-card.free::before { background: var(--st-free); box-shadow: 2px 0 8px -2px rgba(34, 197, 94, 0.4); }
        .mini-card.busy::before { background: var(--st-busy); box-shadow: 2px 0 8px -2px rgba(239, 68, 68, 0.4); }
        .mini-card.ring::before { background: var(--st-ring); animation: blink-border 1s infinite; }
        .mini-card.off { opacity: 0.6; }

        /* HEADER FLEX: RAMAL + NOME */
        .mc-header-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 8px;
        }

        .mc-info-group {
            display: flex;
            align-items: baseline;
            gap: 6px;
            overflow: hidden;
            white-space: nowrap;
        }

        .mc-num { 
            font-family: 'JetBrains Mono', monospace; 
            font-size: 0.95rem; /* Diminuído */
            font-weight: 700; 
            color: #fff;
            flex-shrink: 0;
        }
        
        .mc-name {
            font-size: 0.8rem; 
            color: var(--text-secondary);
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mc-icon { font-size: 0.8rem; color: var(--text-secondary); flex-shrink: 0; }
        .free .mc-icon { color: var(--st-free); }
        .busy .mc-icon { color: var(--st-busy); }

        /* Detalhes (Timer) */
        .mc-active {
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px solid rgba(255,255,255,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }
        .mc-timer { font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: var(--color-timer); font-weight: 600; }
        .mc-dest { font-size: 0.7rem; color: #e2e8f0; max-width: 90px; overflow: hidden; text-overflow: ellipsis; }

        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        @keyframes blink-border { 0%, 100% { background: var(--st-ring); } 50% { background: rgba(245, 158, 11, 0.3); } }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
    </style>
</head>
<body>

    <nav class="top-nav">
        <div class="brand"><i class="bi bi-grid-fill text-primary"></i> VOXBLUE PANEL</div>
        
        <div class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="searchBox" class="search-input" placeholder="Filtrar...">
        </div>

        <div class="nav-actions">
            <a href="troncos.php" title="Troncos"><i class="bi bi-hdd-network"></i></a>
            <a href="logout.php" title="Sair"><i class="bi bi-power"></i></a>
        </div>
    </nav>

    <div class="queues-wrapper" id="queue-area">
        <div class="text-muted small">Carregando filas...</div>
    </div>

    <div class="monitor-bar">
        <div class="text-secondary small fw-bold">RAMAIS</div>
        <div class="legend">
            <div class="l-item"><div class="dot free"></div> Livre</div>
            <div class="l-item"><div class="dot busy"></div> Em Uso</div>
            <div class="l-item"><div class="dot ring"></div> Chamando</div>
            <div class="l-item"><div class="dot off"></div> Offline</div>
        </div>
    </div>

    <div class="content-area">
        <div class="ramal-grid" id="grid-area">
            </div>
    </div>

<script>
    let filterTerm = "";
    document.getElementById('searchBox').addEventListener('input', (e) => filterTerm = e.target.value.toLowerCase());

    function formatTime(seconds) {
        if (isNaN(seconds) || seconds < 0) return "00:00";
        const m = Math.floor((seconds % 3600) / 60).toString().padStart(2,'0');
        const s = Math.floor(seconds % 60).toString().padStart(2,'0');
        const h = Math.floor(seconds / 3600);
        return h > 0 ? `${h}:${m}:${s}` : `${m}:${s}`;
    }

    function renderFilas(filas) {
        const el = document.getElementById('queue-area');
        if(!filas || filas.length === 0) {
            el.innerHTML = '<span class="text-muted small">Sem filas.</span>'; return;
        }
        el.innerHTML = filas.map(f => `
            <div class="kpi-card">
                <div class="kpi-head" title="${f.nome}">${f.nome}</div>
                <div class="kpi-body">
                    <div><div class="kpi-tag">ESP: ${f.espera}</div></div>
                    <div class="text-end">
                        <div class="kpi-val ${parseInt(f.logados)>0?'text-success':''}">${f.logados}</div>
                        <div style="font-size:0.55rem; color:#64748b">ON</div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function renderRamais(ramais) {
        const el = document.getElementById('grid-area');
        const now = new Date().getTime();

        const filtered = ramais.filter(r => 
            r.user.includes(filterTerm) || r.nome.toLowerCase().includes(filterTerm) || (r.did && r.did.includes(filterTerm))
        );

        if(filtered.length === 0) {
            el.innerHTML = '<div class="text-muted small w-100 text-center py-5">Nenhum ramal encontrado.</div>'; return;
        }

        el.innerHTML = filtered.map(r => {
            let vClass = 'off'; let icon = 'bi-dash-lg';
            if(r.css_class === 'st-free') { vClass = 'free'; icon = 'bi-check-lg'; }
            if(r.css_class === 'st-busy') { vClass = 'busy'; icon = 'bi-telephone-fill'; }
            if(r.status === 'CHAMANDO') { vClass = 'ring'; icon = 'bi-bell-fill'; }

            let activeHtml = '';
            if(vClass === 'busy' || vClass === 'ring') {
                let dur = 0;
                if (r.inicio) {
                    let start = Date.parse(r.inicio);
                    if(!isNaN(start)) dur = Math.floor((now - start)/1000);
                }
                activeHtml = `
                <div class="mc-active">
                    <span class="mc-timer">${formatTime(dur)}</span>
                    <span class="mc-dest" title="${r.did}">${r.did}</span>
                </div>`;
            }

            return `
            <div class="mini-card ${vClass}">
                <div class="mc-header-flex">
                    <div class="mc-info-group">
                        <div class="mc-num">${r.user}</div>
                        <div class="mc-name" title="${r.nome}">${r.nome}</div>
                    </div>
                    <i class="bi ${icon} mc-icon"></i>
                </div>
                ${activeHtml}
            </div>
            `;
        }).join('');
    }

    function update() {
        fetch('backend.php').then(r => r.json()).then(data => {
            renderFilas(data.filas); renderRamais(data.ramais);
        }).catch(e => console.error(e));
    }
    setInterval(update, 1000); update();
</script>
</body>
</html>