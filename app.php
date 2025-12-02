<?php
// Arquivo: /var/www/html/relatorios/app.php
require_once 'config.php';

// --- LÓGICA DO DASHBOARD (Server-Side para não travar) ---
$view = $_GET['view'] ?? 'dashboard';
$dashData = [];
$dashError = '';

if ($view === 'dashboard') {
    try {
        $pdo = getConexao();
        
        // Filtro de Data (Padrão: Hoje)
        $data_hoje = date('Y-m-d');
        $data_filtro = $_GET['data'] ?? $data_hoje;
        
        $inicio = $data_filtro . ' 00:00:00';
        $fim    = $data_filtro . ' 23:59:59';

        // 1. Query BRUTA (Sem agrupamento SQL para evitar erro)
        $sql = "SELECT agent, event, data1, data2, data3, queuename 
                FROM queue_log 
                WHERE time BETWEEN :inicio AND :fim 
                AND event IN ('COMPLETECALLER','COMPLETEAGENT','CONNECT','ABANDON','EXITWITHTIMEOUT','PAUSE','RINGNOANSWER')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['inicio' => $inicio, 'fim' => $fim]);
        
        // 2. Processamento Matemático no PHP (Infalível)
        $kpis = ['total' => 0, 'atendidas' => 0, 'sla_ok' => 0, 'abandono_lento' => 0, 'longas' => 0];
        $agentes = [];

        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ag = trim(str_replace(['PJSIP/','SIP/','IAX2/'], '', $r['agent']));
            if ($ag == 'NONE' || empty($ag)) continue;

            // Inicializa agente
            if (!isset($agentes[$ag])) {
                $agentes[$ag] = [
                    'nome' => $map_agentes[$ag] ?? $ag,
                    'fila' => $map_filas[$r['queuename']] ?? $r['queuename'],
                    'atendidas' => 0, 'pausas' => 0, 'rejeitadas' => 0, 'tempo' => 0
                ];
            }

            // Cálculos
            if (in_array($r['event'], ['COMPLETECALLER', 'COMPLETEAGENT'])) {
                $kpis['total']++; 
                $kpis['atendidas']++;
                $agentes[$ag]['atendidas']++;
                $agentes[$ag]['tempo'] += (int)$r['data2'];
                if ((int)$r['data2'] > 1200) $kpis['longas']++;
            }
            
            if ($r['event'] == 'CONNECT') {
                if ((int)$r['data1'] <= 60) $kpis['sla_ok']++;
            }
            
            if (in_array($r['event'], ['ABANDON','EXITWITHTIMEOUT'])) {
                $kpis['total']++; // Conta abandono no total
                if ((int)$r['data3'] > 60) $kpis['abandono_lento']++;
            }
            
            if ($r['event'] == 'PAUSE') $agentes[$ag]['pausas']++;
            if ($r['event'] == 'RINGNOANSWER') $agentes[$ag]['rejeitadas']++;
        }

        // Ordenação
        usort($agentes, function($a, $b) { return $b['atendidas'] - $a['atendidas']; });

        // Totais Finais
        $base_total = $kpis['total'] > 0 ? $kpis['total'] : 1;
        $base_at = $kpis['atendidas'] > 0 ? $kpis['atendidas'] : 1;

        $dashData = [
            'sla' => round(($kpis['sla_ok'] / $base_at) * 100, 1),
            'abandono' => round(($kpis['abandono_lento'] / $base_total) * 100, 1),
            'longas' => round(($kpis['longas'] / $base_at) * 100, 1),
            'total' => $kpis['total'],
            'ranking' => $agentes,
            'melhor' => $agentes[0] ?? ['nome' => '-', 'atendidas' => 0]
        ];

    } catch (Exception $e) {
        $dashError = $e->getMessage();
    }
}
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
        /* ESTILOS VISUAIS */
        :root { --sidebar-bg: #0f172a; --bg-body: #f8fafc; --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar .brand { padding: 24px; font-weight: 700; font-size: 1.1rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 10px; }
        .nav-btn { display: block; padding: 12px 16px; color: #94a3b8; text-decoration: none; border-radius: 8px; margin: 4px 10px; transition: 0.2s; }
        .nav-btn:hover { background: rgba(255,255,255,0.05); color: white; }
        .nav-btn.active { background: rgba(59, 130, 246, 0.15); color: #60a5fa; font-weight: 600; }
        .nav-btn i { margin-right: 10px; }

        /* Main */
        .main-content { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
        .page-wrapper { padding: 30px 40px; }
        
        /* Dashboard Cards */
        .stat-card { background: white; padding: 25px; border-radius: 16px; border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .stat-val { font-size: 2.5rem; font-weight: 700; color: #0f172a; }
        .card-best { background: var(--primary-gradient); color: white; padding: 30px; border-radius: 16px; position: relative; overflow: hidden; box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.4); }
        .trophy-icon { position: absolute; right: -20px; top: 10px; font-size: 8rem; opacity: 0.2; transform: rotate(15deg); }
        
        /* Tabela */
        .table-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 20px; }
        .table thead th { background: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; padding: 15px 20px; font-weight: 600; }
        .table tbody td { padding: 15px 20px; vertical-align: middle; }
        
        /* Badges */
        .rank-badge { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; background: #f1f5f9; }
        .rank-1 { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
        .rank-2 { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .rank-3 { background: #ffedd5; color: #9a3412; border: 1px solid #fdba74; }
        
        .badge-soft { padding: 5px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .bg-success-soft { background: #dcfce7; color: #166534; } 
        .bg-danger-soft { background: #fee2e2; color: #991b1b; }
        .bg-warning-soft { background: #fef9c3; color: #854d0e; }

        /* Filtros */
        .search-area { background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .filter-label { font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand"><i class="bi bi-graph-up-arrow"></i>&nbsp; Analytics Pro</div>
    <div class="mt-3">
        <a href="?view=dashboard" class="nav-btn <?php echo $view=='dashboard'?'active':''; ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a href="?view=chamadas" class="nav-btn <?php echo $view=='chamadas'?'active':''; ?>"><i class="bi bi-telephone"></i> Chamadas</a>
        <a href="?view=pausas" class="nav-btn <?php echo $view=='pausas'?'active':''; ?>"><i class="bi bi-cup-hot"></i> Pausas</a>
        <a href="?view=sessoes" class="nav-btn <?php echo $view=='sessoes'?'active':''; ?>"><i class="bi bi-person-badge"></i> Sessões</a>
    <div class="mt-3 border-top border-secondary pt-2 opacity-50 small ms-3 mb-1">AO VIVO</div>
    <a href="realtime/painel.php" class="nav-btn text-warning">
        <i class="bi bi-activity animate-pulse"></i> Monitor Realtime
    </a>
    </div>
</div>

<div class="main-content">
    <div class="page-wrapper">
        
        <?php if ($view === 'dashboard'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold m-0">Visão Geral da Operação</h3>
                    <p class="text-muted m-0 small">Dados calculados em tempo real</p>
                </div>
                <form class="d-flex bg-white p-1 rounded border align-items-center gap-2">
                    <i class="bi bi-calendar4 text-muted ms-2"></i>
                    <input type="date" name="data" value="<?php echo isset($_GET['data']) ? $_GET['data'] : date('Y-m-d'); ?>" class="form-control border-0 fw-bold text-secondary" onchange="this.form.submit()">
                </form>
            </div>

            <?php if ($dashError): ?>
                <div class="alert alert-danger">Erro no cálculo: <?php echo $dashError; ?></div>
            <?php else: ?>
                <div class="row g-4 mb-4">
                    <div class="col-md-3"><div class="stat-card" style="border-color:#10b981;"><div class="text-muted small fw-bold text-uppercase">SLA (< 60s)</div><div class="stat-val text-success"><?php echo $dashData['sla']; ?>%</div></div></div>
                    <div class="col-md-3"><div class="stat-card" style="border-color:#ef4444;"><div class="text-muted small fw-bold text-uppercase">Abandono (> 60s)</div><div class="stat-val text-danger"><?php echo $dashData['abandono']; ?>%</div></div></div>
                    <div class="col-md-3"><div class="stat-card" style="border-color:#f59e0b;"><div class="text-muted small fw-bold text-uppercase">Longas (> 20m)</div><div class="stat-val text-warning"><?php echo $dashData['longas']; ?>%</div></div></div>
                    <div class="col-md-3"><div class="stat-card" style="border-color:#3b82f6;"><div class="text-muted small fw-bold text-uppercase">Total Chamadas</div><div class="stat-val text-primary"><?php echo $dashData['total']; ?></div></div></div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card-best h-100 d-flex flex-column justify-content-center">
                            <i class="bi bi-trophy-fill trophy-icon"></i>
                            <div class="text-white-50 small fw-bold text-uppercase mb-2">Campeão do Dia</div>
                            <h2 class="fw-bold mb-3"><?php echo $dashData['melhor']['nome']; ?></h2>
                            <div class="bg-white text-dark px-3 py-2 rounded-pill d-inline-flex align-items-center gap-2 fw-bold shadow-sm" style="width: fit-content;">
                                <i class="bi bi-check-circle-fill text-success"></i> <?php echo $dashData['melhor']['atendidas']; ?> Atendimentos
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="table-card">
                            <div class="p-3 border-bottom bg-light fw-bold text-secondary">Ranking de Performance</div>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="bg-light"><tr><th class="ps-4">#</th><th>Agente</th><th>Fila</th><th class="text-center">Atendidas</th><th class="text-center">Rejeitadas</th><th class="text-end pe-4">TMA</th></tr></thead>
                                    <tbody>
                                        <?php 
                                        $pos=1; 
                                        foreach($dashData['ranking'] as $r): 
                                            $tma = $r['atendidas'] > 0 ? round($r['tempo']/$r['atendidas']) : 0;
                                        ?>
                                        <tr>
                                            <td class="ps-4"><div class="rank-badge <?php echo $pos<=3?'rank-'.$pos:''; ?>"><?php echo $pos; ?></div></td>
                                            <td class="fw-bold text-dark"><?php echo $r['nome']; ?></td>
                                            <td><span class="badge bg-light text-secondary border"><?php echo $r['fila']; ?></span></td>
                                            <td class="text-center fw-bold text-primary fs-5"><?php echo $r['atendidas']; ?></td>
                                            <td class="text-center <?php echo $r['rejeitadas']>0?'text-danger fw-bold':'text-muted'; ?>"><?php echo $r['rejeitadas']; ?></td>
                                            <td class="text-end pe-4 font-monospace text-secondary"><?php echo gmdate("H:i:s", $tma); ?></td>
                                        </tr>
                                        <?php $pos++; endforeach; ?>
                                        <?php if(empty($dashData['ranking'])) echo '<tr><td colspan="6" class="text-center py-5 text-muted">Sem dados hoje.</td></tr>'; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php elseif ($view === 'chamadas'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4"><h3 class="fw-bold m-0">Chamadas</h3></div>
            <div class="search-area">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2"><div class="filter-label">Início</div><input type="datetime-local" id="inicio" class="form-control form-control-sm"></div>
                    <div class="col-md-2"><div class="filter-label">Fim</div><input type="datetime-local" id="fim" class="form-control form-control-sm"></div>
                    <div class="col-md-2"><div class="filter-label">Status</div><select id="status" class="form-select form-select-sm"><option value="">Todos</option><option value="ATENDIDA">ATENDIDA</option><option value="ABANDONO">ABANDONO</option></select></div>
                    <div class="col-md-3"><div class="filter-label">Buscar</div><input type="text" id="busca" class="form-control form-control-sm" placeholder="Nome, Cliente, ID..."></div>
                    <div class="col-md-3 text-end">
                        <button class="btn btn-primary btn-sm" onclick="carregarChamadas(1)">Atualizar</button>
                        <button class="btn btn-success btn-sm ms-1" onclick="exportar('listar')"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</button>
                    </div>
                </div>
            </div>
            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0"><thead class="bg-light"><tr><th>Data</th><th>Fila</th><th>Cliente</th><th>Agente</th><th>Status</th><th>Espera</th><th>Duração</th><th class="text-center">Áudio</th></tr></thead><tbody id="lista-chamadas"></tbody></table>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3 border-top"><div class="text-muted small"><span id="total-chamadas">0</span> registros</div><nav><ul class="pagination pagination-sm m-0" id="pag-chamadas"></ul></nav></div>
            </div>

        <?php elseif ($view === 'pausas'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4"><h3 class="fw-bold m-0">Pausas</h3></div>
            <div class="search-area">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3"><div class="filter-label">Início</div><input type="datetime-local" id="inicio" class="form-control form-control-sm"></div>
                    <div class="col-md-3"><div class="filter-label">Fim</div><input type="datetime-local" id="fim" class="form-control form-control-sm"></div>
                    <div class="col-md-3"><div class="filter-label">Buscar</div><input type="text" id="busca" class="form-control form-control-sm" placeholder="Agente..."></div>
                    <div class="col-md-3 text-end">
                        <button class="btn btn-primary btn-sm" onclick="carregarPausas(1)">Atualizar</button>
                        <button class="btn btn-success btn-sm ms-1" onclick="exportar('pausas')"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</button>
                    </div>
                </div>
            </div>
            <div class="table-card"><table class="table table-hover align-middle mb-0"><thead class="bg-light"><tr><th>Agente</th><th>Motivo</th><th>Início</th><th>Fim</th><th>Duração</th></tr></thead><tbody id="lista-pausas"></tbody></table><div class="d-flex justify-content-between align-items-center p-3 border-top"><div class="text-muted small"><span id="total-pausas">0</span> registros</div><nav><ul class="pagination pagination-sm m-0" id="pag-pausas"></ul></nav></div></div>

        <?php elseif ($view === 'sessoes'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4"><h3 class="fw-bold m-0">Sessões</h3></div>
            <div class="search-area">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3"><div class="filter-label">Início</div><input type="datetime-local" id="inicio" class="form-control form-control-sm"></div>
                    <div class="col-md-3"><div class="filter-label">Fim</div><input type="datetime-local" id="fim" class="form-control form-control-sm"></div>
                    <div class="col-md-3"><div class="filter-label">Buscar</div><input type="text" id="busca" class="form-control form-control-sm" placeholder="Agente..."></div>
                    <div class="col-md-3 text-end">
                        <button class="btn btn-primary btn-sm" onclick="carregarSessoes(1)">Atualizar</button>
                        <button class="btn btn-success btn-sm ms-1" onclick="exportar('sessoes')"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</button>
                    </div>
                </div>
            </div>
            <div class="table-card"><table class="table table-hover align-middle mb-0"><thead class="bg-light"><tr><th>Agente</th><th>Fila</th><th>Entrada</th><th>Saída</th><th>Tempo Logado</th></tr></thead><tbody id="lista-sessoes"></tbody></table><div class="d-flex justify-content-between align-items-center p-3 border-top"><div class="text-muted small"><span id="total-sessoes">0</span> registros</div><nav><ul class="pagination pagination-sm m-0" id="pag-sessoes"></ul></nav></div></div>
        <?php endif; ?>

    </div>
</div>

<div class="modal fade" id="audioModal"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Player</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><audio id="playerAudio" controls style="width:100%"></audio></div></div></div></div>

<script>
const API_URL = 'api.php';

// Inicializa Datas (apenas se os inputs existirem)
const initDates = () => {
    if(document.getElementById('inicio')) {
        const d = new Date();
        const y = d.getFullYear(), m = String(d.getMonth()+1).padStart(2,0), day = String(d.getDate()).padStart(2,0);
        document.getElementById('inicio').value = `${y}-${m}-${day}T00:00`;
        document.getElementById('fim').value = `${y}-${m}-${day}T23:59`;
    }
};

function playAudio(url) {
    const a = document.getElementById('playerAudio'); a.src = url;
    new bootstrap.Modal(document.getElementById('audioModal')).show();
    a.play().catch(e=>{});
}

function exportar(acao) {
    let p = new URLSearchParams({
        acao: acao, export: 'true',
        inicio: document.getElementById('inicio').value,
        fim: document.getElementById('fim').value,
        busca: document.getElementById('busca').value
    });
    if(document.getElementById('status')) p.append('status', document.getElementById('status').value);
    window.open(API_URL + '?' + p.toString(), '_blank');
}

function renderPagination(containerId, total, pages, currentPage, callbackName) {
    const div = document.getElementById(containerId);
    const infoDiv = document.getElementById(containerId.replace('pag-', 'total-'));
    if(infoDiv) infoDiv.innerText = total;
    if(!div) return;
    div.innerHTML = '';
    if(pages <= 1) return;

    const btn = (lbl, pg, active) => `<li class="page-item ${active?'active':''}"><a class="page-link" href="#" onclick="${callbackName}(${pg});return false;">${lbl}</a></li>`;
    let html = '';
    if(currentPage > 1) html += btn('<', currentPage-1, false);
    
    let start = Math.max(1, currentPage - 2);
    let end = Math.min(pages, start + 4);
    if(end - start < 4) start = Math.max(1, end - 4);

    if(start > 1) { html += btn('1', 1, false); if(start > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`; }
    for(let i=start; i<=end; i++) html += btn(i, i, i===currentPage);
    if(end < pages) { if(end < pages-1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`; html += btn(pages, pages, false); }

    if(currentPage < pages) html += btn('>', currentPage+1, false);
    div.innerHTML = `<ul class="pagination pagination-sm m-0">${html}</ul>`;
}

// Carregadores
window.carregarChamadas = (pg=1) => {
    const tbody = document.getElementById('lista-chamadas');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5">Carregando...</td></tr>';
    let p = new URLSearchParams({ acao:'listar', page:pg, inicio:document.getElementById('inicio').value, fim:document.getElementById('fim').value, status:document.getElementById('status').value, busca:document.getElementById('busca').value });
    fetch(API_URL+'?'+p).then(r=>r.json()).then(j=>{
        if(j.status=='error'){tbody.innerHTML=`<tr><td colspan="8" class="text-danger">${j.msg}</td></tr>`;return;}
        renderPagination('pag-chamadas', j.total, j.pages, pg, 'window.carregarChamadas');
        if(!j.data.length){tbody.innerHTML='<tr><td colspan="8" class="text-center text-muted">Sem registros</td></tr>';return;}
        let html='';
        j.data.forEach(r=>{
            let btn = (r.status_txt=='ATENDIDA' && r.duracao_fmt!='00:00:00') ? `<button onclick="playAudio('${r.link_gravacao}')" class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-play-fill"></i></button>` : '-';
            let st = r.status_txt=='ATENDIDA' ? 'success' : 'danger';
            html+=`<tr><td>${r.data_fmt}</td><td><span class="badge bg-light text-dark border">${r.nome_fila}</span></td><td>${r.numero_cliente||'-'}</td><td class="fw-bold">${r.nome_agente}</td><td><span class="badge-soft bg-${st}-soft text-${st}">${r.status_txt}</span></td><td class="font-monospace text-muted">${r.espera_fmt}</td><td class="font-monospace fw-bold">${r.duracao_fmt}</td><td class="text-center">${btn}</td></tr>`;
        });
        tbody.innerHTML = html;
    });
};

window.carregarPausas = (pg=1) => {
    const tbody = document.getElementById('lista-pausas');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center">Carregando...</td></tr>';
    let p = new URLSearchParams({ acao:'pausas', page:pg, inicio:document.getElementById('inicio').value, fim:document.getElementById('fim').value, busca:document.getElementById('busca').value });
    fetch(API_URL+'?'+p).then(r=>r.json()).then(j=>{
        if(j.status=='error'){tbody.innerHTML=`<tr><td colspan="5" class="text-danger">${j.msg}</td></tr>`;return;}
        renderPagination('pag-pausas', j.total, j.pages, pg, 'window.carregarPausas');
        if(!j.data.length){tbody.innerHTML='<tr><td colspan="5" class="text-center text-muted">Sem pausas</td></tr>';return;}
        let html='';
        j.data.forEach(r=>{ html+=`<tr><td class="fw-bold text-dark">${r.agente}</td><td><span class="badge-soft bg-warning-soft">${r.motivo}</span></td><td>${r.inicio}</td><td>${r.fim}</td><td class="fw-bold font-monospace">${r.duracao}</td></tr>`; });
        tbody.innerHTML = html;
    });
};

window.carregarSessoes = (pg=1) => {
    const tbody = document.getElementById('lista-sessoes');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center">Carregando...</td></tr>';
    let p = new URLSearchParams({ acao:'sessoes', page:pg, inicio:document.getElementById('inicio').value, fim:document.getElementById('fim').value, busca:document.getElementById('busca').value });
    fetch(API_URL+'?'+p).then(r=>r.json()).then(j=>{
        if(j.status=='error'){tbody.innerHTML=`<tr><td colspan="5" class="text-danger">${j.msg}</td></tr>`;return;}
        renderPagination('pag-sessoes', j.total, j.pages, pg, 'window.carregarSessoes');
        if(!j.data.length){tbody.innerHTML='<tr><td colspan="5" class="text-center text-muted">Sem sessões</td></tr>';return;}
        let html='';
        j.data.forEach(r=>{ html+=`<tr><td class="fw-bold text-dark">${r.agente}</td><td>${r.fila}</td><td>${r.entrada}</td><td>${r.saida}</td><td class="fw-bold text-success font-monospace">${r.tempo_total}</td></tr>`; });
        tbody.innerHTML = html;
    });
};

document.addEventListener('DOMContentLoaded', () => {
    initDates();
    const view = '<?php echo $view; ?>';
    // Se for dashboard, a lógica PHP já rodou. Se for outro, roda JS.
    if(view==='chamadas') carregarChamadas();
    if(view==='pausas') carregarPausas();
    if(view==='sessoes') carregarSessoes();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>