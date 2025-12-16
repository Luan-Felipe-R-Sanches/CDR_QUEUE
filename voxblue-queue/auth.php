<?php
// Arquivo: auth.php
session_start();

// Verifica se existe sessão válida
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Tenta detectar se está numa subpasta (ex: realtime/) para corrigir o link
    $path = file_exists('login.php') ? 'login.php' : '../login.php';
    header("Location: $path");
    exit;
}
?>