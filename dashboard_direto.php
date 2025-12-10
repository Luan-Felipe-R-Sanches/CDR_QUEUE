<?php
// Arquivo: /var/www/html/relatorios/dashboard_direto.php
require_once 'auth.php'; // Proteção de Login
require_once 'config.php';

// --- LÓGICA PHP (MANTIDA) ---
$data_hoje = date('Y-m-d');
$inicio_raw = $_GET['inicio'] ?? $data_hoje . ' 00:00:00';
$fim_raw    = $_GET['fim']    ?? $data_hoje . ' 23:59:59';

$inicio = str_replace('T', ' ', $inicio_raw);
$fim    = str_replace('T', ' ', $fim_raw);

if (strlen($inicio) <= 16) $inicio .= ':00';
if (strlen($fim) <= 16) {
    if (substr($fim, 11, 5) == '00:00') $fim = substr($fim, 0, 10) . ' 23:59:59';
    else $fim .= ':59';
}

$inicio_html = date('Y-m-d\TH:i', strtotime($inicio));
$fim_html    = date('Y-m-d\TH:i', strtotime($fim));

$pagina_atual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$por_pagina = 10; 

$kpis = ['total'=>0, 'atendidas'=>0, 'sla_ok'=>0, 'abandono'=>0, 'abandono_lento'=>0, 'longas'=>0];
$agentes = [];
$melhor_agente = ['nome'=>'-', 'atendidas'=>0];

try {
    $pdo = getConexao();
    $sql = "SELECT agent, event, data1, data2, data3, queuename 
            FROM queue_log 
            WHERE time BETWEEN :inicio AND :fim 
            AND event IN ('COMPLETECALLER','COMPLETEAGENT','CONNECT','ABANDON','EXITWITHTIMEOUT','PAUSE','RINGNOANSWER')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':inicio' => $inicio, ':fim' => $fim]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $evt = $row['event'];
        $d1 = (int)$row['data1']; $d2 = (int)$row['data2']; $d3 = (int)$row['data3']; 
        $ag_cod = trim(str_replace(['PJSIP/','SIP/','IAX2/'], '', $row['agent']));
        if (empty($ag_cod) || $ag_cod == 'NONE') continue;
        
        if (!isset($agentes[$ag_cod])) {
            $agentes[$ag_cod] = ['nome' => isset($map_agentes[$ag_cod]) ? $map_agentes[$ag_cod] : $ag_cod, 'fila' => isset($map_filas[$row['queuename']]) ? $map_filas[$row['queuename']] : $row['queuename'], 'atendidas' => 0, 'pausas' => 0, 'rejeitadas' => 0, 'tempo_falado' => 0];
        }

        if (in_array($evt, ['COMPLETECALLER','COMPLETEAGENT'])) { $kpis['total']++; $kpis['atendidas']++; $agentes[$ag_cod]['atendidas']++; $agentes[$ag_cod]['tempo_falado'] += $d2; if ($d2 > 1200) $kpis['longas']++; }
        if ($evt == 'CONNECT' && $d1 <= 60) $kpis['sla_ok']++;
        if (in_array($evt, ['ABANDON','EXITWITHTIMEOUT'])) { $kpis['total']++; $kpis['abandono']++; if ($d3 > 60) $kpis['abandono_lento']++; }
        if ($evt == 'PAUSE') $agentes[$ag_cod]['pausas']++;
        if ($evt == 'RINGNOANSWER') $agentes[$ag_cod]['rejeitadas']++;
    }

    $base_at = $kpis['atendidas'] > 0 ? $kpis['atendidas'] : 1;
    $base_tot = $kpis['total'] > 0 ? $kpis['total'] : 1;
    $perc_sla = round(($kpis['sla_ok'] / $base_at) * 100, 1);
    $perc_abandono = round(($kpis['abandono_lento'] / $base_tot) * 100, 1);
    $perc_longas = round(($kpis['longas'] / $base_at) * 100, 1);

    usort($agentes, function($a, $b) { return $b['atendidas'] - $a['atendidas']; });
    if (!empty($agentes)) $melhor_agente = $agentes[0];

    $total_agentes = count($agentes);
    $total_paginas = ceil($total_agentes / $por_pagina);
    $offset = ($pagina_atual - 1) * $por_pagina;
    $agentes_paginados = array_slice($agentes, $offset, $por_pagina);

} catch (Exception $e) { die("Erro: " . $e->getMessage()); }

