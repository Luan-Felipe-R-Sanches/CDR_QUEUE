<?php
// Arquivo: /var/www/html/relatorios/app.php
require_once 'auth.php';
require_once 'config.php';

$view = $_GET['view'] ?? 'dashboard';
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
        :root { --sidebar-bg: #0f172a; --bg-body: #f8fafc; --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); display: flex; height: 100vh; overflow: hidden; }
        
        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar .brand { padding: 24px; font-weight: 700; font-size: 1.1rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 10px; }
        .main-content { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
        .page-wrapper { padding: 30px 40px; }

        .nav-btn { display: block; padding: 12px 16px; color: #94a3b8; text-decoration: none; border-radius: 8px; margin: 4px 10px; transition: 0.2s; font-size: 0.9rem; }
        .nav-btn:hover { background: rgba(255,255,255,0.05); color: white; }
        .nav-btn.active { background: rgba(59, 130, 246, 0.15); color: #60a5fa; font-weight: 600; }
        .nav-btn i { margin-right: 10px; }

        .search-area { background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .table-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 20px; }
        .filter-label { font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 4px; text-transform: uppercase; }
        
        /* Dashboard */
        .stat-card { background: white; padding: 25px; border-radius: 16px; border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6; box-shadow: 0 2px 4px rgba(0,0,0,0.02); height: 100%; }
        .stat-val { font-size: 2.5rem; font-weight: 700; color: #0f172a; line-height: 1; }
        .card-best { background: var(--primary-gradient); color: white; padding: 30px; border-radius: 16px; position: relative; overflow: hidden; box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.4); margin-bottom: 20px; }
        .trophy-icon { position: absolute; right: -20px; top: 10px; font-size: 8rem; opacity: 0.2; transform: rotate(15deg); }
        .rank-badge { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; background: #f1f5f9; }
        .rank-1 { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
        
        .badge-soft { padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .bg-success-soft { background: #dcfce7; color: #166534; }
        .bg-danger-soft { background: #fee2e2; color: #991b1b; }
        .bg-secondary-soft { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .table tbody td { vertical-align: middle; padding: 12px 15px; }
        .table thead th { background: #f8fafc; font-size: 0.75rem; text-transform: uppercase; color: #64748b; padding: 15px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand">
        <i class="bi bi-headset"></i>&nbsp; VOXBLUE CALL CENTER
    </div>
    
    <div class="mt-3">
        <a href="?view=dashboard" class="nav-btn <?= $view==='dashboard'?'active':'' ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a href="?view=chamadas" class="nav-btn <?= $view==='chamadas'?'active':'' ?>"><i class="bi bi-telephone"></i> Chamadas</a>
        <a href="?view=pausas" class="nav-btn <?= $view==='pausas'?'active':'' ?>"><i class="bi bi-cup-hot"></i> Pausas</a>
        <a href="?view=sessoes" class="nav-btn <?= $view==='sessoes'?'active':'' ?>"><i class="bi bi-person-badge"></i> Sessões</a>
        
        <div class="mt-3 border-top border-secondary pt-2 opacity-50 small ms-3 mb-1">AO VIVO</div>
        <a href="../voxblue-queue/panel-queue/painel.php" class="nav-btn text-warning"><i class="bi bi-activity"></i> Monitor Realtime</a>
    </div>
    <div class="mt-auto pt-4 border-top border-secondary mx-3 mb-3">
        <a href="logout.php" class="btn btn-outline-danger w-100 fw-bold btn-sm"><i class="bi bi-box-arrow-right me-2"></i> SAIR</a>
    </div>
</div>

<div class="main-content">
    <div class="page-wrapper">
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold m-0 text-capitalize"><?= $view ?></h3>
        </div>

        <div class="search-area">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <div class="filter-label">Início</div><input type="datetime-local" id="inicio" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <div class="filter-label">Fim</div><input type="datetime-local" id="fim" class="form-control form-control-sm">
                </div>
                
                <?php if($view === 'chamadas'): ?>
                <div class="col-md-2">
                    <div class="filter-label">Status</div>
                    <select id="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="ATENDIDA">ATENDIDA</option>
                        <option value="ABANDONO">ABANDONO</option>
                    </select>
                </div>
                <?php endif; ?>

                <?php if($view !== 'dashboard'): ?>
                <div class="col-md-3">
                    <div class="filter-label">Buscar</div>
                    <input type="text" id="busca" class="form-control form-control-sm" placeholder="Nome, número, fila...">
                </div>
                <?php endif; ?>

                <div class="col text-end">
                    <button class="btn btn-primary btn-sm px-4 fw-bold" onclick="loadData(1)"><i class="bi bi-arrow-repeat"></i> Atualizar</button>
                    <?php if($view !== 'dashboard'): ?>
                        <button class="btn btn-success btn-sm ms-1" onclick="exportData()"><i class="bi bi-file-spreadsheet"></i> CSV</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if($view === 'dashboard'): ?>
        <div id="dash-content">
            <div class="row g-4 mb-4">
                <div class="col-md-3"><div class="stat-card" style="border-color:#10b981;">
                    <div class="text-muted small fw-bold mb-2">SLA (< 60s)</div>
                    <div class="stat-val text-success" id="kpi-sla">0%</div>
                </div></div>
                <div class="col-md-3"><div class="stat-card" style="border-color:#ef4444;">
                    <div class="text-muted small fw-bold mb-2">ABANDONO (> 60s)</div>
                    <div class="stat-val text-danger" id="kpi-abd">0%</div>
                </div></div>
                <div class="col-md-3"><div class="stat-card" style="border-color:#f59e0b;">
                    <div class="text-muted small fw-bold mb-2">LONGAS (> 20m)</div>
                    <div class="stat-val text-warning" id="kpi-lng">0%</div>
                </div></div>
                <div class="col-md-3"><div class="stat-card" style="border-color:#3b82f6;">
                    <div class="text-muted small fw-bold mb-2">TOTAL</div>
                    <div class="stat-val text-primary" id="kpi-tot">0</div>
                </div></div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card-best">
                        <i class="bi bi-trophy-fill trophy-icon"></i>
                        <div class="text-white-50 small fw-bold mb-2">MELHOR DESEMPENHO</div>
                        <h2 class="fw-bold mb-3 text-truncate" id="best-name">--</h2>
                        <div class="bg-white text-dark px-3 py-1 rounded-pill d-inline-flex fw-bold shadow-sm">
                            <i class="bi bi-check-circle-fill text-success me-2"></i> <span id="best-val">0</span> Atendimentos
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="table-card">
                        <div class="p-3 border-bottom bg-light fw-bold text-secondary">RANKING</div>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead><tr><th class="ps-4">#</th><th>Agente</th><th>Fila</th><th class="text-center">Atendidas</th><th class="text-end pe-4">TMA</th></tr></thead>
                                <tbody id="dash-ranking"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light" id="list-head"></thead>
                    <tbody id="list-body"></tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <div class="text-muted small"><span id="total-regs">0</span> registros</div>
                <nav><ul class="pagination pagination-sm m-0" id="pag-nav"></ul></nav>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<div class="modal fade" id="audioModal"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header py-2"><h6 class="modal-title">Player</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-2"><audio id="playerAudio" controls style="width:100%"></audio></div></div></div></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const API_URL = 'api.php';
    const VIEW = '<?= $view ?>'; // Variável PHP para JS

    $(document).ready(() => {
        const d = new Date();
        const fmt = (n) => String(n).padStart(2,'0');
        const today = `${d.getFullYear()}-${fmt(d.getMonth()+1)}-${fmt(d.getDate())}`;
        
        // Só define datas se os campos estiverem vazios
        if(!$('#inicio').val()) $('#inicio').val(`${today}T00:00`);
        if(!$('#fim').val()) $('#fim').val(`${today}T23:59`);
        
        loadData(1);
        
        // Cabeçalhos Dinâmicos
        if(VIEW === 'chamadas') $('#list-head').html('<tr><th>Data</th><th>Fila</th><th>Cliente</th><th>Agente</th><th>Status</th><th>Espera</th><th>Duração</th><th class="text-center">Ações</th></tr>');
        if(VIEW === 'pausas') $('#list-head').html('<tr><th>Agente</th><th>Fila</th><th>Início</th><th>Fim</th><th>Duração</th></tr>');
        if(VIEW === 'sessoes') $('#list-head').html('<tr><th>Agente</th><th>Fila</th><th>Entrada</th><th>Saída</th><th>Tempo Logado</th></tr>');

        // BUSCA AJAX INSTANTÂNEA
        let debounce;
        $('#busca').on('input', function() {
            clearTimeout(debounce);
            debounce = setTimeout(() => loadData(1), 500); // Aguarda 500ms
        });
    });

    function loadData(page = 1) {
        // Mapeia o nome da view para a ação correta da API
        let actionName = (VIEW === 'dashboard' ? 'dashboard' : (VIEW === 'chamadas' ? 'listar' : VIEW));

        let params = {
            acao: actionName,
            page: page,
            inicio: $('#inicio').val(),
            fim: $('#fim').val(),
            busca: $('#busca').val(),
            status: $('#status').val()
        };

        const tbody = (VIEW === 'dashboard') ? $('#dash-ranking') : $('#list-body');
        tbody.html('<tr><td colspan="8" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm"></div> Carregando...</td></tr>');

        $.getJSON(API_URL, params, (res) => {
            if(res.status === 'error') { tbody.html(`<tr><td colspan="8" class="text-danger text-center">${res.msg}</td></tr>`); return; }

            if(VIEW === 'dashboard') {
                $('#kpi-sla').text(res.kpis.sla_percent + '%');
                $('#kpi-abd').text(res.kpis.abandono_percent + '%');
                $('#kpi-lng').text(res.kpis.longas_percent + '%');
                $('#kpi-tot').text(res.kpis.total_absoluto);
                $('#best-name').text(res.melhor_agente.nome);
                $('#best-val').text(res.melhor_agente.qtd);

                let html = '';
                if(!res.ranking.length) html = '<tr><td colspan="6" class="text-center text-muted">Sem dados.</td></tr>';
                else {
                    res.ranking.forEach((r, i) => {
                        let badge = (i < 3) ? `rank-badge rank-${i+1}` : 'rank-badge';
                        html += `<tr><td class="ps-4"><div class="${badge}">${i+1}</div></td><td class="fw-bold text-dark">${r.agente}</td><td><span class="badge-soft bg-secondary-soft">${r.fila}</span></td><td class="text-center fs-5 fw-bold text-primary">${r.atendidas}</td><td class="text-end pe-4 font-monospace fw-bold text-secondary">${r.tma}</td></tr>`;
                    });
                }
                tbody.html(html);
            } else {
                renderTable(res.data);
                renderPagination(res.total, res.pages, page);
            }
        });
    }

    function renderTable(data) {
        let html = '';
        if(!data.length) html = '<tr><td colspan="8" class="text-center text-muted py-4">Nenhum registro encontrado.</td></tr>';
        
        data.forEach(r => {
            if(VIEW === 'chamadas') {
                let stClass = r.status_txt === 'ATENDIDA' ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger';
                let actions = r.status_txt === 'ATENDIDA' && r.duracao_fmt !== '00:00:00' 
                    ? `<button onclick="play('${r.link_gravacao}')" class="btn btn-sm btn-outline-primary py-0 border-0" title="Ouvir"><i class="bi bi-play-circle-fill fs-5"></i></button>
                       <a href="${r.link_download}" class="btn btn-sm btn-outline-secondary py-0 border-0" title="Baixar"><i class="bi bi-download fs-5"></i></a>` 
                    : '-';
                
                html += `<tr><td>${r.data_fmt}</td><td><span class="badge-soft bg-secondary-soft">${r.nome_fila}</span></td><td>${r.numero_cliente||'-'}</td><td class="fw-bold">${r.nome_agente}</td><td><span class="badge-soft ${stClass}">${r.status_txt}</span></td><td class="font-monospace text-muted">${r.espera_fmt}</td><td class="font-monospace fw-bold">${r.duracao_fmt}</td><td class="text-center">${actions}</td></tr>`;
            }
            if(VIEW === 'pausas') {
                html += `<tr><td class="fw-bold">${r.agente}</td><td><span class="badge-soft bg-secondary-soft">${r.fila}</span></td><td>${r.inicio}</td><td>${r.fim}</td><td class="font-monospace fw-bold">${r.duracao}</td></tr>`;
            }
            if(VIEW === 'sessoes') {
                html += `<tr><td class="fw-bold">${r.agente}</td><td><span class="badge-soft bg-secondary-soft">${r.fila}</span></td><td>${r.entrada}</td><td>${r.saida}</td><td class="font-monospace fw-bold text-success">${r.tempo_total}</td></tr>`;
            }
        });
        $('#list-body').html(html);
    }

    function renderPagination(total, pages, curr) {
        $('#total-regs').text(total);
        let html = '';
        if(pages > 1) {
            if(curr > 1) html += `<li class="page-item"><a class="page-link" href="#" onclick="loadData(${curr-1})">&laquo;</a></li>`;
            let start = Math.max(1, curr - 2), end = Math.min(pages, start + 4);
            for(let i=start; i<=end; i++) html += `<li class="page-item ${i===curr?'active':''}"><a class="page-link" href="#" onclick="loadData(${i})">${i}</a></li>`;
            if(curr < pages) html += `<li class="page-item"><a class="page-link" href="#" onclick="loadData(${curr+1})">&raquo;</a></li>`;
        }
        $('#pag-nav').html(html);
    }

    function play(url) {
        $('#playerAudio').attr('src', url);
        new bootstrap.Modal('#audioModal').show();
        let audio = document.getElementById('playerAudio');
        audio.load(); // Garante que recarrega o novo source
        audio.play().catch(e => console.log("Autoplay bloqueado ou erro: ", e));
    }
    
    $('#audioModal').on('hidden.bs.modal', function() { 
        let a = document.getElementById('playerAudio'); a.pause(); a.currentTime=0; a.src=""; 
    });

    // CORREÇÃO AQUI: Função de Exportação
    function exportData() {
        // Se a view for 'chamadas', mudamos para 'listar', senão mantemos o nome original (pausas, sessoes)
        let actionName = (VIEW === 'chamadas' ? 'listar' : VIEW);

        let qs = $.param({
            acao: actionName, 
            export: 'true',
            inicio: $('#inicio').val(), 
            fim: $('#fim').val(),
            busca: $('#busca').val(), 
            status: $('#status').val()
        });
        
        // Abre numa nova aba para forçar o download
        window.open(API_URL + '?' + qs, '_blank');
    }
</script>
</body>
</html>