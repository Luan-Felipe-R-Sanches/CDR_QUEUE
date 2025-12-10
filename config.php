<?php
// Arquivo: config.php

// 1. Credenciais do Banco de Dados
// Usamos 'root' para ter acesso tanto ao banco 'asterisk' quanto ao 'netmaxxi_callcenter'
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'n3tware385br'); // Coloque sua senha do root aqui se houver
define('DB_NAME', 'asterisk'); 

// 2. Fuso Horário
date_default_timezone_set('America/Campo_Grande');

// 3. Definição da Função de Conexão (Global)
if (!function_exists('getConexao')) {
    function getConexao() {
        try {
            $dsn = "mysql:host=".DB_HOST.";charset=utf8";
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("SET NAMES utf8");
            
            // Seleciona o banco padrão para as queries do sistema (queue_log)
            $pdo->exec("USE " . DB_NAME);
            
            return $pdo;
        } catch (PDOException $e) {
            die(json_encode(['status' => 'error', 'msg' => 'Erro Crítico DB: ' . $e->getMessage()]));
        }
    }
}

// --- CARREGAMENTO DINÂMICO DE NOMES (MÁGICA AQUI) ---

// Inicializa arrays vazios para evitar erro se o banco falhar
$map_agentes = [];
$map_filas = [];

try {
    // Cria uma conexão temporária para carregar os mapas
    $pdoMap = getConexao();

    // A. CARREGAR AGENTES (Do banco netmaxxi_callcenter)
    // Tenta ler a tabela users que criamos no projeto anterior
    try {
        $stmtAg = $pdoMap->query("SELECT username, name FROM netmaxxi_callcenter.users");
        while ($row = $stmtAg->fetch(PDO::FETCH_ASSOC)) {
            // Limpa o ramal (remove PJSIP, espaços, etc) deixando só números
            $ramal = preg_replace('/[^0-9]/', '', $row['username']);
            $map_agentes[$ramal] = $row['name'];
        }
    } catch (Exception $e) {
        // Se a tabela não existir, ignora silenciosamente
    }

    // B. CARREGAR FILAS (Do banco asterisk)
    // Lê a configuração nativa do Issabel
    try {
        $stmtQ = $pdoMap->query("SELECT extension, descr FROM asterisk.queues_config");
        while ($row = $stmtQ->fetch(PDO::FETCH_ASSOC)) {
            $num = $row['extension'];
            $nome = trim($row['descr']);
            // Se a descrição estiver vazia, usa "Fila XXX"
            $map_filas[$num] = empty($nome) ? "Fila $num" : $nome;
        }
    } catch (Exception $e) {
        // Ignora erros
    }

    // Fecha conexão temporária
    $pdoMap = null;

} catch (Exception $e) {
    // Se der erro geral, segue com arrays vazios
}

// Configurações do AMI (Para o Realtime)
if (!defined('AMI_HOST')) define('AMI_HOST', '127.0.0.1');
if (!defined('AMI_PORT')) define('AMI_PORT', 5038);
if (!defined('AMI_USER')) define('AMI_USER', 'php_dashboard'); // Verifique seu /etc/asterisk/manager.conf
if (!defined('AMI_PASS')) define('AMI_PASS', 'senha_segura_ami');
?>