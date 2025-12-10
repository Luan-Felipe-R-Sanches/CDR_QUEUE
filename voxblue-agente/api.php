<?php
// Arquivo: api.php
header('Content-Type: application/json');
require_once 'db.php'; // Sua conexão PDO já está aqui
require_once 'classes/NetmaxxiCore.php';

// Validar Sessão
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Não autorizado']);
    exit;
}

// Carrega config base (credenciais AMI)
$config = require 'config.php';

// --- MUDANÇA 1: CARREGAR FILAS DO BANCO ASTERISK ---
try {
    // A tabela queues_config geralmente fica no banco 'asterisk'
    // Como estamos logados como root (ou user com permissão), podemos acessar com "asterisk.tabela"
    $stmt = $pdo->query("SELECT extension, descr FROM asterisk.queues_config ORDER BY extension ASC");
    
    $filasDoBanco = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Cria o array [ '9000' => 'ATENDIMENTO N1' ]
        $filasDoBanco[$row['extension']] = $row['descr'];
    }
    
    // Sobrescreve a configuração manual
    if (!empty($filasDoBanco)) {
        $config['queues'] = $filasDoBanco;
    }

} catch (PDOException $e) {
    // Se der erro (ex: banco asterisk não acessível), mantém o do config.php ou avisa
    error_log("Erro ao ler filas do Asterisk: " . $e->getMessage());
}

// --- MUDANÇA 2: CARREGAR AGENTES DO BANCO NETMAXXI ---
// (Isso já fizemos antes, mantendo aqui)
$stmt = $pdo->query("SELECT username, name FROM users");
$agentesDb = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $num = preg_replace('/[^0-9]/', '', $row['username']);
    $agentesDb[$num] = $row['name'];
}
$config['agentes'] = $agentesDb;


// --- INICIA O CORE ---
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $core = new \Netmaxxi\NetmaxxiCore($config);

    if ($action === 'control') {
        $type = $_POST['type'] ?? '';
        $queue = $_POST['queue'] ?? '';
        $interface = $_SESSION['user_tech'] . '/' . $_SESSION['user_name'];
        
        if (empty($type) || empty($queue)) throw new \Exception("Dados incompletos.");

        $result = $core->agentAction($type, $queue, $interface);
        $response = ['status' => $result['success'] ? 'ok' : 'error', 'msg' => $result['message']];

    } elseif ($action === 'monitor') {
        // Agora ele monitora as filas dinâmicas do banco!
        $data = $core->getQueueLiveStatus($config['queues']);
        $response = ['status' => 'ok', 'data' => $data];
    }

} catch (\Exception $e) {
    $response = ['status' => 'error', 'msg' => $e->getMessage()];
}

echo json_encode($response);