<?php
// Arquivo: voxblue-agente/db.php

// 1. Inicia Sessão se não existir
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Configurações de Banco de Dados
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'n3tware385br');
define('DB_NAME', 'netmaxxi_callcenter');

// 3. Conexão PDO
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Se falhar, mostra erro amigável (evita tela branca)
    die("<h3>Erro Crítico</h3><p>Não foi possível conectar ao banco de dados: " . $e->getMessage() . "</p>");
}

// 4. Função de Verificação de Admin
function checkAdmin() {
    // Verifica se está logado
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    
    // Verifica se é admin
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        // Se for agente tentando acessar admin, manda para o painel de agente
        header('Location: index.php');
        exit;
    }
}

// 5. Função de Verificação de Usuário (Qualquer um logado)
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
?>