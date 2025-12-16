<?php
// Arquivo: config.php

// --- 1. Carregador de .env (Leve e Nativo) ---
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Ignora comentários
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Carrega o .env da mesma pasta
loadEnv(__DIR__ . '/.env');

// --- 2. Definição de Constantes ---
// Usa getenv() para pegar do .env ou usa um valor default seguro
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: '');

define('AMI_HOST', getenv('AMI_HOST') ?: '127.0.0.1');
define('AMI_PORT', getenv('AMI_PORT') ?: 5038);
define('AMI_USER', getenv('AMI_USER') ?: '');
define('AMI_PASS', getenv('AMI_PASS') ?: '');

// Configuração de Fuso Horário
date_default_timezone_set(getenv('TIMEZONE') ?: 'America/Sao_Paulo');

// --- 3. Conexão com Banco de Dados ---
if (!function_exists('getConexao')) {
    function getConexao() {
        try {
            // Conecta sem selecionar banco primeiro para garantir suporte a utf8
            $dsn = "mysql:host=" . DB_HOST . ";charset=utf8";
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Seleciona o banco definido
            $pdo->exec("USE " . DB_NAME);
            
            return $pdo;
        } catch (PDOException $e) {
            // Retorna JSON erro se for uma requisição API, senão morre texto simples
            $msg = 'Erro Conexão DB: ' . $e->getMessage();
            if (strpos($_SERVER['SCRIPT_NAME'], 'api.php') !== false) {
                die(json_encode(['status' => 'error', 'msg' => $msg]));
            }
            die($msg);
        }
    }
}

// --- 4. Carregamento Dinâmico de Mapas (Agentes/Filas) ---
$map_agentes = [];
$map_filas = [];

try {
    $pdoMap = getConexao();

    // Carrega Agentes (netmaxxi_callcenter)
    try {
        $stmtAg = $pdoMap->query("SELECT username, name FROM netmaxxi_callcenter.users");
        while ($row = $stmtAg->fetch(PDO::FETCH_ASSOC)) {
            $ramal = preg_replace('/[^0-9]/', '', $row['username']);
            $map_agentes[$ramal] = $row['name'];
        }
    } catch (Exception $e) {}

    // Carrega Filas (asterisk)
    try {
        $stmtQ = $pdoMap->query("SELECT extension, descr FROM asterisk.queues_config");
        while ($row = $stmtQ->fetch(PDO::FETCH_ASSOC)) {
            $num = $row['extension'];
            $nome = trim($row['descr']);
            $map_filas[$num] = empty($nome) ? "Fila $num" : $nome;
        }
    } catch (Exception $e) {}

    $pdoMap = null;
} catch (Exception $e) {}
?>