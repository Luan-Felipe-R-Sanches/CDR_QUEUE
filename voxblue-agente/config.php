<?php
// Arquivo: config.php

// 1. Caminho absoluto do .env
$envPath = __DIR__ . '/.env';

// 2. Proteção: Se não achar o arquivo, para tudo e avisa.
if (!file_exists($envPath)) {
    die("ERRO CRÍTICO: O arquivo .env não foi encontrado em: $envPath");
}

// 3. Leitura linha por linha (Lógica idêntica ao teste que funcionou)
$env = [];
$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    $line = trim($line);
    // Pula comentários (#) ou linhas vazias
    if (empty($line) || $line[0] === '#') continue;

    // Quebra no sinal de igual
    $parts = explode('=', $line, 2);
    if (count($parts) === 2) {
        $key = trim($parts[0]);
        $val = trim($parts[1]);
        $val = trim($val, "\"'"); // Remove aspas extras se tiver
        
        $env[$key] = $val;
    }
}

// 4. Retorna a configuração usando os dados lidos
return [
    'db' => [
        'host'   => $env['DB_HOST'] ?? 'localhost',
        'dbname' => $env['DB_NAME'] ?? '',
        'user'   => $env['DB_USER'] ?? '',
        'pass'   => $env['DB_PASS'] ?? ''
    ],
    'ami' => [
        'host'     => $env['AMI_HOST'] ?? '127.0.0.1',
        'port'     => $env['AMI_PORT'] ?? 5038,
        'username' => $env['AMI_USER'] ?? '',
        'secret'   => $env['AMI_PASS'] ?? ''
    ],
    'queues' => []
];