<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>

<head>
    <meta charset="UTF-8">
    <title>VoxBlue Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Rajdhani:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Paleta Dark Mode Profissional */
            --bg-body: #020617;
            /* Fundo quase preto */
            --bg-card: #1e293b;
            /* Card cinza azulado */
            --border: #334155;
            /* Bordas sutis */

            --text-main: #f8fafc;
            /* Branco Gelo (Leitura principal) */
            --text-muted: #94a3b8;
            /* Cinza claro (Detalhes) */

            --accent: #22d3ee;
            /* Ciano Neon */
            --success: #4ade80;
            /* Verde Neon */
            --danger: #f87171;
            /* Vermelho Suave */
            --warning: #fbbf24;
            /* Amarelo */
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            padding-bottom: 80px;
        }

        /* Títulos e Números com fonte Tech */
        h1,
        h2,
        h3,
        h4,
        h5,
        .kpi-val,
        .ext-num,
        .q-waiting {
            font-family: 'Rajdhani', sans-serif;
        }

        /* NAVBAR */
        .top-nav {
            background: rgba(15, 23, 42, 0.9);
            border-bottom: 1px solid var(--border);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }

        .brand-title {
            font-weight: 700;
            font-size: 1.4rem;
            letter-spacing: 1px;
            color: #fff;
        }

        /* CONTAINER */
        .dashboard-container {
            padding: 30px;
            max-width: 1800px;
            margin: 0 auto;
        }

        /* KPI CARDS */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .kpi-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            padding: 25px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            transition: transform 0.2s, border-color 0.2s;
            cursor: pointer;
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            border-color: var(--accent);
        }

        .kpi-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .kpi-val {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            color: #fff;
        }

        .kpi-lbl {
            font-size: 0.9rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        /* SEPARADORES */
        .section-header {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section-header::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
            opacity: 0.5;
        }

        /* FILAS */
        .queue-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 15px;
            margin-bottom: 40px;
        }

        .queue-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .q-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
        }

        .q-info {
            color: var(--text-muted);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 4px;
        }

        .q-waiting {
            font-size: 2rem;
            font-weight: 700;
            color: var(--danger);
            text-shadow: 0 0 10px rgba(248, 113, 113, 0.3);
        }

        .q-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            text-align: right;
        }

        /* RAMAIS (Cards Largos e Brancos) */
        .ext-grid {
            display: grid;
            /* Largura mínima aumentada para 220px para caber nomes longos */
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
        }

        .ext-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.2s;
            min-height: 110px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Efeito de brilho no hover */
        .ext-card:hover {
            background: #263345;
        }

        .ext-num {
            font-size: 1.6rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 2px;
        }

        .ext-name {
            font-size: 0.9rem;
            color: #cbd5e1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .ext-status {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-block;
            letter-spacing: 0.5px;
        }

        /* DID (Número Discado) - Bem visível */
        .ext-did {
            margin-top: 8px;
            font-size: 0.85rem;
            color: #fff;
            background: rgba(0, 0, 0, 0.4);
            padding: 4px 8px;
            border-radius: 6px;
            font-family: 'Rajdhani', monospace;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        /* STATUS COLORS (Borda Superior + Texto Brilhante) */
        /* Livre */
        .st-free {
            border-top: 4px solid var(--success);
        }

        .st-free .ext-status {
            background: rgba(74, 222, 128, 0.15);
            color: var(--success);
        }

        /* Ocupado */
        .st-busy {
            border-top: 4px solid var(--danger);
        }

        .st-busy .ext-status {
            background: rgba(248, 113, 113, 0.15);
            color: var(--danger);
        }

        .st-busy .ext-num {
            color: var(--danger);
        }

        /* Chamando */
        .st-ring {
            border-top: 4px solid var(--warning);
            animation: pulse 1.5s infinite;
        }

        .st-ring .ext-status {
            background: rgba(251, 191, 36, 0.15);
            color: var(--warning);
        }

        /* Offline */
        .st-off {
            border-top: 4px solid #475569;
            opacity: 0.6;
        }

        .st-off .ext-status {
            background: rgba(255, 255, 255, 0.05);
            color: #64748b;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.6;
            }

            100% {
                opacity: 1;
            }
        }

        /* LEGENDA FIXA */
        .legend-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(15, 23, 42, 0.95);
            border-top: 1px solid var(--border);
            padding: 12px;
            display: flex;
            justify-content: center;
            gap: 30px;
            font-size: 0.85rem;
            z-index: 200;
            font-weight: 500;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.5);
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
    </style>
</head>

