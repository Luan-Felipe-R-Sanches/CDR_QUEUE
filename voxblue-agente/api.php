<?php
// Arquivo: api.php

// 1. Define cabeçalho JSON (Importante!)
header('Content-Type: application/json; charset=utf-8');

// 2. Inicia buffer para capturar erros indevidos do PHP
ob_start();

// 3. Carrega o banco (O db.php já cuida da sessão, NÃO inicie session_start aqui)
require_once 'db.php';
require_once 'classes/NetmaxxiCore.php';

// Limpa qualquer texto (avisos/erros) gerado pelos includes acima
ob_end_clean(); 

// 4. Verificação de Segurança
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

// 5. Carrega Configurações
try {
    $config = require 'config.php';
    
    // Tenta carregar filas do banco
    try {
        $stmt = $pdo->query("SELECT extension, descr FROM asterisk.queues_config ORDER BY extension ASC");
        $filasDb = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $filasDb[$row['extension']] = $row['descr'];
        }
        if (!empty($filasDb)) $config['queues'] = $filasDb;
    } catch (Exception $e) { /* Ignora erro de filas */ }

    // Busca agentes
    $stmt = $pdo->query("SELECT username, name FROM users");
    $agentesDb = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $num = preg_replace('/[^0-9]/', '', $r['username']);
        $agentesDb[$num] = $r['name'];
    }
    $config['agentes'] = $agentesDb;

    // 6. Executa a Lógica
    $core = new \Netmaxxi\NetmaxxiCore($config);
    $action = $_REQUEST['action'] ?? '';

    if ($action === 'monitor') {
        $data = $core->getQueueLiveStatus($config['queues']);
        echo json_encode(['status' => 'ok', 'data' => $data]);
    } 
    elseif ($action === 'control') {
        $type = $_POST['type'] ?? '';
        $queue = $_POST['queue'] ?? '';
        $interface = $_SESSION['user_tech'] . '/' . $_SESSION['user_name'];
        $res = $core->agentAction($type, $queue, $interface);
        echo json_encode(['status' => $res['success']?'ok':'error', 'msg' => $res['message']]);
    } 
    else {
        echo json_encode(['status' => 'error', 'msg' => 'Ação inválida']);
    }

} catch (Exception $e) {
    // Retorna erro formatado em JSON para o Frontend ler
    echo json_encode(['status' => 'error', 'msg' => 'Erro interno: ' . $e->getMessage()]);
}
?>