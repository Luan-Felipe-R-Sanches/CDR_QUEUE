<?php
// Arquivo: realtime/backend.php
error_reporting(0); // Silencia erros visuais
header('Content-Type: application/json');

// Ajuste o caminho do config se necessário
require_once '../config.php';

// Fallback de segurança se config falhar
if (!defined('AMI_HOST')) define('AMI_HOST', '127.0.0.1');
if (!defined('AMI_PORT')) define('AMI_PORT', 5038);
if (!defined('AMI_USER')) define('AMI_USER', 'php_dashboard');
if (!defined('AMI_PASS')) define('AMI_PASS', 'senha_segura_ami');

// Função Principal
function get_queue_data() {
    $socket = @fsockopen(AMI_HOST, AMI_PORT, $errno, $errstr, 3);
    if (!$socket) return ['error' => "AMI Offline: $errstr"];

    // 1. Login
    fputs($socket, "Action: Login\r\nUsername: ".AMI_USER."\r\nSecret: ".AMI_PASS."\r\n\r\n");
    
    // Consome resposta do Login
    $w = 0; 
    while (!feof($socket) && $w++ < 10) { 
        $line = fgets($socket, 1024);
        if (trim($line) == "") break; // Fim do cabeçalho de login
        if (stripos($line, "Authentication failed") !== false) return ['error' => "Erro de Senha AMI"];
    }

    // 2. Pede Status das Filas
    fputs($socket, "Action: QueueStatus\r\n\r\n");

    // 3. Lê até acabar a lista (QueueStatusComplete)
    $buffer = "";
    $start = microtime(true);
    
    while (!feof($socket)) {
        $line = fgets($socket, 8192);
        $buffer .= $line;
        
        // Palavra mágica que indica o fim da lista
        if (stripos($line, "QueueStatusComplete") !== false) break;
        
        // Timeout de segurança (3s)
        if ((microtime(true) - $start) > 3) break;
    }

    fputs($socket, "Action: Logoff\r\n\r\n");
    fclose($socket);

    return parse_ami($buffer);
}

// Parser Inteligente
function parse_ami($raw) {
    $lines = explode("\n", $raw);
    $queues = [];
    $current_evt = [];

    // Agrupa linhas em blocos de eventos
    $events = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) {
            if (!empty($current_evt)) {
                $events[] = $current_evt;
                $current_evt = [];
            }
        } else {
            $parts = explode(': ', $line, 2);
            if (count($parts) == 2) {
                $current_evt[$parts[0]] = $parts[1];
            }
        }
    }

    // Processa os eventos
    foreach ($events as $evt) {
        if (!isset($evt['Event'])) continue;
        $type = $evt['Event'];
        $qid = $evt['Queue'] ?? '';

        if ($qid == 'default' || empty($qid)) continue;

        // Cria a fila se não existir
        if (!isset($queues[$qid])) {
            $queues[$qid] = [
                'numero' => $qid,
                'nome' => "Fila $qid",
                'logados' => 0, 'espera' => 0, 'atendidas' => 0, 'abandonadas' => 0,
                'membros' => [], 'chamadas' => []
            ];
        }

        // Estatísticas
        if ($type == 'QueueParams') {
            $queues[$qid]['logados'] = (int)($evt['LoggedIn'] ?? 0);
            $queues[$qid]['espera'] = (int)($evt['Callers'] ?? 0);
            $queues[$qid]['atendidas'] = (int)($evt['Completed'] ?? 0);
            $queues[$qid]['abandonadas'] = (int)($evt['Abandoned'] ?? 0);
        }
        // Membros (Agentes)
        elseif ($type == 'QueueMember') {
            $interface = $evt['Name']; // PJSIP/1001
            $ramal = preg_replace('/[^0-9]/', '', $interface);
            $nome = $evt['Name']; // Pode ser melhorado buscando do banco

            $queues[$qid]['membros'][] = [
                'nome' => $nome,
                'interface' => $interface,
                'ramal' => $ramal,
                'status' => (int)$evt['Status'],
                'paused' => (isset($evt['Paused']) && $evt['Paused'] == '1'),
                'dynamic' => (stripos($evt['Membership'] ?? '', 'dynamic') !== false)
            ];
        }
        // Chamadas em espera
        elseif ($type == 'QueueEntry') {
            $queues[$qid]['chamadas'][] = [
                'numero' => $evt['CallerIDNum'] ?? 'Desc.',
                'wait' => ($evt['Wait'] ?? '0') . 's'
            ];
        }
    }

    // Tenta pegar nomes bonitos do banco (Opcional)
    try {
        global $pdo; 
        if(!function_exists('getConexao')) require_once '../config.php';
        $pdo = getConexao();
        
        // Nomes das Filas
        $stmt = $pdo->query("SELECT extension, descr FROM asterisk.queues_config");
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if(isset($queues[$r['extension']])) {
                $queues[$r['extension']]['nome'] = $r['descr'];
            }
        }
        
        // Nomes dos Agentes (Opcional - Melhora visual)
        // Se quiser ver nomes reais, descomente abaixo
        /*
        $stmt2 = $pdo->query("SELECT id, description FROM asterisk.devices");
        $mapaNomes = [];
        while($r = $stmt2->fetch(PDO::FETCH_ASSOC)) $mapaNomes[$r['id']] = $r['description'];
        
        foreach($queues as &$q) {
            foreach($q['membros'] as &$m) {
                if(isset($mapaNomes[$m['ramal']])) $m['nome'] = $mapaNomes[$m['ramal']];
            }
        }
        */

    } catch(Exception $e) {}

    return ['filas' => array_values($queues)];
}

echo json_encode(get_queue_data());
?>