<?php
// Arquivo: /var/www/html/relatorios/realtime/backend.php
error_reporting(0);
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config.php';

// Fallback de credenciais
if (!defined('AMI_USER')) define('AMI_USER', 'php_dashboard');
if (!defined('AMI_PASS')) define('AMI_PASS', 'senha_segura_ami');
if (!defined('AMI_HOST')) define('AMI_HOST', '127.0.0.1');
if (!defined('AMI_PORT')) define('AMI_PORT', 5038);

function getAMI() {
    $socket = @fsockopen(AMI_HOST, AMI_PORT, $errno, $errstr, 2);
    if (!$socket) return ['error' => "Offline"];

    fputs($socket, "Action: Login\r\nUsername: ".AMI_USER."\r\nSecret: ".AMI_PASS."\r\n\r\n");
    fputs($socket, "Action: QueueStatus\r\n\r\n");
    fputs($socket, "Action: Logoff\r\n\r\n");

    $buffer = "";
    $start = microtime(true);
    stream_set_timeout($socket, 1); 
    while (!feof($socket) && (microtime(true) - $start) < 2) {
        $buffer .= fgets($socket, 4096);
    }
    fclose($socket);
    return parseAMI($buffer);
}

function parseAMI($raw) {
    global $map_agentes, $map_filas;
    
    $raw = str_replace("\r\n", "\n", $raw);
    $blocks = explode("\n\n", $raw); 
    $queues = [];
    
    // 1. Identifica Filas
    foreach ($blocks as $block) {
        if (strpos($block, "Event: QueueParams") !== false) {
            $data = parseBlock($block);
            $qid = $data['Queue'];
            if ($qid === 'default') continue;

            $queues[$qid] = [
                'id' => $qid,
                'nome' => isset($map_filas[$qid]) ? $map_filas[$qid] : "Fila $qid",
                'espera' => (int)($data['Calls'] ?? 0),
                'abandonadas' => (int)($data['Abandoned'] ?? 0),
                'atendidas' => (int)($data['Completed'] ?? 0),
                'membros' => [],
                'chamadas' => []
            ];
        }
    }

    // 2. Preenche Dados
    foreach ($blocks as $block) {
        if (empty(trim($block))) continue;
        $data = parseBlock($block);
        
        if (!isset($data['Queue']) || !isset($queues[$data['Queue']])) continue;
        $qid = $data['Queue'];

        // AGENTES
        if (isset($data['Event']) && $data['Event'] == 'QueueMember') {
            $rawName = $data['Name']; 
            $cleanID = preg_replace('/[^0-9]/', '', $rawName);
            $nomeDisplay = isset($map_agentes[$cleanID]) ? $map_agentes[$cleanID] : $data['Name'];
            
            $st = (int)$data['Status'];
            $paused = (isset($data['Paused']) && $data['Paused'] == '1');
            
            $queues[$qid]['membros'][] = [
                'nome' => $nomeDisplay,
                'status' => $st,
                'paused' => $paused,
                'calls' => (int)($data['CallsTaken'] ?? 0)
            ];
        }

        // CHAMADAS NA ESPERA (TRONCO E DID)
        if (isset($data['Event']) && $data['Event'] == 'QueueEntry') {
            // Numero do Cliente
            $num = $data['CallerIDNum'] ?? $data['CallerID'] ?? 'Desconhecido';
            $name = $data['CallerIDName'] ?? '';
            $displayCaller = ($name && $name != 'unknown' && $name != $num) ? "$name ($num)" : $num;

            // Extração do Tronco via CANAL (Ex: PJSIP/TroncoVivo-00001)
            $channel = $data['Channel'] ?? '';
            $trunk = 'Desconhecido';
            
            if (!empty($channel)) {
                // Pega o que está entre a barra / e o traço -
                // Ex: PJSIP/Vivo-assd -> Vivo
                $parts = explode('/', $channel);
                if (isset($parts[1])) {
                    $trunkParts = explode('-', $parts[1]);
                    $trunk = $trunkParts[0];
                }
            }

            $queues[$qid]['chamadas'][] = [
                'numero' => $displayCaller,
                'tronco' => $trunk, // NOVO CAMPO
                'wait' => gmdate("H:i:s", (int)($data['Wait'] ?? 0))
            ];
        }
    }

    return array_values($queues);
}

function parseBlock($block) {
    $data = [];
    $lines = explode("\n", trim($block));
    foreach ($lines as $line) {
        $parts = explode(":", trim($line), 2);
        if (count($parts) == 2) {
            $data[trim($parts[0])] = trim($parts[1]);
        }
    }
    return $data;
}

echo json_encode(getAMI());
?>