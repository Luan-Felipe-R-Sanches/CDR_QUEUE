<?php
// config.php
return [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'netmaxxi_callcenter',
        'user' => 'root',      // Altere aqui
        'pass' => 'n3tware385br'   // Altere aqui
    ],
    'ami' => [
        'host' => '127.0.0.1',
        'port' => 5038,
        'username' => 'netmaxxi_ws',
        'secret' => 'uma_senha_forte_e_segura_2025'
    ],
    'queues' => [
        '9000' => 'Callcenter N1',
        '9010' => 'Suporte Técnico',
        '701'  => 'Teste Lab'
    ]
    // Removemos o array 'agentes' estático, pois virá do banco
];