<?php
// Arquivo: realtime/backend.php
error_reporting(0);
header('Content-Type: application/json');
require_once '../config.php';
require_once 'Lib/AsteriskService.php';

// Sistema de Cache (2 segundos para não travar o banco/Asterisk)
$cacheFile = sys_get_temp_dir() . '/ami_queue_cache.json';
$cacheTime = 2; 

// Se cache existe e é recente, usa ele
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    readfile($cacheFile);
    exit;
}

try {
    $ami = new AsteriskService();
    // Pede status das filas ao Asterisk
    $raw = $ami->sendCommand("Action: QueueStatus\r\n\r\n");
    
    // Processa os dados cruzando com o Banco de Dados
    $data = parseAmiOutput($raw);
    
    // Salva cache e exibe
    file_put_contents($cacheFile, json_encode($data));
    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

// --- Funções de Processamento ---

function parseAmiOutput($raw) {
    $lines = explode("\n", $raw);
    $queues = [];
    $current = [];
    
    // 1. Busca nomes das Filas (Asterisk)
    $dbQueueNames = getQueueNamesFromDB();
    
    // 2. Busca nomes dos Agentes (Netmaxxi) - AQUI ESTÁ A CORREÇÃO
    $dbAgentNames = getAgentNamesFromDB();

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) {
            if (!empty($current)) processEvent($current, $queues, $dbQueueNames, $dbAgentNames);
            $current = [];
        } else {
            $parts = explode(': ', $line, 2);
            if (count($parts) == 2) $current[$parts[0]] = $parts[1];
        }
    }
    // Processa último bloco
    if (!empty($current)) processEvent($current, $queues, $dbQueueNames, $dbAgentNames);

    return ['filas' => array_values($queues)];
}

function processEvent($evt, &$queues, $queueMap, $agentMap) {
    if (!isset($evt['Event'])) return;
    $type = $evt['Event'];
    $qid = $evt['Queue'] ?? '';

    if (empty($qid) || $qid == 'default') return;

    // Cria a fila se não existir
    if (!isset($queues[$qid])) {
        $queues[$qid] = [
            'numero' => $qid,
            'nome' => $queueMap[$qid] ?? "Fila $qid",
            'atendidas' => 0, 'abandonadas' => 0, 'logados' => 0, 'espera' => 0,
            'membros' => [], 'chamadas' => []
        ];
    }

    // Estatísticas da Fila
    if ($type == 'QueueParams') {
        $queues[$qid]['logados'] = (int)($evt['LoggedIn']??0);
        $queues[$qid]['espera'] = (int)($evt['Callers']??0);
        $queues[$qid]['atendidas'] = (int)($evt['Completed']??0);
        $queues[$qid]['abandonadas'] = (int)($evt['Abandoned']??0);
    } 
    // Membros (Agentes)
    elseif ($type == 'QueueMember') {
        $iface = $evt['Name']; // Vem como PJSIP/9002
        
        // Extrai apenas o número do ramal (remove PJSIP/)
        // Ex: PJSIP/9002 vira 9002
        $ramal = preg_replace('/^[A-Za-z0-9]+\//', '', $iface); 
        
        // Verifica se tem nome no banco, senão usa o ramal mesmo
        $nomeBonito = isset($agentMap[$ramal]) ? $agentMap[$ramal] : $ramal;

        $queues[$qid]['membros'][] = [
            'nome' => $nomeBonito, // Agora mostra "Camila", "Luan", etc.
            'interface' => $iface,
            'ramal' => $ramal,
            'status' => (int)$evt['Status'],
            'paused' => (isset($evt['Paused']) && $evt['Paused'] == '1'),
            'dynamic' => stripos($evt['Membership']??'', 'dynamic') !== false
        ];
    } 
    // Chamadas na Espera
    elseif ($type == 'QueueEntry') {
        $queues[$qid]['chamadas'][] = [
            'numero' => $evt['CallerIDNum'] ?? 'Desc.',
            'wait' => ($evt['Wait'] ?? '0') . 's'
        ];
    }
}

// Busca Nomes das Filas
function getQueueNamesFromDB() {
    try {
        $pdo = getConexao();
        // Ajuste aqui se sua tabela de filas tiver outro nome
        $stmt = $pdo->query("SELECT extension, descr FROM asterisk.queues_config");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) { return []; }
}

// NOVA FUNÇÃO: Busca Nomes dos Agentes
function getAgentNamesFromDB() {
    try {
        $pdo = getConexao();
        // FETCH_KEY_PAIR cria um array onde a chave é 'username' e valor é 'name'
        // Ex: ['9002' => 'Camila', '9034' => 'Luan Felipe']
        $sql = "SELECT username, name FROM netmaxxi_callcenter.users";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) { return []; }
}
?>