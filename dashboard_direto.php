<?php
// Arquivo: /var/www/html/relatorios/dashboard_direto.php

require_once 'config.php';

// 1. Definição de Datas (Padrão: Hoje)
$data_hoje = date('Y-m-d');
$inicio = isset($_GET['inicio']) ? $_GET['inicio'] : $data_hoje . ' 00:00:00';
$fim    = isset($_GET['fim'])    ? $_GET['fim']    : $data_hoje . ' 23:59:59';

// Garante formato correto para o input HTML
$inicio_html = date('Y-m-d\TH:i', strtotime($inicio));
$fim_html    = date('Y-m-d\TH:i', strtotime($fim));

// 2. Processamento de Dados (A Mágica acontece aqui)
$kpis = [
    'total' => 0,
    'atendidas' => 0,
    'sla_ok' => 0,      // < 60s
    'abandono' => 0,
    'abandono_lento' => 0, // > 60s
    'longas' => 0       // > 20min
];

$agentes = [];
$ranking = [];

try {
    $pdo = getConexao();
    
    // Query BRUTA e LEVE (Traz tudo e o PHP se vira)
    $sql = "SELECT time, callid, queuename, agent, event, data1, data2, data3 
            FROM queue_log 
            WHERE time BETWEEN :inicio AND :fim 
            AND event IN ('CONNECT','COMPLETECALLER','COMPLETEAGENT','ABANDON','EXITWITHTIMEOUT','PAUSE','RINGNOANSWER')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':inicio' => $inicio, ':fim' => $fim]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $evt = $row['event'];
        $d1 = (int)$row['data1']; // Wait ou Pausa
        $d2 = (int)$row['data2']; // Talk
        $d3 = (int)$row['data3']; // Wait no abandono
        
        // Normaliza Agente
        $ag_cod = trim(str_replace(['PJSIP/','SIP/','IAX2/'], '', $row['agent']));
        if (empty($ag_cod) || $ag_cod == 'NONE') continue;
        
        // Inicializa Agente se não existir
        if (!isset($agentes[$ag_cod])) {
            $agentes[$ag_cod] = [
                'nome' => isset($map_agentes[$ag_cod]) ? $map_agentes[$ag_cod] : $ag_cod,
                'fila' => isset($map_filas[$row['queuename']]) ? $map_filas[$row['queuename']] : $row['queuename'],
                'atendidas' => 0,
                'pausas' => 0,
                'rejeitadas' => 0,
                'tempo_falado' => 0
            ];
        }

        // --- Lógica de KPIs ---
        
        // Atendidas
        if ($evt == 'COMPLETECALLER' || $evt == 'COMPLETEAGENT') {
            $kpis['total']++;
            $kpis['atendidas']++;
            $agentes[$ag_cod]['atendidas']++;
            $agentes[$ag_cod]['tempo_falado'] += $d2;
            
            // Chamada Longa (> 20 min = 1200s)
            if ($d2 > 1200) $kpis['longas']++;
        }
        
        // SLA (Atendida e Espera <= 60s)
        if ($evt == 'CONNECT') {
            if ($d1 <= 60) $kpis['sla_ok']++;
        }

        // Abandonos
        if ($evt == 'ABANDON' || $evt == 'EXITWITHTIMEOUT') {
            $kpis['abandono']++;
            // Abandono Lento (> 60s)
            if ($d3 > 60) $kpis['abandono_lento']++;
        }

        // Performance Agente
        if ($evt == 'PAUSE') $agentes[$ag_cod]['pausas']++;
        if ($evt == 'RINGNOANSWER') $agentes[$ag_cod]['rejeitadas']++;
    }

    // --- Cálculos Finais ---
    
    // Porcentagens (Proteção div zero)
    $base_total = $kpis['total'] > 0 ? $kpis['total'] : 1; // Para longas e SLA usa-se o total de atendidas geralmente, mas aqui usaremos geral
    $base_atendidas = $kpis['atendidas'] > 0 ? $kpis['atendidas'] : 1;
    
    $perc_sla = round(($kpis['sla_ok'] / $base_atendidas) * 100, 1);
    $perc_abandono = round(($kpis['abandono_lento'] / ($kpis['total'] + $kpis['abandono'] > 0 ? $kpis['total'] + $kpis['abandono'] : 1)) * 100, 1);
    $perc_longas = round(($kpis['longas'] / $base_atendidas) * 100, 1);

    // Ordenação do Ranking
    usort($agentes, function($a, $b) {
        return $b['atendidas'] - $a['atendidas'];
    });
    
    // Melhor Agente
    $melhor_agente = isset($agentes[0]) ? $agentes[0] : ['nome' => '-', 'atendidas' => 0];

} catch (Exception $e) {
    die("Erro Crítico SQL: " . $e->getMessage());
}

