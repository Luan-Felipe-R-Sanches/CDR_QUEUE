<?php
// Arquivo: /var/www/html/relatorios/app.php
require_once 'auth.php';
require_once 'config.php';

// --- CONFIGURAÇÃO INICIAL ---
// Como o dashboard saiu, a visão padrão passa a ser 'chamadas'
$view = $_GET['view'] ?? 'chamadas';

// (O código antigo de cálculo do dashboard foi removido daqui)
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <style>
        :root {
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc;
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: white;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .sidebar .brand {
            padding: 24px;
            font-weight: 700;
            font-size: 1.1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-btn {
            display: block;
            padding: 12px 16px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 8px;
            margin: 4px 10px;
            transition: 0.2s;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .nav-btn.active {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            font-weight: 600;
        }

        .nav-btn i {
            margin-right: 10px;
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .page-wrapper {
            padding: 30px 40px;
        }

        /* Estilos de Tabela e Filtros */
        .table-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.75rem;
            text-transform: uppercase;
            padding: 15px 20px;
            font-weight: 600;
        }

        .table tbody td {
            padding: 15px 20px;
            vertical-align: middle;
        }

        .badge-soft {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .bg-success-soft {
            background: #dcfce7;
            color: #166534;
        }

        .bg-danger-soft {
            background: #fee2e2;
            color: #991b1b;
        }

        .bg-warning-soft {
            background: #fef9c3;
            color: #854d0e;
        }

        .bg-secondary-soft {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .search-area {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .filter-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="brand"><i class="bi bi-graph-up-arrow"></i>&nbsp; Analytics Pro</div>
        <div class="mt-3">
            <a href="dashboard_direto.php" class="nav-btn"><i class="bi bi-grid-1x2"></i> Dashboard</a>

            <a href="?view=chamadas" class="nav-btn <?php echo $view == 'chamadas' ? 'active' : ''; ?>"><i class="bi bi-telephone"></i> Chamadas</a>
            <a href="?view=pausas" class="nav-btn <?php echo $view == 'pausas' ? 'active' : ''; ?>"><i class="bi bi-cup-hot"></i> Pausas</a>
            <a href="?view=sessoes" class="nav-btn <?php echo $view == 'sessoes' ? 'active' : ''; ?>"><i class="bi bi-person-badge"></i> Sessões</a>

            <div class="mt-3 border-top border-secondary pt-2 opacity-50 small ms-3 mb-1">AO VIVO</div>
            <a href="realtime/painel.php" class="nav-btn text-warning"><i class="bi bi-activity"></i> Monitor Realtime</a>
        </div>
        <div class="mt-auto pt-4 border-top border-secondary mx-3 mb-3">
            <a href="logout.php" class="btn btn-outline-danger w-100 fw-bold btn-sm">
                <i class="bi bi-box-arrow-right me-2"></i> SAIR
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-wrapper">

            <?php if ($view === 'chamadas'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold m-0">Chamadas</h3>
                </div>
                <div class="search-area">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <div class="filter-label">Início</div><input type="datetime-local" id="inicio" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <div class="filter-label">Fim</div><input type="datetime-local" id="fim" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <div class="filter-label">Status</div><select id="status" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="ATENDIDA">ATENDIDA</option>
                                <option value="ABANDONO">ABANDONO</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="filter-label">Buscar</div><input type="text" id="busca" class="form-control form-control-sm" placeholder="Digite para pesquisar...">
                        </div>
                        <div class="col-md-3 text-end"><button class="btn btn-primary btn-sm" onclick="carregarChamadas(1)">Atualizar</button><button class="btn btn-success btn-sm ms-1" onclick="exportar('listar')"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</button></div>
                    </div>
                </div>
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Data</th>
                                    <th>Fila</th>
                                    <th>Cliente</th>
                                    <th>Agente</th>
                                    <th>Status</th>
                                    <th>Espera</th>
                                    <th>Duração</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-chamadas"></tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-3 border-top">
                        <div class="text-muted small"><span id="total-chamadas">0</span> registros</div>
                        <nav>
                            <ul class="pagination pagination-sm m-0" id="pag-chamadas"></ul>
                        </nav>
                    </div>
                </div>

            <?php elseif ($view === 'pausas'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold m-0">Pausas</h3>
                </div>
                <div class="search-area">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <div class="filter-label">Início</div><input type="datetime-local" id="inicio" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <div class="filter-label">Fim</div><input type="datetime-local" id="fim" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <div class="filter-label">Buscar</div><input type="text" id="busca" class="form-control form-control-sm" placeholder="Digite para pesquisar...">
                        </div>
                        <div class="col-md-3 text-end"><button class="btn btn-primary btn-sm" onclick="carregarPausas(1)">Atualizar</button><button class="btn btn-success btn-sm ms-1" onclick="exportar('pausas')"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</button></div>
                    </div>
                </div>
                <div class="table-card">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Agente</th>
                                <th>Fila</th>
                                <th>Início</th>
                                <th>Fim</th>
                                <th>Duração</th>
                            </tr>
                        </thead>
                        <tbody id="lista-pausas"></tbody>
                    </table>
                    <div class="d-flex justify-content-between align-items-center p-3 border-top">
                        <div class="text-muted small"><span id="total-pausas">0</span> registros</div>
                        <nav>
                            <ul class="pagination pagination-sm m-0" id="pag-pausas"></ul>
                        </nav>
                    </div>
                </div>

            <?php elseif ($view === 'sessoes'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold m-0">Sessões</h3>
                </div>
                <div class="search-area">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <div class="filter-label">Início</div><input type="datetime-local" id="inicio" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <div class="filter-label">Fim</div><input type="datetime-local" id="fim" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <div class="filter-label">Buscar</div><input type="text" id="busca" class="form-control form-control-sm" placeholder="Digite para pesquisar...">
                        </div>
                        <div class="col-md-3 text-end"><button class="btn btn-primary btn-sm" onclick="carregarSessoes(1)">Atualizar</button><button class="btn btn-success btn-sm ms-1" onclick="exportar('sessoes')"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</button></div>
                    </div>
                </div>
                <div class="table-card">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Agente</th>
                                <th>Fila</th>
                                <th>Entrada</th>
                                <th>Saída</th>
                                <th>Tempo Logado</th>
                            </tr>
                        </thead>
                        <tbody id="lista-sessoes"></tbody>
                    </table>
                    <div class="d-flex justify-content-between align-items-center p-3 border-top">
                        <div class="text-muted small"><span id="total-sessoes">0</span> registros</div>
                        <nav>
                            <ul class="pagination pagination-sm m-0" id="pag-sessoes"></ul>
                        </nav>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="modal fade" id="audioModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Player</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"><audio id="playerAudio" controls style="width:100%"></audio></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const API_URL = 'api.php';
        const currentView = '<?php echo $view; ?>';

        // Configuração de Datas
        const initDates = () => {
            if (document.getElementById('inicio')) {
                const d = new Date();
                const y = d.getFullYear(),
                    m = String(d.getMonth() + 1).padStart(2, 0),
                    day = String(d.getDate()).padStart(2, 0);
                document.getElementById('inicio').value = `${y}-${m}-${day}T00:00`;
                document.getElementById('fim').value = `${y}-${m}-${day}T23:59`;
            }
        };

        // Player e Stop no Close
        function playAudio(url) {
            const a = document.getElementById('playerAudio');
            a.src = url;
            new bootstrap.Modal(document.getElementById('audioModal')).show();
            a.play();
        }
        // EVENT LISTENER PARA PARAR O AUDIO
        document.getElementById('audioModal').addEventListener('hidden.bs.modal', function() {
            const a = document.getElementById('playerAudio');
            a.pause();
            a.currentTime = 0;
            a.src = "";
        });

        // Auto-Pesquisa (Debounce)
        let debounceTimer;
        if (document.getElementById('busca')) {
            document.getElementById('busca').addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    if (currentView === 'chamadas') carregarChamadas(1);
                    if (currentView === 'pausas') carregarPausas(1);
                    if (currentView === 'sessoes') carregarSessoes(1);
                }, 600); // 600ms delay para não flodar a API
            });
        }

        function exportar(acao) {
            let p = new URLSearchParams({
                acao: acao,
                export: 'true',
                inicio: document.getElementById('inicio').value,
                fim: document.getElementById('fim').value,
                busca: document.getElementById('busca').value
            });
            if (document.getElementById('status')) p.append('status', document.getElementById('status').value);
            window.open(API_URL + '?' + p.toString(), '_blank');
        }

        function renderPagination(containerId, total, pages, currentPage, callbackName) {
            const div = document.getElementById(containerId);
            if (document.getElementById(containerId.replace('pag-', 'total-'))) document.getElementById(containerId.replace('pag-', 'total-')).innerText = total;
            if (!div) return;
            div.innerHTML = '';

            if (pages < 1) return; // Se tiver 0 ou 1 página, não precisa mostrar

            const btn = (lbl, pg, active) => `<li class="page-item ${active?'active':''}"><a class="page-link" href="#" onclick="${callbackName}(${pg});return false;">${lbl}</a></li>`;
            let html = '';

            if (currentPage > 1) html += btn('<', currentPage - 1, false);

            let start = Math.max(1, currentPage - 2);
            let end = Math.min(pages, start + 4);
            if (end - start < 4) start = Math.max(1, end - 4);

            if (start > 1) {
                html += btn('1', 1, false);
                if (start > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            for (let i = start; i <= end; i++) html += btn(i, i, i === currentPage);
            if (end < pages) {
                if (end < pages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                html += btn(pages, pages, false);
            }

            if (currentPage < pages) html += btn('>', currentPage + 1, false);

            div.innerHTML = `<ul class="pagination pagination-sm m-0">${html}</ul>`;
        }

        // Carregadores
        window.carregarChamadas = (pg = 1) => {
            const tbody = document.getElementById('lista-chamadas');
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5">Carregando...</td></tr>';
            let p = new URLSearchParams({
                acao: 'listar',
                page: pg,
                inicio: document.getElementById('inicio').value,
                fim: document.getElementById('fim').value,
                status: document.getElementById('status').value,
                busca: document.getElementById('busca').value
            });
            fetch(API_URL + '?' + p).then(r => r.json()).then(j => {
                if (j.status == 'error') {
                    tbody.innerHTML = `<tr><td colspan="8" class="text-danger">${j.msg}</td></tr>`;
                    return;
                }
                renderPagination('pag-chamadas', j.total, j.pages, pg, 'window.carregarChamadas');
                let html = '';
                j.data.forEach(r => {
                    let actions = '-';
                    if (r.status_txt == 'ATENDIDA' && r.duracao_fmt != '00:00:00') {
                        // Play + Download
                        actions = `
                <div class="d-flex justify-content-center gap-2">
                    <button onclick="playAudio('${r.link_gravacao}')" class="btn btn-sm btn-outline-primary py-0" title="Ouvir"><i class="bi bi-play-fill"></i></button>
                    <a href="${r.link_download}" class="btn btn-sm btn-outline-secondary py-0" title="Baixar"><i class="bi bi-download"></i></a>
                </div>`;
                    }
                    let st = r.status_txt == 'ATENDIDA' ? 'success' : 'danger';
                    html += `<tr><td>${r.data_fmt}</td><td><span class="badge-soft bg-secondary-soft">${r.nome_fila}</span></td><td>${r.numero_cliente||'-'}</td><td class="fw-bold">${r.nome_agente}</td><td><span class="badge-soft bg-${st}-soft text-${st}">${r.status_txt}</span></td><td class="font-monospace text-muted">${r.espera_fmt}</td><td class="font-monospace fw-bold">${r.duracao_fmt}</td><td class="text-center">${actions}</td></tr>`;
                });
                tbody.innerHTML = html || '<tr><td colspan="8" class="text-center text-muted">Sem registros</td></tr>';
            });
        };

        window.carregarPausas = (pg = 1) => {
            const tbody = document.getElementById('lista-pausas');
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">Carregando...</td></tr>';
            let p = new URLSearchParams({
                acao: 'pausas',
                page: pg,
                inicio: document.getElementById('inicio').value,
                fim: document.getElementById('fim').value,
                busca: document.getElementById('busca').value
            });
            fetch(API_URL + '?' + p).then(r => r.json()).then(j => {
                if (j.status == 'error') {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-danger">${j.msg}</td></tr>`;
                    return;
                }
                renderPagination('pag-pausas', j.total, j.pages, pg, 'window.carregarPausas');
                let html = '';
                j.data.forEach(r => {
                    html += `<tr><td class="fw-bold text-dark">${r.agente}</td><td><span class="badge-soft bg-secondary-soft">${r.fila}</span></td><td>${r.inicio}</td><td>${r.fim}</td><td class="fw-bold font-monospace">${r.duracao}</td></tr>`;
                });
                tbody.innerHTML = html || '<tr><td colspan="5" class="text-center text-muted">Sem pausas</td></tr>';
            });
        };

        window.carregarSessoes = (pg = 1) => {
            const tbody = document.getElementById('lista-sessoes');
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">Carregando...</td></tr>';
            let p = new URLSearchParams({
                acao: 'sessoes',
                page: pg,
                inicio: document.getElementById('inicio').value,
                fim: document.getElementById('fim').value,
                busca: document.getElementById('busca').value
            });
            fetch(API_URL + '?' + p).then(r => r.json()).then(j => {
                if (j.status == 'error') {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-danger">${j.msg}</td></tr>`;
                    return;
                }
                renderPagination('pag-sessoes', j.total, j.pages, pg, 'window.carregarSessoes');
                let html = '';
                j.data.forEach(r => {
                    html += `<tr><td class="fw-bold text-dark">${r.agente}</td><td><span class="badge-soft bg-secondary-soft">${r.fila}</span></td><td>${r.entrada}</td><td>${r.saida}</td><td class="fw-bold text-success font-monospace">${r.tempo_total}</td></tr>`;
                });
                tbody.innerHTML = html || '<tr><td colspan="5" class="text-center text-muted">Sem sessões</td></tr>';
            });
        };

        document.addEventListener('DOMContentLoaded', () => {
            initDates();
            if (currentView === 'chamadas') carregarChamadas();
            if (currentView === 'pausas') carregarPausas();
            if (currentView === 'sessoes') carregarSessoes();
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>