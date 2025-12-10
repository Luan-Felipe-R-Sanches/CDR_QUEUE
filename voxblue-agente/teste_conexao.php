<?php
// /var/www/html/netmaxxi_panel/teste_conexao.php
require_once 'vendor/autoload.php';
$config = require 'config.php';

echo "1. Tentando conectar em {$config['ami']['host']}:{$config['ami']['port']}...\n";

try {
    $pami = new \PAMI\Client\Impl\ClientImpl($config['ami']);
    $pami->open();
    echo "2. SUCESSO! Conexão estabelecida.\n";
    
    // Teste simples: Ping
    $response = $pami->send(new \PAMI\Message\Action\PingAction());
    echo "3. Resposta do Asterisk: " . $response->getMessage() . "\n";
    
    $pami->close();
} catch (Exception $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Verifique: \n";
    echo "- Se o usuário 'netmaxxi_ws' existe no /etc/asterisk/manager_custom.conf\n";
    echo "- Se a senha está igual\n";
    echo "- Se o SELinux não está bloqueando o Apache\n";
}