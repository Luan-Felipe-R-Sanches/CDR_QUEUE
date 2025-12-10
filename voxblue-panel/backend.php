<?php
// Arquivo: voxblue-solutions/voxblue-panel/backend.php
error_reporting(0);
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// Tenta pegar do Python local
$json = @file_get_contents("http://127.0.0.1:5000/stats");

if ($json === FALSE) {
    echo json_encode([
        'error' => 'Servidor Python Offline. Rode "python3 server.py" no terminal.',
        'ramais' => [], 'troncos' => [], 'filas' => []
    ]);
} else {
    echo $json;
}
?>