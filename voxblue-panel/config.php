<?php
// Arquivo: voxblue-solutions/voxblue-panel/config.php

// 1. BANCO DE DADOS
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'n3tware385br'); 
define('DB_NAME', 'netmaxxi_callcenter');

// 2. AMI (Asterisk Manager)
define('AMI_HOST', '127.0.0.1');
define('AMI_PORT', 5038);
define('AMI_USER', 'php_dashboard');
define('AMI_PASS', 'senha_segura_ami');

// 3. ARI (Asterisk REST Interface) - NOVO!
define('ARI_HOST', '127.0.0.1');
define('ARI_PORT', 8088);
define('ARI_USER', 'admin');        // Seu usuário ARI
define('ARI_PASS', 'n3tware385br'); // Sua senha ARI

// Conexão PDO
if (!function_exists('getConexao')) {
    function getConexao() {
        try {
            $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            // Retorna erro JSON se falhar
            die(json_encode(['error' => 'Erro DB: ' . $e->getMessage()]));
        }
    }
}
?>