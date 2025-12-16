<?php
// Arquivo: /var/www/html/config_global.php
session_start();

// Configurações do Banco
define('DB_HOST', '127.0.0.1'); // Ajuste se necessário
define('DB_USER', 'root');
define('DB_PASS', 'n3tware385br');
define('DB_NAME', 'netmaxxi_callcenter');

// Fuso Horário
date_default_timezone_set('America/Sao_Paulo');

function getGlobalConnection() {
    try {
        $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Erro de conexão global: " . $e->getMessage());
    }
}

// Função para verificar se está logado
function checkGlobalAuth() {
    if (!isset($_SESSION['vox_user']) || empty($_SESSION['vox_user'])) {
        header('Location: /login.php');
        exit;
    }
}
?>