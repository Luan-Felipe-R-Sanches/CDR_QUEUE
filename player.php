<?php
// Arquivo: /var/www/html/relatorios/player.php

$id = $_GET['id'] ?? '';
$date = $_GET['date'] ?? '';

if (empty($id))
    die("ID invalido.");

// 1. Tenta descobrir o caminho do arquivo
// O Issabel organiza em /var/spool/asterisk/monitor/ANO/MES/DIA/
$baseDir = "/var/spool/asterisk/monitor/";
$targetFile = "";

// Se temos a data, vamos direto na pasta (Muito mais rápido)
if (!empty($date)) {
    $time = strtotime($date);
    $path = $baseDir . date('Y', $time) . "/" . date('m', $time) . "/" . date('d', $time) . "/";
    // Busca arquivos que contenham o ID no nome (ex: q-9000-...-ID.wav)
    $files = glob($path . "*" . $id . ".*");
    if ($files && isset($files[0]))
        $targetFile = $files[0];
}

// Se não achou ou não tem data, busca recursiva (Lento, fallback)
if (empty($targetFile)) {
    // Procura apenas nos últimos 2 dias para não travar o servidor
    $cmd = "find $baseDir -mtime -2 -name '*$id.*' | head -n 1";
    $targetFile = trim(shell_exec($cmd));
}

// 2. Entrega o Áudio
if (!empty($targetFile) && file_exists($targetFile)) {
    $extension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $mime = ($extension == 'gsm') ? 'audio/x-gsm' : 'audio/wav';

    // Headers para tocar no navegador (Streaming)
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($targetFile));
    header('Content-Disposition: inline; filename="' . basename($targetFile) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Accept-Ranges: bytes');

    readfile($targetFile);
    exit;
}

http_response_code(404);
echo "Arquivo de audio nao encontrado no servidor.";
?>