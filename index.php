<?php
// Arquivo: /var/www/html/index.php
session_start();
if (isset($_SESSION['vox_user'])) {
    header('Location: portal.php');
} else {
    header('Location: login.php');
}
exit;
?>