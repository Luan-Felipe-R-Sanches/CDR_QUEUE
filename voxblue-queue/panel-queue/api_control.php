<?php
// Arquivo: realtime/api_control.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../config.php';

$action = $_REQUEST['action'] ?? '';

// --- 1. GET USERS (Mantido igual) ---
if ($action === 'get_users') {
    try {
        $pdo = getConexao();
        $sql = "SELECT username, name, tech FROM netmaxxi_callcenter.users ORDER BY name ASC";
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

// --- 2. COMANDOS AMI ---
require_once 'Lib/AsteriskService.php';

try {
    $ami = new AsteriskService();
    $resp = "Ação desconhecida";

    // CORREÇÃO: Usando $_POST direto com trim para não quebrar caracteres como "/" ou "@"
    // Isso é vital para agentes fixos (ex: Local/1000@from-queue)
    $queue = trim($_POST['queue'] ?? '');
    $iface = trim($_POST['interface'] ?? '');

    if ($action === 'add_member') {
        if(!$queue || !$iface) throw new Exception("Faltou Fila ou Interface");
        $cmd = "Action: QueueAdd\r\nQueue: $queue\r\nInterface: $iface\r\nPaused: false\r\nPenalty: 0\r\n\r\n";
        $resp = $ami->sendCommand($cmd);
    } 
    elseif ($action === 'remove_member') {
        $cmd = "Action: QueueRemove\r\nQueue: $queue\r\nInterface: $iface\r\n\r\n";
        $resp = $ami->sendCommand($cmd);
    }
    elseif ($action === 'pause_member') {
        $paused = ($_POST['paused'] === 'true') ? 'true' : 'false';
        $cmd = "Action: QueuePause\r\nQueue: $queue\r\nInterface: $iface\r\nPaused: $paused\r\n\r\n";
        $resp = $ami->sendCommand($cmd);
    }
    elseif ($action === 'spy') {
        $sup = trim($_POST['supervisor'] ?? '');
        $tgt = trim($_POST['target'] ?? '');
        $mode = ($_POST['mode'] ?? '') === 'w' ? 'w' : ''; 
        
        if (!$sup) throw new Exception("Seu ramal não foi definido");

        $cmd  = "Action: Originate\r\nChannel: PJSIP/$sup\r\nApplication: ChanSpy\r\n";
        $cmd .= "Data: all,e($tgt)$mode\r\nCallerID: Monitor $tgt\r\nAsync: true\r\n\r\n";
        $resp = $ami->sendCommand($cmd);
    }

    // Tratamento de resposta mais limpo
    if (stripos($resp, 'Error') !== false || stripos($resp, 'failed') !== false) {
        // Se der erro, mostra o que tentou enviar para facilitar o debug
        $cleanResp = strip_tags($resp); // Remove sujeira HTML se houver
        echo json_encode([
            'status' => "Falha AMI", 
            'detalhe' => "Asterisk recusou a interface '$iface' na fila '$queue'. Resposta: $cleanResp"
        ]);
    } else {
        echo json_encode(['status' => 'Sucesso']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'Erro Interno', 'msg' => $e->getMessage()]);
}
?>