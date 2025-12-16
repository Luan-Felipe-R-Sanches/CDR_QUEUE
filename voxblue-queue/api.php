<?php
// Arquivo: /var/www/html/relatorios/api.php

// Buffer para garantir que nenhum erro/espaço suje o JSON/CSV
ob_start(); 
error_reporting(0);
ini_set('display_errors', 0);
ini_set('memory_limit', '512M'); 
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

// --- FUNÇÕES AUXILIARES ---
function outputJSON($data) { 
    ob_end_clean(); 
    echo json_encode($data); 
    exit; 
}

function outputCSV($data, $filename, $headers) {
    ob_end_clean(); // Limpa qualquer lixo anterior
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para Excel abrir acentos corretamente
    fputcsv($out, $headers, ';'); // Cabeçalho
    
    foreach ($data as $row) {
        // Remove chaves textuais se houver, garante array indexado
        fputcsv($out, array_values($row), ';'); 
    }
    
    fclose($out); 
    exit;
}

function cleanAgent($ag) { 
    return trim(str_replace(['PJSIP/','SIP/','IAX2/','Agent/'], '', $ag)); 
}

try {
    $pdo = getConexao();

    // Parâmetros Globais
    $acao   = $_GET['acao'] ?? 'listar';
    $inicio = str_replace('T', ' ', $_GET['inicio'] ?? date('Y-m-d 00:00:00'));
    $fim    = str_replace('T', ' ', $_GET['fim'] ?? date('Y-m-d 23:59:59'));
    
    if (strlen($inicio) <= 16) $inicio .= ':00';
    if (strlen($fim) <= 16)    $fim    .= ':59';

    $busca  = trim($_GET['busca'] ?? '');
    $pagina = (int)($_GET['page'] ?? 1);
    $limit  = 50;
    
    // Config Exportação
    $is_export = (isset($_GET['export']) && $_GET['export'] == 'true');
    if ($is_export) { $limit = 100000; $pagina = 1; } // Limite alto para CSV
    $offset = ($pagina - 1) * $limit;
    
    // Parâmetros Base SQL
    $params = ['inicio' => $inicio, 'fim' => $fim];
    
    // --- LÓGICA DE BUSCA AVANÇADA (Corrigida) ---
    $where_busca = "";
    
    if (!empty($busca)) {
        $condicoes = [];
        
        // 1. Busca textual direta (Call ID, Fila ou Ramal Bruto)
        $condicoes[] = "agent LIKE :busca_txt";
        $condicoes[] = "queuename LIKE :busca_txt";
        if ($acao === 'listar') $condicoes[] = "callid LIKE :busca_txt";
        
        $params['busca_txt'] = "%$busca%";

        // 2. Busca pelo Nome Real do Agente (Mapeamento Reverso)
        // Se o usuário digita "Maria", procuramos qual o ID (PJSIP/1000) da Maria
        if (!empty($map_agentes)) {
            $i = 0;
            foreach ($map_agentes as $id => $nome) {
                // Se o nome do agente contém o texto da busca
                if (stripos($nome, $busca) !== false) {
                    $key = "busca_ag_$i";
                    $condicoes[] = "agent LIKE :$key"; // Adiciona OR agent LIKE ...
                    $params[$key] = "%$id%"; // Busca pelo ID (ex: 1000)
                    $i++;
                }
            }
        }
        
        $where_busca = " AND (" . implode(' OR ', $condicoes) . ") ";
    }

    // --- AÇÃO: DASHBOARD ---
    if ($acao === 'dashboard') {
        $sql = "SELECT agent, event, data1, data2, data3, queuename FROM queue_log 
                WHERE time BETWEEN :inicio AND :fim 
                AND event IN ('COMPLETECALLER','COMPLETEAGENT','CONNECT','ABANDON','EXITWITHTIMEOUT','PAUSE','RINGNOANSWER')";
        
        $stmt = $pdo->prepare($sql);
        // Bind manual porque dashboard não usa filtro de busca na query principal geralmente,
        // mas se quiser filtrar o dashboard por nome, teria que adaptar. 
        // Por padrão Dashboard mostra tudo do período.
        $stmt->execute(['inicio'=>$inicio, 'fim'=>$fim]);
        
        $kpis = ['total'=>0, 'atendidas'=>0, 'sla_ok'=>0, 'abandono_lento'=>0, 'longas'=>0];
        $agentes = [];

        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ag = cleanAgent($r['agent']);
            if ($ag == 'NONE' || empty($ag)) continue;

            if (!isset($agentes[$ag])) {
                $agentes[$ag] = [
                    'nome' => $map_agentes[$ag] ?? $ag,
                    'fila' => $map_filas[$r['queuename']] ?? $r['queuename'],
                    'atendidas' => 0, 'rejeitadas' => 0, 'tempo' => 0
                ];
            }

            if (in_array($r['event'], ['COMPLETECALLER','COMPLETEAGENT'])) {
                $kpis['total']++; $kpis['atendidas']++;
                $agentes[$ag]['atendidas']++;
                $tempo = (int)$r['data2'];
                $agentes[$ag]['tempo'] += $tempo;
                if ($tempo > 1200) $kpis['longas']++;
            }
            if ($r['event'] == 'CONNECT' && (int)$r['data1'] <= 60) $kpis['sla_ok']++;
            if (in_array($r['event'], ['ABANDON','EXITWITHTIMEOUT'])) {
                $kpis['total']++;
                if ((int)$r['data3'] > 60) $kpis['abandono_lento']++;
            }
            if ($r['event'] == 'RINGNOANSWER') $agentes[$ag]['rejeitadas']++;
        }

        $ranking = [];
        $melhor = ['nome'=>'-', 'qtd'=>0];
        foreach($agentes as $d) {
            if($d['atendidas'] > $melhor['qtd']) $melhor = ['nome'=>$d['nome'], 'qtd'=>$d['atendidas']];
            $tma = $d['atendidas'] > 0 ? round($d['tempo'] / $d['atendidas']) : 0;
            $ranking[] = [
                'agente' => $d['nome'], 'fila' => $d['fila'], 
                'atendidas' => $d['atendidas'], 'rejeitadas' => $d['rejeitadas'], 
                'tma' => gmdate("H:i:s", $tma)
            ];
        }
        usort($ranking, function($a,$b){ return $b['atendidas'] - $a['atendidas']; });

        $base_at = $kpis['atendidas'] ?: 1;
        $base_tot = $kpis['total'] ?: 1;
        
        outputJSON([
            'status' => 'success',
            'melhor_agente' => $melhor,
            'kpis' => [
                'sla_percent' => round(($kpis['sla_ok']/$base_at)*100, 1),
                'abandono_percent' => round(($kpis['abandono_lento']/$base_tot)*100, 1),
                'longas_percent' => round(($kpis['longas']/$base_at)*100, 1),
                'total_absoluto' => $kpis['atendidas']
            ],
            'ranking' => $ranking
        ]);
    }

    // --- AÇÃO: LISTAR CHAMADAS ---
    if ($acao === 'listar') {
        $filtroStatus = "";
        if (($_GET['status']??'') === 'ATENDIDA') $filtroStatus = " AND event IN ('COMPLETECALLER','COMPLETEAGENT') ";
        if (($_GET['status']??'') === 'ABANDONO') $filtroStatus = " AND event IN ('ABANDON','EXITWITHTIMEOUT') ";

        $sqlBase = "FROM queue_log WHERE time BETWEEN :inicio AND :fim 
                    AND event IN ('COMPLETECALLER','COMPLETEAGENT','ABANDON','EXITWITHTIMEOUT') 
                    $where_busca $filtroStatus";

        $sql = "SELECT time, callid, queuename, agent, event, data1, data2, data3 $sqlBase ORDER BY time DESC";
        if (!$is_export) $sql .= " LIMIT $limit OFFSET $offset";
        
        $stmt = $pdo->prepare($sql);
        foreach($params as $k=>$v) $stmt->bindValue(":$k",$v);
        $stmt->execute();
        
        $data = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ag = cleanAgent($row['agent']);
            $is_abandon = strpos($row['event'], 'ABANDON') !== false || strpos($row['event'], 'EXIT') !== false;
            
            // Dados para visualização e CSV
            $item = [
                'data_fmt' => date('d/m/Y H:i:s', strtotime($row['time'])),
                'nome_fila' => $map_filas[$row['queuename']] ?? $row['queuename'],
                'numero_cliente' => '-', 
                'nome_agente' => ($ag=='NONE')?'-':($map_agentes[$ag]??$ag),
                'status_txt' => $is_abandon ? 'ABANDONO' : 'ATENDIDA',
                'espera_fmt' => gmdate("H:i:s", (int)($is_abandon ? $row['data3'] : $row['data1'])),
                'duracao_fmt' => gmdate("H:i:s", (int)($is_abandon ? 0 : $row['data2'])),
            ];

            // Se for exportação, não adiciona links HTML
            if ($is_export) {
                $data[] = $item;
            } else {
                $item['link_gravacao'] = "player.php?id={$row['callid']}&date=".date('Y-m-d',strtotime($row['time']));
                $item['link_download'] = "player.php?id={$row['callid']}&date=".date('Y-m-d',strtotime($row['time']))."&download=true";
                $data[] = $item;
            }
        }

        if($is_export) outputCSV($data, 'chamadas.csv', ['Data','Fila','Cliente','Agente','Status','Espera','Duracao']);
        
        // Contagem Total
        $stmtC = $pdo->prepare("SELECT COUNT(*) $sqlBase");
        foreach($params as $k=>$v) $stmtC->bindValue(":$k",$v);
        $stmtC->execute();
        $total = (int)$stmtC->fetchColumn();
        
        outputJSON(['status'=>'success', 'data'=>$data, 'total'=>$total, 'pages'=>ceil($total/$limit)]);
    }

    // --- AÇÃO: PAUSAS ---
    if ($acao === 'pausas') {
        $sql = "SELECT time, agent, event, queuename FROM queue_log 
                WHERE time BETWEEN :inicio AND :fim AND (event LIKE 'PAUSE%' OR event LIKE 'UNPAUSE%') 
                $where_busca ORDER BY agent, time ASC";
        
        $stmt = $pdo->prepare($sql);
        foreach($params as $k=>$v) $stmt->bindValue(":$k",$v);
        $stmt->execute();
        
        $res = []; $temp = [];
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ag = cleanAgent($r['agent']);
            if (empty($ag) || $ag == 'NONE') continue;
            
            if (strpos($r['event'], 'PAUSE') === 0) {
                $temp[$ag] = ['ini' => $r['time'], 'fila' => $r['queuename']];
            } elseif (strpos($r['event'], 'UNPAUSE') === 0 && isset($temp[$ag])) {
                $ini = strtotime($temp[$ag]['ini']); $fim = strtotime($r['time']);
                $item = [
                    'agente' => $map_agentes[$ag] ?? $ag,
                    'fila' => $map_filas[$temp[$ag]['fila']] ?? $temp[$ag]['fila'],
                    'inicio' => date('d/m H:i', $ini),
                    'fim' => date('d/m H:i', $fim),
                    'duracao' => gmdate("H:i:s", $fim - $ini),
                ];
                // Campo 'ts' é usado só para ordenar na tela, não vai pro CSV
                if (!$is_export) $item['ts'] = $ini;
                
                $res[] = $item;
                unset($temp[$ag]);
            }
        }
        
        if($is_export) outputCSV($res, 'pausas.csv', ['Agente','Fila','Inicio','Fim','Duracao']);
        
        usort($res, function($a,$b){ return $b['ts'] - $a['ts']; });
        $total = count($res);
        $paged = array_slice($res, $offset, $limit);
        outputJSON(['status'=>'success', 'data'=>$paged, 'total'=>$total, 'pages'=>ceil($total/$limit)]);
    }

    // --- AÇÃO: SESSÕES ---
    if ($acao === 'sessoes') {
        $sql = "SELECT time, agent, event, queuename FROM queue_log 
                WHERE time BETWEEN :inicio AND :fim AND event IN ('ADDMEMBER','REMOVEMEMBER','AGENTLOGIN','AGENTLOGOFF') 
                $where_busca ORDER BY agent, time ASC";
        
        $stmt = $pdo->prepare($sql);
        foreach($params as $k=>$v) $stmt->bindValue(":$k",$v);
        $stmt->execute();

        $res = []; $temp = [];
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ag = cleanAgent($r['agent']);
            if (empty($ag) || $ag == 'NONE') continue;

            if (in_array($r['event'], ['ADDMEMBER','AGENTLOGIN'])) {
                $temp[$ag] = ['ini' => $r['time'], 'fila' => $r['queuename']];
            } elseif (in_array($r['event'], ['REMOVEMEMBER','AGENTLOGOFF']) && isset($temp[$ag])) {
                $ini = strtotime($temp[$ag]['ini']); $fim = strtotime($r['time']);
                $item = [
                    'agente' => $map_agentes[$ag] ?? $ag,
                    'fila' => $map_filas[$temp[$ag]['fila']] ?? $temp[$ag]['fila'],
                    'entrada' => date('d/m H:i', $ini),
                    'saida' => date('d/m H:i', $fim),
                    'tempo_total' => gmdate("H:i:s", $fim - $ini),
                ];
                if (!$is_export) $item['ts'] = $ini;
                
                $res[] = $item;
                unset($temp[$ag]);
            }
        }

        if($is_export) outputCSV($res, 'sessoes.csv', ['Agente','Fila','Entrada','Saida','Tempo']);
        
        usort($res, function($a,$b){ return $b['ts'] - $a['ts']; });
        $total = count($res);
        $paged = array_slice($res, $offset, $limit);
        outputJSON(['status'=>'success', 'data'=>$paged, 'total'=>$total, 'pages'=>ceil($total/$limit)]);
    }

} catch (Exception $e) { outputJSON(['status'=>'error', 'msg'=>$e->getMessage()]); }
?>