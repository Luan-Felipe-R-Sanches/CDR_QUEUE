<?php
// Arquivo: realtime/Lib/AsteriskService.php

class AsteriskService {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $timeout = 3;

    public function __construct() {
        // Usa constantes do config.php ou defaults seguros
        $this->host = defined('AMI_HOST') ? AMI_HOST : '127.0.0.1';
        $this->port = defined('AMI_PORT') ? AMI_PORT : 5038;
        $this->user = defined('AMI_USER') ? AMI_USER : '';
        $this->pass = defined('AMI_PASS') ? AMI_PASS : '';
    }

    /**
     * Envia comandos para o Asterisk e retorna a resposta
     */
    public function sendCommand($actions) {
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
        if (!$socket) throw new Exception("AMI Offline: $errstr");

        // 1. Login
        fwrite($socket, "Action: Login\r\nUsername: {$this->user}\r\nSecret: {$this->pass}\r\n\r\n");
        
        // 2. Limpa o Buffer de Login e Eventos iniciais (FullyBooted)
        $start = microtime(true);
        while (!feof($socket) && (microtime(true) - $start) < 2) {
            $line = fgets($socket, 4096);
            // Só para de ler quando encontrar a resposta do Login
            if (stripos($line, "Message: Authentication accepted") !== false) break;
            if (stripos($line, "Response: Error") !== false) {
                fclose($socket);
                throw new Exception("AMI Login Falhou");
            }
        }

        // 3. Envia o Comando Real
        fwrite($socket, $actions);

        // 4. Lê a Resposta do Comando
        $response = "";
        $start = microtime(true);
        while (!feof($socket) && (microtime(true) - $start) < 3) {
            $line = fgets($socket, 8192);
            $response .= $line;
            
            // Critérios de parada de leitura
            if (stripos($line, "QueueStatusComplete") !== false) break; // Para listas longas
            if (stripos($line, "Message: ") !== false && stripos($actions, "QueueStatus") === false) break; // Para comandos simples
        }

        fwrite($socket, "Action: Logoff\r\n\r\n");
        fclose($socket);

        return $response;
    }
}
?>