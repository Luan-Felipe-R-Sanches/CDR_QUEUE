<?php
// setup_db.php
$host = 'localhost';
$user = 'root'; // Seu usuario do banco mysql
$pass = 'n3tware385br'; // Sua senha do mysql

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS netmaxxi_callcenter");
    $pdo->exec("USE netmaxxi_callcenter");
    
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(100) NOT NULL,
        tech VARCHAR(10) DEFAULT 'PJSIP',
        role ENUM('admin', 'agent') DEFAULT 'agent'
    )";
    $pdo->exec($sql);

    // Cria Admin padrão (Senha: admin123)
    $passHash = password_hash('n3tware385br', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, name, role) VALUES ('admin', ?, 'Super Admin', 'admin')");
    $stmt->execute([$passHash]);

    echo "Banco de dados e Tabela criados! Usuário: admin / Senha: admin123";

} catch (PDOException $e) {
    die("Erro DB: " . $e->getMessage());
}