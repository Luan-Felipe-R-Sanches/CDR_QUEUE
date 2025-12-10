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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Rajdhani:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-body: #020617;
            --bg-card: #1e293b;
            --border: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #22d3ee;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
        }

        .top-nav {
            background: rgba(15, 23, 42, 0.95);
            border-bottom: 1px solid var(--border);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .container {
            max-width: 1400px;
            margin-top: 40px;
        }

        h4,
        h5 {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
        }

        .trunk-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .trunk-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            border-left: 5px solid #3b82f6;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .t-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .t-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #fff;
            font-family: 'Rajdhani', sans-serif;
        }

        .t-state {
            font-size: 0.8rem;
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 8px;
            border-radius: 6px;
            font-family: monospace;
        }

        .t-status {
            margin-top: 5px;
            font-weight: 600;
            font-size: 0.95rem;
            color: #4ade80;
            /* Verde */
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .t-usage {
            background: rgba(6, 182, 212, 0.1);
            border: 1px solid rgba(6, 182, 212, 0.2);
            padding: 12px;
            border-radius: 8px;
            margin-top: 5px;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .t-channel {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-family: monospace;
            word-break: break-all;
            margin-top: 5px;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px;
            border: 2px dashed var(--border);
            border-radius: 20px;
            color: var(--text-muted);
        }

        .empty-icon {
            font-size: 3rem;
            color: #334155;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <nav class="top-nav">
        <h4 class="m-0 text-white"><i class="bi bi-hdd-network text-info me-2"></i> MONITOR DE TRONCOS</h4>
        <a href="index.php" class="btn btn-outline-light"><i class="bi bi-arrow-left"></i> Voltar</a>
    </nav>

    <div class="container">
        <h5 class="mb-4 text-muted"><i class="bi bi-activity"></i> Canais de Voz em Utilização</h5>

        <div class="trunk-grid" id="trunk-area">
            <div class="text-muted">Carregando dados...</div>
        </div>
    </div>
    <script>
        function update() {
            fetch('backend.php')
                .then(r => r.json())
                .then(data => {
                    render(data.troncos);
                })
                .catch(e => console.error(e));
        }

        function render(troncosData) {
            let html = '';

            if (troncosData.lista && troncosData.lista.length > 0) {
                html = troncosData.lista.map(t => `
                    <div class="trunk-card">
                        <div class="t-header">
                            <div class="t-name"><i class="bi bi-telephone-outbound-fill me-2"></i> ${t.nome}</div>
                            <div class="t-state">${t.status}</div>
                        </div>
                        
                        <div class="t-status">
                            <span class="spinner-grow spinner-grow-sm text-success"></span> EM USO
                        </div>
                        
                        <div class="t-usage">
                            <i class="bi bi-arrow-left-right text-info"></i> ${t.uso}
                        </div>
                        
                        <div class="t-channel"><i class="bi bi-share me-1"></i> ${t.canal}</div>
                    </div>
                `).join('');
            } else {
                html = `
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-check2-circle"></i></div>
                    <h4>Nenhum tronco em uso no momento.</h4>
                    <p>Todos os canais de saída estão livres.</p>
                </div>`;
            }

            document.getElementById('trunk-area').innerHTML = html;
        }

        update();
        setInterval(update, 2000);
    </script>
</body>

</html>