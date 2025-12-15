<?php
// backend.php
error_reporting(0);
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// Tenta conectar no servidor Python com timeout curto (2 segundos)
$context = stream_context_create(['http' => ['timeout' => 2]]);
$json = @file_get_contents("http://127.0.0.1:5000/stats", false, $context);

if ($json === FALSE) {
    // --- MODO DE EMERGÊNCIA ---
    // Se o Python falhar, o PHP gera um card falso para testar o Frontend
    $fakeData = [
        'status' => 'error',
        'message' => 'Python Offline - Dados Simulados pelo PHP',
        'troncos' => [
            'total' => 1,
            'lista' => [
                [
                    'nome' => 'TRONCO-TESTE-PHP',
                    'canal' => 'SIP/Teste-001',
                    'status' => 'Up',
                    'ramal' => '100',
                    'destino' => '4002-8922',
                    'inicio' => date('Y-m-d\TH:i:s.000O', time() - 65) // 1m 05s atrás
                ]
            ]
        ]
    ];
    echo json_encode($fakeData);
} else {
    // Se o Python respondeu, repassa os dados
    echo $json;
}
?>