<body>

    <nav class="top-nav">
        <div class="d-flex align-items-center gap-3">
            <div class="brand-title"><i class="bi bi-activity text-info me-2"></i> VOXBLUE PANEL</div>

            <a href="troncos.php" class="btn btn-sm btn-outline-info d-flex align-items-center gap-2">
                <i class="bi bi-hdd-network"></i> Monitor de Troncos
            </a>
        </div>

        <div class="d-flex gap-3 align-items-center">
            <div id="clock" class="font-monospace text-info fw-bold fs-5">--:--:--</div>
            <a href="../../portal.php" class="btn btn-outline-light btn-sm px-3">Sair</a>
        </div>
    </nav>

    <div class="dashboard-container">

        <div class="kpi-grid">
            <div class="kpi-card" onclick="window.location.href='troncos.php'">
                <div class="kpi-icon text-primary"><i class="bi bi-hdd-rack"></i></div>
                <div>
                    <div class="kpi-val" id="trunk-total">-</div>
                    <div class="kpi-lbl">Canais Ativos</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon text-success"><i class="bi bi-headset"></i></div>
                <div>
                    <div class="kpi-val" id="total-online">-</div>
                    <div class="kpi-lbl">Agentes Livres</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon text-danger"><i class="bi bi-telephone-outbound"></i></div>
                <div>
                    <div class="kpi-val" id="total-busy">-</div>
                    <div class="kpi-lbl">Em Atendimento</div>
                </div>
            </div>
        </div>

        <div class="section-header"><i class="bi bi-people-fill"></i> Filas de Atendimento</div>
        <div class="queue-grid" id="queue-area">
            <span class="text-muted small ms-2">Carregando filas...</span>
        </div>

        <div class="section-header"><i class="bi bi-grid-3x3-gap-fill"></i> Status dos Ramais</div>
        <div class="ext-grid" id="extension-area">
            <span class="text-muted small ms-2">Carregando ramais...</span>
        </div>

    </div>

    <div class="legend-bar">
        <div><span class="dot" style="background:var(--success)"></span> Livre</div>
        <div><span class="dot" style="background:var(--danger)"></span> Falando</div>
        <div><span class="dot" style="background:var(--warning)"></span> Chamando</div>
        <div><span class="dot" style="background:#64748b"></span> Offline</div>
    </div>
    <script>
        // Relógio
        setInterval(() => document.getElementById('clock').innerText = new Date().toLocaleTimeString(), 1000);

        function update() {
            fetch('backend.php')
                .then(r => r.json())
                .then(data => {
                    if (data.error) return console.error(data.error);
                    render(data);
                })
                .catch(e => console.error("Erro Conexão:", e));
        }

        function render(data) {
            let online = 0,
                busy = 0;

            // 1. RAMAIS
            let htmlExt = data.ramais.map(r => {
                if (r.css_class === 'st-free') online++;
                if (r.css_class === 'st-busy') busy++;

                let didBlock = '';
                // Mostra DID
                if (r.did && r.did !== '' && (r.css_class === 'st-busy' || r.css_class === 'st-ring')) {
                    didBlock = `<div class="ext-did" title="Conectado">
                                    <i class="bi bi-telephone-forward-fill text-warning"></i> ${r.did}
                                </div>`;
                }

                return `
                <div class="ext-card ${r.css_class}">
                    <div class="ext-num">${r.user}</div>
                    <div class="ext-name" title="${r.nome}">${r.nome}</div>
                    <div class="ext-status">${r.status_text}</div>
                    ${didBlock}
                </div>`;
            }).join('');

            document.getElementById('extension-area').innerHTML = htmlExt || '<span class="text-muted ms-2">Nenhum ramal.</span>';

            // 2. KPIs
            document.getElementById('trunk-total').innerText = data.troncos.total;
            document.getElementById('total-online').innerText = online;
            document.getElementById('total-busy').innerText = busy;

            // 3. FILAS
            let htmlQ = '';
            if (data.filas && data.filas.length > 0) {
                htmlQ = data.filas.map(q => `
                    <div class="queue-card">
                        <div>
                            <div class="q-name">${q.nome}</div>
                            <div class="q-info"><i class="bi bi-person-check-fill"></i> ${q.logados} Logados</div>
                        </div>
                        <div class="text-end">
                            <div class="q-waiting">${q.espera}</div>
                            <div class="q-label">NA FILA</div>
                        </div>
                    </div>
                `).join('');
            } else {
                htmlQ = '<span class="text-muted small ms-2">Nenhuma fila ativa.</span>';
            }
            document.getElementById('queue-area').innerHTML = htmlQ;
        }

        update();
        setInterval(update, 5000); // 5s para não sobrecarregar
    </script>

</body>

</html>