function secToTime($s) { return gmdate("H:i:s", $s); }
function urlPagina($pg) { global $inicio_html, $fim_html; return "?page=$pg&inicio=" . urlencode($inicio_html) . "&fim=" . urlencode($fim_html); }
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Analytics Pro - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        /* --- ESTILO PADRÃO (IDÊNTICO AO APP.PHP) --- */
        :root { --sidebar-bg: #0f172a; --bg-body: #f8fafc; --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); display: flex; height: 100vh; overflow: hidden; }
        
        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar .brand { padding: 24px; font-weight: 700; font-size: 1.1rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 10px; }
        
        /* Botões do Menu (Padronizados) */
        .nav-btn { display: block; padding: 12px 16px; color: #94a3b8; text-decoration: none; border-radius: 8px; margin: 4px 10px; transition: 0.2s; font-size: 0.95rem; }
        .nav-btn:hover { background: rgba(255,255,255,0.05); color: white; }
        .nav-btn.active { background: rgba(59, 130, 246, 0.15); color: #60a5fa; font-weight: 600; }
        .nav-btn i { margin-right: 10px; }

        .main-content { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
        .page-wrapper { padding: 30px 40px; }
        
        /* Cards */
        .stat-card { background: white; padding: 25px; border-radius: 16px; border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6; box-shadow: 0 2px 4px rgba(0,0,0,0.02); height: 100%; }
        .stat-val { font-size: 2.5rem; font-weight: 700; color: #0f172a; line-height: 1; }
        .card-best { background: var(--primary-gradient); color: white; padding: 30px; border-radius: 16px; position: relative; overflow: hidden; box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.4); margin-bottom: 20px; }
        .trophy-icon { position: absolute; right: -20px; top: 10px; font-size: 8rem; opacity: 0.2; transform: rotate(15deg); }
        
        /* Tabela */
        .table-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 20px; }
        .table thead th { background: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; padding: 15px 20px; font-weight: 600; }
        .table tbody td { padding: 15px 20px; vertical-align: middle; }
        
        /* Badges */
        .rank-badge { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; background: #f1f5f9; }
        .rank-1 { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
        .badge-soft { padding: 5px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        /* Área de Filtros (Igual ao App.php) */
        .search-area { background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .filter-label { font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; }
        
        /* Paginação */
        .pagination { margin: 0; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand"><i class="bi bi-graph-up-arrow"></i>&nbsp; Analytics Pro</div>
    <div class="mt-3">
        <a href="dashboard_direto.php" class="nav-btn active"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a href="app.php?view=chamadas" class="nav-btn"><i class="bi bi-telephone"></i> Chamadas</a>
        <a href="app.php?view=pausas" class="nav-btn"><i class="bi bi-cup-hot"></i> Pausas</a>
        <a href="app.php?view=sessoes" class="nav-btn"><i class="bi bi-person-badge"></i> Sessões</a>
        
        <div class="mt-3 border-top border-secondary pt-2 opacity-50 small ms-3 mb-1">AO VIVO</div>
        <a href="realtime/painel.php" class="nav-btn text-warning"><i class="bi bi-activity"></i> Monitor Realtime</a>
    </div>
    
    <div class="mt-auto pt-4 border-top border-secondary mx-3 mb-3">
        <a href="logout.php" class="btn btn-outline-danger w-100 fw-bold btn-sm"><i class="bi bi-box-arrow-right me-2"></i> SAIR</a>
    </div>
</div>

<div class="main-content">
    <div class="page-wrapper">
        
        

        <form class="search-area d-flex align-items-end gap-3">
            <div>
                <div class="filter-label">Início</div>
                <input type="datetime-local" name="inicio" value="<?php echo $inicio_html; ?>" class="form-control form-control-sm">
            </div>
            <div>
                <div class="filter-label">Fim</div>
                <input type="datetime-local" name="fim" value="<?php echo $fim_html; ?>" class="form-control form-control-sm">
            </div>
            <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold"><i class="bi bi-filter"></i> Filtrar</button>
        </form>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card" style="border-color:#10b981;">
                    <div class="text-muted small fw-bold text-uppercase mb-2">SLA (< 60s)</div>
                    <div class="stat-val text-success"><?php echo $perc_sla; ?>%</div>
                    <div class="text-muted small mt-2"><i class="bi bi-check-circle"></i> <?php echo $kpis['sla_ok']; ?> atendidas no prazo</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-color:#ef4444;">
                    <div class="text-muted small fw-bold text-uppercase mb-2">Abandono Crítico</div>
                    <div class="stat-val text-danger"><?php echo $perc_abandono; ?>%</div>
                    <div class="text-muted small mt-2"><i class="bi bi-x-circle"></i> <?php echo $kpis['abandono_lento']; ?> desistiram > 60s</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-color:#f59e0b;">
                    <div class="text-muted small fw-bold text-uppercase mb-2">Chamadas Longas</div>
                    <div class="stat-val text-warning"><?php echo $perc_longas; ?>%</div>
                    <div class="text-muted small mt-2"><i class="bi bi-hourglass-bottom"></i> <?php echo $kpis['longas']; ?> chamadas > 20min</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-color:#3b82f6;">
                    <div class="text-muted small fw-bold text-uppercase mb-2">Volume Total</div>
                    <div class="stat-val text-primary"><?php echo $kpis['total']; ?></div>
                    <div class="text-muted small mt-2"><i class="bi bi-telephone-inbound"></i> Chamadas no período</div>
                </div>
            </div>
        </div>

        <div class="row align-items-start">
            <div class="col-md-4">
                <div class="card-best">
                    <i class="bi bi-trophy-fill trophy-icon"></i>
                    <div class="text-white-50 small fw-bold text-uppercase mb-2">Melhor Desempenho</div>
                    <h2 class="fw-bold mb-3 text-truncate"><?php echo $melhor_agente['nome']; ?></h2>
                    <div class="bg-white text-dark px-3 py-2 rounded-pill d-inline-flex align-items-center gap-2 fw-bold shadow-sm" style="width: fit-content;">
                        <i class="bi bi-check-circle-fill text-success"></i> <?php echo $melhor_agente['atendidas']; ?> Atendimentos
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="table-card">
                    <div class="p-3 border-bottom bg-light fw-bold text-secondary d-flex justify-content-between">
                        <span>RANKING DE AGENTES</span>
                        <span class="badge bg-white text-dark border"><?php echo count($agentes); ?> Agentes</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Agente</th>
                                    <th>Fila Principal</th>
                                    <th class="text-center">Atendidas</th>
                                    <th class="text-center">Rejeitadas</th>
                                    <th class="text-end pe-4">TMA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($agentes_paginados)): ?>
                                    <tr><td colspan="6" class="text-center py-5 text-muted">Sem dados para este período.</td></tr>
                                <?php endif; ?>

                                <?php 
                                $pos = $offset + 1;
                                foreach($agentes_paginados as $r): 
                                    $tma = $r['atendidas'] > 0 ? round($r['tempo_falado'] / $r['atendidas']) : 0;
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="rank-badge <?php echo $pos<=3?'rank-'.$pos:''; ?>"><?php echo $pos; ?></div>
                                    </td>
                                    <td class="fw-bold text-dark"><?php echo $r['nome']; ?></td>
                                    <td><span class="badge-soft"><?php echo $r['fila']; ?></span></td>
                                    <td class="text-center fw-bold text-primary fs-5"><?php echo $r['atendidas']; ?></td>
                                    <td class="text-center <?php echo $r['rejeitadas']>0?'text-danger fw-bold':'text-muted'; ?>"><?php echo $r['rejeitadas']; ?></td>
                                    <td class="text-end pe-4 font-monospace text-secondary fw-bold"><?php echo secToTime($tma); ?></td>
                                </tr>
                                <?php $pos++; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if($total_paginas > 1): ?>
                    <div class="p-3 border-top bg-light d-flex justify-content-end">
                        <nav>
                            <ul class="pagination pagination-sm m-0">
                                <li class="page-item <?php echo $pagina_atual <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo urlPagina($pagina_atual - 1); ?>">Anterior</a>
                                </li>
                                <?php for($i=1; $i<=$total_paginas; $i++): ?>
                                    <li class="page-item <?php echo $i == $pagina_atual ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo urlPagina($i); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $pagina_atual >= $total_paginas ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo urlPagina($pagina_atual + 1); ?>">Próximo</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>