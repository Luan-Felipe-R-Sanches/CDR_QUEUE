<?php
// Arquivo: config_global.php
// ATENÇÃO: Não deixe espaço em branco antes do <?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações do Banco (VERIFIQUE SE ESTÃO CERTAS)
define('DB_HOST', '127.0.0.1'); 
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
        // Se der erro aqui, vai aparecer na tela agora
        die("Erro fatal de conexão com o banco: " . $e->getMessage());
    }
}

// Função de verificação de login
function checkGlobalAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['vox_user']) || empty($_SESSION['vox_user'])) {
        header('Location: login.php');
        exit;
    }
}
?>