<?php
// Conexão com Banco de Dados (Ajuste a senha/user conforme seu /etc/asterisk/res_config_mysql.conf)
$servername = "localhost";
$username = "root";
$password = "n3tware385br"; // Geralmente fica em /etc/issabel.conf

try {
    $conn = new PDO("mysql:host=$servername;dbname=asterisk", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Limpa tabela asterisk.queue_members (A mais comum para painéis)
    $stmt = $conn->prepare("DELETE FROM queue_members WHERE queue_name = :fila");
    $stmt->execute(['fila' => '800']);
    
    // 2. Se usar Issabel CallCenter, limpa a tabela agent_queue também
    // (Descomente se necessário e se o usuário tiver permissão no banco call_center)
    /*
    $conn->exec("USE call_center");
    $stmt2 = $conn->prepare("DELETE FROM agent_queue WHERE queue = :fila");
    $stmt2->execute(['fila' => '800']);
    */

    echo "Limpeza do Banco de Dados realizada para a fila 800.";
    
} catch(PDOException $e) {
    echo "Erro ao limpar banco: " . $e->getMessage();
}
?>