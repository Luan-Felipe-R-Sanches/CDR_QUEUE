<?php
// db.php
session_start();
$config = require 'config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset=utf8",
        $config['db']['user'],
        $config['db']['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão com Banco: " . $e->getMessage());
}

// Função para verificar se está logado
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Função para verificar se é Admin
function checkAdmin() {
    checkAuth();
    if ($_SESSION['user_role'] !== 'admin') {
        die("Acesso negado. Apenas administradores.");
    }
}