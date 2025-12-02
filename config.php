<?php
// Arquivo: /var/www/html/relatorios/config.php

// 1. Credenciais do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'asterisk');
define('DB_USER', 'root');
define('DB_PASS', ''); // 


// 2. Fuso Horário (Ajuste conforme sua região)
date_default_timezone_set('America/Campo_Grande');

// 3. Mapeamento de Agentes (ID Técnico -> Nome Real)
// Dica: Adicione aqui os números que aparecem no relatório
$map_agentes = [
    '1050' => 'João Silva',
    '9000' => 'Suporte N1',
    '9002' => 'Maria Souza',
    '9003' => 'Pedro Santos',
    '9005' => 'Ana Costa',
    '9006' => 'Carlos TI',
    '9018' => 'Recepcionista'
];

// 4. Mapeamento de Filas (ID da Fila -> Nome Amigável)
$map_filas = [
    '9000' => 'Suporte Técnico',
    '9010' => 'Vendas',
    '9023' => 'Financeiro'
];

// 5. Função de Conexão (Blindada)
if (!function_exists('getConexao')) {
    function getConexao() {
        try {
            // DSN com Charset UTF8 explícito
            $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8";
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            
            // Configurações de Erro e Timezone do Banco
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("SET NAMES utf8");
            $pdo->exec("SET time_zone = '-04:00';"); // Sincroniza fuso do banco
            
            return $pdo;
        } catch (PDOException $e) {
            // Se der erro, mata o processo e avisa (o api.php vai pegar isso)
            die(json_encode(['status' => 'error', 'msg' => 'Erro Conexão DB: ' . $e->getMessage()]));
        }
    }
}