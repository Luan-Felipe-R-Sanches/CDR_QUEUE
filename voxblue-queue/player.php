<?php
// Arquivo: /var/www/html/relatorios/player.php

$id = $_GET['id'] ?? '';
$date = $_GET['date'] ?? '';
$download = isset($_GET['download']) && $_GET['download'] == 'true';

if (empty($id)) die("ID invalido.");

// 1. Tenta descobrir o caminho do arquivo
$baseDir = "/var/spool/asterisk/monitor/";
$targetFile = "";

// Busca rápida
if (!empty($date)) {
    $time = strtotime($date);
    $path = $baseDir . date('Y', $time) . "/" . date('m', $time) . "/" . date('d', $time) . "/";
    $files = glob($path . "*" . $id . ".*");
    if ($files && isset($files[0])) $targetFile = $files[0];
}

// Busca recursiva
if (empty($targetFile)) {
    $cmd = "find $baseDir -mtime -2 -name '*$id.*' | head -n 1";
    $targetFile = trim(shell_exec($cmd));
}

// 2. Entrega o Arquivo
if (!empty($targetFile) && file_exists($targetFile)) {
    $extension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $mime = ($extension == 'gsm') ? 'audio/x-gsm' : 'audio/wav';
    $filename = basename($targetFile);

    if ($download) {
        // Headers para forçar Download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($targetFile));
    } else {
        // Headers para Streaming
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($targetFile));
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
    }

    readfile($targetFile);
    exit;
}

http_response_code(404);
echo "Arquivo de audio nao encontrado.";
?>