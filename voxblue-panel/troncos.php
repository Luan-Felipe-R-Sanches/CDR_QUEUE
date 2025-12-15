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
    <title>Monitor de Troncos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Paleta Slate (Mesma dos Ramais) */
            --bg-body: #0f172a;       /* Slate 900 */
            --bg-surface: #1e293b;    /* Slate 800 */
            --bg-surface-hover: #334155;
            --border-color: #334155;
            
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            
            /* Status Troncos */
            --st-active: #22c55e;     /* Verde */
            --color-timer: #facc15;   /* Amarelo */
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            font-size: 0.95rem;
        }

        /* NAVBAR */
        .top-nav {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            padding: 12px 30px;
            position: sticky; top: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
        }

        .brand { font-weight: 700; font-size: 1.2rem; color: #fff; display: flex; align-items: center; gap: 10px; }

        .search-wrapper { position: relative; width: 350px; }
        .search-input {
            background: var(--bg-surface); border: 1px solid var(--border-color); color: #fff;
            border-radius: 8px; padding: 8px 15px 8px 40px; width: 100%;
        }
        .search-input:focus { outline: none; border-color: #64748b; background: #263346; }
        .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-size: 0.9rem; }

        .nav-btn { color: var(--text-secondary); margin-left: 15px; font-size: 1.3rem; transition: 0.2s; text-decoration: none; }
        .nav-btn:hover { color: #fff; }

        /* GRID */
        .container-fluid { padding: 30px 40px; }
        
        .grid-troncos {
            display: grid;
            /* Largura mínima confortável para leitura */
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        /* CARD DESIGN */
        .trunk-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            transition: all 0.2s;
            display: flex; 
            flex-direction: column;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .trunk-card:hover { 
            transform: translateY(-3px); 
            background: var(--bg-surface-hover);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }

        /* Barra lateral indicando atividade */
        .trunk-card::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
            background: var(--st-active);
            box-shadow: 2px 0 10px rgba(34, 197, 94, 0.3);
        }

        .t-header {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex; justify-content: space-between; align-items: center;
            gap: 15px;
        }

        .t-name {
            font-weight: 700;
            font-size: 1rem;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            
            /* Lógica para não quebrar */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .t-badge {
            font-size: 0.65rem; font-weight: 800; padding: 3px 8px; border-radius: 4px;
            background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2);
            flex-shrink: 0; /* Garante que o badge não amasse */
            display: flex; align-items: center; gap: 5px;
        }
        
        .pulse-dot { width: 6px; height: 6px; background: #4ade80; border-radius: 50%; animation: pulse 1.5s infinite; }

        .t-body {
            padding: 25px 20px;
            text-align: center;
            flex: 1;
            display: flex; flex-direction: column; justify-content: center;
        }

        .t-timer {
            font-family: 'JetBrains Mono', monospace;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--color-timer);
            margin-bottom: 10px;
        }

        .t-dest-label { font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .t-dest-val { font-size: 1.2rem; font-weight: 600; color: #e2e8f0; }

        .t-footer {
            padding: 8px 15px;
            background: rgba(0,0,0,0.2);
            font-size: 0.7rem; color: var(--text-secondary);
            font-family: monospace; text-align: right;
            border-top: 1px solid rgba(255,255,255,0.05);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center; padding: 60px;
            color: var(--text-secondary);
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.2);
        }

        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.3; } 100% { opacity: 1; } }
        
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
    </style>
</head>
<body>

    <nav class="top-nav">
        <div class="brand"><i class="bi bi-hdd-network-fill text-success"></i> TRONCOS</div>
        
        <div class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="searchTrunk" class="search-input" placeholder="Buscar tronco ou número...">
        </div>

        <a href="index.php" class="nav-btn" title="Voltar"><i class="bi bi-x-lg"></i></a>
    </nav>

    <div class="container-fluid">
        <div id="trunk-area" class="grid-troncos">
            <div class="text-secondary p-5 text-center w-100">Carregando troncos...</div>
        </div>
    </div>

<script>
    let searchTerm = "";
    
    document.getElementById('searchTrunk').addEventListener('input', (e) => {
        searchTerm = e.target.value.toLowerCase();
    });

    function formatTime(seconds) {
        if (isNaN(seconds) || seconds < 0) return "00:00";
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60).toString().padStart(2,'0');
        const s = Math.floor(seconds % 60).toString().padStart(2,'0');
        return h > 0 ? `${h}:${m}:${s}` : `${m}:${s}`;
    }

    function render(lista) {
        const area = document.getElementById('trunk-area');
        const now = new Date().getTime();

        const filtered = lista.filter(t => 
            t.nome.toLowerCase().includes(searchTerm) || 
            t.destino.toLowerCase().includes(searchTerm)
        );

        if (!lista || lista.length === 0) {
            area.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-check-circle-fill fs-2 mb-3 d-block text-success"></i>
                    Todos os canais de saída livres.
                </div>`;
            return;
        }

        if (filtered.length === 0) {
            area.innerHTML = '<div class="empty-state">Nenhum tronco encontrado.</div>';
            return;
        }

        area.innerHTML = filtered.map(t => {
            let dur = 0;
            if (t.inicio) {
                const start = Date.parse(t.inicio);
                if (!isNaN(start)) dur = Math.floor((now - start) / 1000);
            }

            return `
            <div class="trunk-card">
                <div class="t-header">
                    <div class="t-name" title="${t.nome}">${t.nome}</div>
                    <div class="t-badge"><div class="pulse-dot"></div> ONLINE</div>
                </div>
                
                <div class="t-body">
                    <div class="t-timer">${formatTime(dur)}</div>
                    <div>
                        <div class="t-dest-label">DESTINO</div>
                        <div class="t-dest-val">${t.destino}</div>
                    </div>
                </div>

                <div class="t-footer" title="${t.canal}">
                    ${t.canal}
                </div>
            </div>`;
        }).join('');
    }

    function update() {
        fetch('backend.php')
            .then(r => r.json())
            .then(data => {
                if (data.troncos && data.troncos.lista) {
                    render(data.troncos.lista);
                } else {
                    render([]);
                }
            })
            .catch(e => console.error(e));
    }

    setInterval(update, 1000);
    update();
</script>
</body>
</html>