// Helper de tempo
function secToTime($seconds) {
    return gmdate("H:i:s", $seconds);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Direto</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        .sidebar { width: 260px; background: #0f172a; min-height: 100vh; position: fixed; color: white; }
        .main { margin-left: 260px; padding: 30px; }
        .card-stat { background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); height: 100%; }
        .val-big { font-size: 2.5rem; font-weight: 700; color: #0f172a; line-height: 1; }
        .label-stat { color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 5px; }
        .nav-link { color: #94a3b8; padding: 12px 20px; display: block; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
        .rank-1 { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .card-hero { background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); color: white; border-radius: 16px; padding: 30px; position: relative; overflow: hidden; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="p-4 fw-bold fs-5 border-bottom border-secondary"><i class="bi bi-graph-up"></i> Analytics Pro</div>
    <div class="py-3">
        <a href="dashboard_direto.php" class="nav-link active"><i class="bi bi-grid-1x2 me-2"></i> Dashboard</a>
        <a href="app.php?view=chamadas" class="nav-link"><i class="bi bi-telephone me-2"></i> Chamadas</a>
        <a href="app.php?view=pausas" class="nav-link"><i class="bi bi-cup-hot me-2"></i> Pausas</a>
        <a href="app.php?view=sessoes" class="nav-link"><i class="bi bi-person-badge me-2"></i> Sessões</a>
    </div>
</div>

<div class="main">
    <form class="bg-white p-3 rounded border mb-4 d-flex align-items-end gap-3 shadow-sm">
        <div>
            <label class="small text-muted fw-bold">Início</label>
            <input type="datetime-local" name="inicio" value="<?php echo $inicio_html; ?>" class="form-control form-control-sm">
        </div>
        <div>
            <label class="small text-muted fw-bold">Fim</label>
            <input type="datetime-local" name="fim" value="<?php echo $fim_html; ?>" class="form-control form-control-sm">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-filter"></i> Filtrar</button>
    </form>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card-stat" style="border-left: 4px solid #10b981;">
                <div class="label-stat">Nível de Serviço (SLA)</div>
                <div class="val-big text-success"><?php echo $perc_sla; ?>%</div>
                <small class="text-muted"><?php echo $kpis['sla_ok']; ?> de <?php echo $kpis['atendidas']; ?> atendidas rápido</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-stat" style="border-left: 4px solid #ef4444;">
                <div class="label-stat">Abandono Crítico</div>
                <div class="val-big text-danger"><?php echo $perc_abandono; ?>%</div>
                <small class="text-muted"><?php echo $kpis['abandono_lento']; ?> desistiram > 60s</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-stat" style="border-left: 4px solid #f59e0b;">
                <div class="label-stat">Chamadas Longas</div>
                <div class="val-big text-warning"><?php echo $perc_longas; ?>%</div>
                <small class="text-muted"><?php echo $kpis['longas']; ?> chamadas > 20min</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-stat" style="border-left: 4px solid #3b82f6;">
                <div class="label-stat">Total Atendidas</div>
                <div class="val-big text-primary"><?php echo $kpis['atendidas']; ?></div>
                <small class="text-muted">Volume total no período</small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card-hero h-100 d-flex flex-column justify-content-center">
                <i class="bi bi-trophy-fill position-absolute opacity-25" style="font-size: 8rem; right: -20px; top: 10px;"></i>
                <div class="text-white-50 small fw-bold text-uppercase mb-2">Campeão do Período</div>
                <h2 class="fw-bold mb-3"><?php echo $melhor_agente['nome']; ?></h2>
                <div class="bg-white text-primary px-3 py-2 rounded-pill d-inline-flex align-items-center gap-2 fw-bold shadow-sm" style="width: fit-content;">
                    <i class="bi bi-check-circle-fill"></i> <?php echo $melhor_agente['atendidas']; ?> Atendimentos
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card-stat p-0 overflow-hidden">
                <div class="p-3 border-bottom bg-light fw-bold text-secondary">Ranking de Performance</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-white">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Agente</th>
                                <th>Fila</th>
                                <th class="text-center">Atendidas</th>
                                <th class="text-center">Pausas</th>
                                <th class="text-center">Rejeitadas</th>
                                <th class="text-end pe-4">TMA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($agentes)): ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">Sem dados para este período.</td></tr>
                            <?php endif; ?>

                            <?php 
                            $pos = 1;
                            foreach($agentes as $r): 
                                $tma = $r['atendidas'] > 0 ? round($r['tempo_falado'] / $r['atendidas']) : 0;
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <?php if($pos == 1): ?><div class="rank-1">1</div>
                                    <?php else: ?><span class="text-muted fw-bold ps-2"><?php echo $pos; ?>º</span><?php endif; ?>
                                </td>
                                <td class="fw-bold text-dark"><?php echo $r['nome']; ?></td>
                                <td><span class="badge bg-light text-secondary border"><?php echo $r['fila']; ?></span></td>
                                <td class="text-center fw-bold fs-5 text-primary"><?php echo $r['atendidas']; ?></td>
                                <td class="text-center text-muted"><?php echo $r['pausas']; ?></td>
                                <td class="text-center <?php echo $r['rejeitadas']>0?'text-danger fw-bold':'text-muted'; ?>"><?php echo $r['rejeitadas']; ?></td>
                                <td class="text-end pe-4 font-monospace text-secondary"><?php echo secToTime($tma); ?></td>
                            </tr>
                            <?php $pos++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>