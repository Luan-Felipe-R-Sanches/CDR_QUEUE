<?php
// Arquivo: realtime/api_control.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once '../config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// --- 1. GET USERS ---
if ($action === 'get_users') {
    try {
        $pdo = getConexao();
        $stmt = $pdo->query("SELECT username, name, tech FROM netmaxxi_callcenter.users ORDER BY name ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

// --- ENVIO AMI ---
function sendAMI($commands) {
    $socket = @fsockopen(AMI_HOST, AMI_PORT, $errno, $errstr, 2);
    if (!$socket) return "Erro Conexão: $errstr";
    
    fwrite($socket, "Action: Login\r\nUsername: ".AMI_USER."\r\nSecret: ".AMI_PASS."\r\n\r\n");
    fread($socket, 1024); // Consome login

    fwrite($socket, $commands);
    
    // Lê resposta
    $output = "";
    $start = microtime(true);
    while (!feof($socket) && (microtime(true) - $start) < 0.2) {
        $output .= fgets($socket, 1024);
    }
    
    fwrite($socket, "Action: Logoff\r\n\r\n");
    fclose($socket);
    
    // Retorna erro se houver
    if (stripos($output, 'Response: Error') !== false) {
        preg_match('/Message: (.*)/', $output, $matches);
        return "Asterisk: " . ($matches[1] ?? 'Erro desconhecido');
    }
    
    return "Sucesso";
}

// --- 2. ADICIONAR ---
if ($action === 'add_member') {
    $queue = trim($_POST['queue']);
    $interface = trim($_POST['interface']);
    $cmd  = "Action: QueueAdd\r\nQueue: $queue\r\nInterface: $interface\r\nPaused: false\r\nPenalty: 0\r\n\r\n";
    echo json_encode(['status' => sendAMI($cmd)]);
    exit;
}

// --- 3. REMOVER ---
if ($action === 'remove_member') {
    $queue = trim($_POST['queue']);
    $interface = trim($_POST['interface']);
    $cmd  = "Action: QueueRemove\r\nQueue: $queue\r\nInterface: $interface\r\n\r\n";
    echo json_encode(['status' => sendAMI($cmd)]);
    exit;
}

// --- 4. SPY ---
if ($action === 'spy') {
    $supervisor = trim($_POST['supervisor']);
    $target = trim($_POST['target']);
    $mode = trim($_POST['mode']);
    $tech = $_POST['tech'] ?? 'PJSIP';
    
    if (empty($supervisor)) { echo json_encode(['status' => 'Faltou seu ramal']); exit; }

    $cmd  = "Action: Originate\r\n";
    $cmd .= "Channel: $tech/$supervisor\r\n";
    $cmd .= "Application: ChanSpy\r\n";
    $cmd .= "Data: all,e($target)$mode\r\n";
    $cmd .= "CallerID: Monitor $target\r\n";
    $cmd .= "Async: true\r\n\r\n";
    
    echo json_encode(['status' => sendAMI($cmd)]);
    exit;
}

// --- 5. PAUSA (NOVO) ---
if ($action === 'pause_member') {
    $queue = trim($_POST['queue']);
    $interface = trim($_POST['interface']);
    $paused = trim($_POST['paused']); // "true" ou "false"
    
    $cmd  = "Action: QueuePause\r\n";
    $cmd .= "Queue: $queue\r\n";
    $cmd .= "Interface: $interface\r\n";
    $cmd .= "Paused: $paused\r\n\r\n";
    
    echo json_encode(['status' => sendAMI($cmd)]);
    exit;
}
?>