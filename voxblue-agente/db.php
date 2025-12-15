<?php
// Arquivo: db.php

// Só inicia a sessão se ela NÃO estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Usa require_once para garantir que o config não cause conflito
$config = require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset=utf8",
        $config['db']['user'],
        $config['db']['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Em produção, não mostre o erro completo na tela, apenas log
    die("Erro de conexão com o banco de dados.");
}

// Funções auxiliares mantidas
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}