<?php
// Arquivo: /var/www/html/relatorios/api.php

ob_start();
error_reporting(0);
ini_set('display_errors', 0);
ini_set('memory_limit', '512M'); 
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

function outputJSON($data) { ob_end_clean(); echo json_encode($data); exit; }
function outputCSV($data, $filename, $headers) {
    ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, $headers, ';');
    foreach ($data as $row) { fputcsv($output, $row, ';'); }
    fclose($output); exit;
}
function utf8ize($d) {
    if (is_array($d)) { foreach ($d as $k => $v) { $d[$k] = utf8ize($v); } } 
    else if (is_string($d)) { return mb_convert_encoding($d, 'UTF-8', 'UTF-8'); }
    return $d;
}

try {
    $pdo = getConexao();

    $acao   = $_GET['acao'] ?? 'listar';
    $inicio = str_replace('T', ' ', $_GET['inicio'] ?? date('Y-m-d 00:00:00'));
    $fim    = str_replace('T', ' ', $_GET['fim'] ?? date('Y-m-d 23:59:59'));
    
    if (strlen($inicio) <= 16) $inicio .= ':00';
    if (strlen($fim) <= 16)    $fim    .= ':59';

    $busca  = $_GET['busca'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $pagina = (int)($_GET['page'] ?? 1);
    $limit  = 50;
    
    $is_export = (isset($_GET['export']) && $_GET['export'] == 'true');
    if ($is_export) { $limit = 100000; $pagina = 1; }
    
    $offset = ($pagina - 1) * $limit;
    $params = ['inicio' => $inicio, 'fim' => $fim];

    $where_busca = "";
    if (!empty($busca)) {
        $condicoes = [];
        $condicoes[] = "main.agent LIKE :busca";
        
        if ($acao === 'listar') {
            $condicoes[] = "main.callid LIKE :busca";
            $condicoes[] = "sub.data2 LIKE :busca"; 
        }

        $params['busca'] = "%$busca%";
        
        if (isset($map_agentes) && is_array($map_agentes)) {
            $i = 0;
            foreach ($map_agentes as $id => $nome) {
                if (stripos(strval($nome), $busca) !== false) {
                    $k = "id_$i"; $condicoes[] = "main.agent LIKE :$k"; $params[$k] = "%$id%"; $i++;
                }
            }
        }
        $where_busca = " AND (" . implode(' OR ', $condicoes) . ") ";
    }

    $where_status = "";
    if ($status_filter === 'ATENDIDA') $where_status = " AND main.event IN ('COMPLETECALLER', 'COMPLETEAGENT', 'CONNECT') ";
    if ($status_filter === 'ABANDONO') $where_status = " AND main.event IN ('ABANDON', 'EXITWITHTIMEOUT', 'EXITWITHKEY') ";

    // DASHBOARD
    if ($acao === 'dashboard') {
        $sql = "SELECT agent, event, data1, data2, data3, queuename FROM queue_log main WHERE time BETWEEN :inicio AND :fim AND event IN ('COMPLETECALLER', 'COMPLETEAGENT', 'CONNECT', 'ABANDON', 'EXITWITHTIMEOUT', 'PAUSE', 'RINGNOANSWER')";
        $stmt = $pdo->prepare($sql); $stmt->execute(['inicio'=>$inicio, 'fim'=>$fim]);
        
        $kpis = ['total_geral'=>0,'atendidas'=>0,'sla_ok'=>0,'abandono_lento'=>0,'longas'=>0]; $agentes_stats=[];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ag = trim(str_replace(['PJSIP/','SIP/','IAX2/'], '', $r['agent'])); if ($ag=='NONE'||empty($ag)) continue;
            if (!isset($agentes_stats[$ag])) $agentes_stats[$ag] = ['nome'=>$map_agentes[$ag]??$ag, 'fila'=>$map_filas[$r['queuename']]??$r['queuename'], 'atendidas'=>0, 'pausas'=>0, 'rejeitadas'=>0, 'tempo'=>0];
            
            if (in_array($r['event'],['COMPLETECALLER','COMPLETEAGENT'])) { $kpis['total_geral']++; $kpis['atendidas']++; $agentes_stats[$ag]['atendidas']++; $agentes_stats[$ag]['tempo']+=(int)$r['data2']; if((int)$r['data2']>1200)$kpis['longas']++; }
            if ($r['event']=='CONNECT' && (int)$r['data1']<=60) $kpis['sla_ok']++;
            if (in_array($r['event'],['ABANDON','EXITWITHTIMEOUT'])) { $kpis['total_geral']++; if((int)$r['data3']>60)$kpis['abandono_lento']++; }
            if ($r['event']=='PAUSE') $agentes_stats[$ag]['pausas']++;
            if ($r['event']=='RINGNOANSWER') $agentes_stats[$ag]['rejeitadas']++;
        }
        
        $ranking=[]; $melhor=['nome'=>'-','qtd'=>0];
        foreach($agentes_stats as $s) { 
            if($s['atendidas']>$melhor['qtd']) $melhor=['nome'=>$s['nome'],'qtd'=>$s['atendidas']]; 
            $ranking[]=['agente'=>$s['nome'],'fila'=>$s['fila'],'atendidas'=>$s['atendidas'],'pausas'=>$s['pausas'],'rejeitadas'=>$s['rejeitadas'],'tma'=>gmdate("H:i:s",$s['atendidas']>0?round($s['tempo']/$s['atendidas']):0)]; 
        }
        usort($ranking, function($a,$b){return $b['atendidas']-$a['atendidas'];});
        $base_at = $kpis['atendidas']>0?$kpis['atendidas']:1; $base_tot = $kpis['total_geral']>0?$kpis['total_geral']:1;
        outputJSON(utf8ize(['status'=>'success','melhor_agente'=>$melhor,'kpis'=>['sla_percent'=>round(($kpis['sla_ok']/$base_at)*100,1),'abandono_percent'=>round(($kpis['abandono_lento']/$base_tot)*100,1),'longas_percent'=>round(($kpis['longas']/$base_at)*100,1),'total_absoluto'=>$kpis['atendidas']],'ranking'=>$ranking]));
    }

    // LISTAR (Chamadas)
    if ($acao === 'listar') {
        $sqlData = "SELECT main.time, main.callid, main.queuename, main.agent, main.event, main.data1, main.data2, main.data3, sub.data2 as numero_cliente
            FROM queue_log main LEFT JOIN queue_log sub ON (main.callid = sub.callid AND sub.event = 'ENTERQUEUE')
            WHERE main.time BETWEEN :inicio AND :fim 
            AND main.event IN ('COMPLETECALLER', 'COMPLETEAGENT', 'ABANDON', 'EXITWITHTIMEOUT', 'EXITWITHKEY')
            $where_busca $where_status ORDER BY main.time DESC";
        
        if (!$is_export) $sqlData .= " LIMIT $limit OFFSET $offset";

        $stmt = $pdo->prepare($sqlData);
        foreach ($params as $k => $v) $stmt->bindValue(":$k", $v);
        $stmt->execute();
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $finalData = [];
        foreach ($dados as $row) {
            $ag = trim(str_replace(['PJSIP/','SIP/','IAX2/'], '', $row['agent']));
            $item = [
                'data_fmt' => date('d/m/Y H:i:s', strtotime($row['time'])),
                'nome_fila' => $map_filas[$row['queuename']] ?? $row['queuename'],
                'numero_cliente' => $row['numero_cliente'] ?? '-',
                'nome_agente' => ($ag == 'NONE') ? '-' : ($map_agentes[$ag] ?? $ag),
                'status_txt' => (strpos($row['event'], 'ABANDON') !== false || strpos($row['event'], 'EXIT') !== false) ? 'ABANDONO' : 'ATENDIDA',
                'espera_fmt' => gmdate("H:i:s", (int)((strpos($row['event'], 'ABANDON') !== false) ? $row['data3'] : $row['data1'])),
                'duracao_fmt' => gmdate("H:i:s", (int)((strpos($row['event'], 'ABANDON') !== false) ? 0 : $row['data2'])),
                // NOVOS LINKS DE PLAYER E DOWNLOAD
                'link_gravacao' => "player.php?id=" . $row['callid'] . "&date=" . date('Y-m-d', strtotime($row['time'])),
                'link_download' => "player.php?id=" . $row['callid'] . "&date=" . date('Y-m-d', strtotime($row['time'])) . "&download=true",
                'callid' => $row['callid']
            ];
            
            if ($is_export) $finalData[] = [$item['data_fmt'], $item['nome_fila'], $item['numero_cliente'], $item['nome_agente'], $item['status_txt'], $item['espera_fmt'], $item['duracao_fmt'], $item['callid']];
            else $finalData[] = $item;
        }

        if ($is_export) {
            outputCSV($finalData, 'chamadas.csv', ['Data', 'Fila', 'Cliente', 'Agente', 'Status', 'Espera', 'Duracao', 'ID']);
        } else {
            // CORREÇÃO DA PAGINAÇÃO AQUI
            $sqlCount = "SELECT COUNT(*) FROM queue_log main LEFT JOIN queue_log sub ON (main.callid = sub.callid AND sub.event = 'ENTERQUEUE')
                WHERE main.time BETWEEN :inicio AND :fim AND main.event IN ('COMPLETECALLER', 'COMPLETEAGENT', 'ABANDON', 'EXITWITHTIMEOUT', 'EXITWITHKEY') $where_busca $where_status";
            $stmtC = $pdo->prepare($sqlCount);
            foreach ($params as $k => $v) $stmtC->bindValue(":$k", $v);
            $stmtC->execute();
            
            // Pega o total UMA VEZ e guarda na variável
            $total = (int)$stmtC->fetchColumn(); 
            
            outputJSON(utf8ize(['status' => 'success', 'data' => $finalData, 'total' => $total, 'pages' => ceil($total / $limit)]));
        }
    }

    // PAUSAS
    if ($acao === 'pausas') {
        $sql = "SELECT time, agent, event, queuename FROM queue_log main WHERE time BETWEEN :inicio AND :fim AND (event LIKE 'PAUSE%' OR event LIKE 'UNPAUSE%') $where_busca ORDER BY agent, time ASC";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) if($k!=='busca') $stmt->bindValue(":$k", $v);
        if(!empty($busca)) $stmt->bindValue(':busca', "%$busca%");
        $stmt->execute();
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $pausas_completa = []; $temp = [];
        foreach($raw as $r) {
            $ag = trim(str_replace(['PJSIP/','SIP/','IAX2/'], '', $r['agent']));
            if ($ag == 'NONE' || empty($ag)) continue;
            if(strpos($r['event'], 'PAUSE') === 0 && strpos($r['event'], 'UNPAUSE') === false) {
                $temp[$ag] = ['inicio' => $r['time'], 'fila_id' => $r['queuename']];
            } elseif(strpos($r['event'], 'UNPAUSE') === 0 && isset($temp[$ag])) {
                $ini = strtotime($temp[$ag]['inicio']); $fim = strtotime($r['time']);
                $fid = $temp[$ag]['fila_id'];
                $nomeFila = (isset($map_filas[$fid])) ? $map_filas[$fid] : $fid;
                if(empty($nomeFila) || $nomeFila == 'NONE') $nomeFila = 'Geral';
                
                $item = ['agente' => isset($map_agentes[$ag])?$map_agentes[$ag]:$ag, 'inicio' => date('d/m/Y H:i:s', $ini), 'fim' => date('d/m/Y H:i:s', $fim), 'duracao' => gmdate("H:i:s", $fim - $ini), 'fila' => $nomeFila, 'ts' => $ini];
                if ($is_export) $pausas_completa[] = [$item['agente'], $item['fila'], $item['inicio'], $item['fim'], $item['duracao']];
                else $pausas_completa[] = $item;
                unset($temp[$ag]);
            }
        }

        if ($is_export) outputCSV($pausas_completa, 'pausas.csv', ['Agente', 'Fila', 'Inicio', 'Fim', 'Duracao']);
        else {
            usort($pausas_completa, function($a, $b) { return $b['ts'] - $a['ts']; });
            $total = count($pausas_completa);
            outputJSON(utf8ize(['status' => 'success', 'data' => array_slice($pausas_completa, $offset, $limit), 'total' => $total, 'pages' => ceil($total / $limit)]));
        }
    }

    // SESSOES
    if ($acao === 'sessoes') {
        $sql = "SELECT time, agent, event, queuename FROM queue_log main WHERE time BETWEEN :inicio AND :fim AND event IN ('ADDMEMBER', 'REMOVEMEMBER', 'AGENTLOGIN', 'AGENTLOGOFF') $where_busca ORDER BY agent, time ASC";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) if($k!=='busca') $stmt->bindValue(":$k", $v);
        if(!empty($busca)) $stmt->bindValue(':busca', "%$busca%");
        $stmt->execute();
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $sessoes = []; $temp = [];
        foreach($raw as $r) {
            $ag = trim(str_replace(['PJSIP/','SIP/','Agent/','IAX2/'], '', $r['agent']));
            if ($ag == 'NONE' || empty($ag)) continue;
            if(in_array($r['event'], ['ADDMEMBER', 'AGENTLOGIN'])) {
                $temp[$ag] = ['inicio' => $r['time'], 'fila' => $r['queuename']];
            } elseif(in_array($r['event'], ['REMOVEMEMBER', 'AGENTLOGOFF']) && isset($temp[$ag])) {
                $ini = strtotime($temp[$ag]['inicio']); $fim = strtotime($r['time']);
                $nome = isset($map_agentes[$ag])?$map_agentes[$ag]:$ag;
                $fila = isset($map_filas[$temp[$ag]['fila']])?$map_filas[$temp[$ag]['fila']]:$temp[$ag]['fila'];
                $item = ['agente' => $nome, 'fila' => $fila, 'entrada' => date('d/m/Y H:i:s', $ini), 'saida' => date('d/m/Y H:i:s', $fim), 'tempo_total' => gmdate("H:i:s", $fim - $ini), 'ts' => $ini];
                if ($is_export) $sessoes[] = [$item['agente'], $item['fila'], $item['entrada'], $item['saida'], $item['tempo_total']];
                else $sessoes[] = $item;
                unset($temp[$ag]);
            }
        }

        if ($is_export) outputCSV($sessoes, 'sessoes.csv', ['Agente', 'Fila', 'Entrada', 'Saida', 'Tempo Logado']);
        else {
            usort($sessoes, function($a, $b) { return $b['ts'] - $a['ts']; });
            $total = count($sessoes);
            outputJSON(utf8ize(['status' => 'success', 'data' => array_slice($sessoes, $offset, $limit), 'total' => $total, 'pages' => ceil($total / $limit)]));
        }
    }

} catch (Exception $e) { outputJSON(['status' => 'error', 'msg' => $e->getMessage()]); }
catch (Throwable $e) { outputJSON(['status' => 'error', 'msg' => $e->getMessage()]); }
?>