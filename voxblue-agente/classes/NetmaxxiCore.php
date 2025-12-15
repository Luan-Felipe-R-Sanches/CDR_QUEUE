<?php
namespace Netmaxxi;

class NetmaxxiCore
{
    private $socket;
    private $host;
    private $port;
    private $user;
    private $pass;
    private $agentMap = []; 

    public function __construct($config)
    {
        // Pega config do array simples
        $this->host = $config['ami']['host'];
        $this->port = $config['ami']['port'];
        $this->user = $config['ami']['username'];
        $this->pass = $config['ami']['secret'];
        
        // Mapeamento de Agentes (Ramal -> Nome)
        $this->agentMap = $config['agentes'] ?? [];
    }

    // Conecta no Socket (TCP Puro)
    private function connect()
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 3);
        if (!$this->socket) throw new \Exception("Erro Conexão AMI: $errstr");

        // Autenticação
        $this->send("Action: Login\r\nUsername: {$this->user}\r\nSecret: {$this->pass}\r\n\r\n");
        $this->read(); // Lê a resposta do login para limpar o buffer
    }

    private function send($data)
    {
        @fwrite($this->socket, $data);
    }

    // Leitura inteligente do buffer
    private function read()
    {
        $response = "";
        $start = microtime(true);
        // Lê até o socket parar de mandar dados ou timeout de 2s
        while (!feof($this->socket)) {
            $line = fgets($this->socket, 4096);
            $response .= $line;
            // Se a linha for vazia e já tivermos conteúdo, provavelmente acabou o pacote
            if (trim($line) == "" && strlen($response) > 5) break; 
            if ((microtime(true) - $start) > 2) break;
        }
        return $response;
    }

    private function disconnect()
    {
        $this->send("Action: Logoff\r\n\r\n");
        @fclose($this->socket);
    }

    // Busca status das filas com CACHE DE 2 SEGUNDOS
    public function getQueueLiveStatus($queues)
    {
        // Define o arquivo de cache na pasta temporária do Linux
        $cacheFile = sys_get_temp_dir() . '/voxblue_cache.json';
        
        // 1. Tenta ler do cache se for recente (menos de 2s)
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 2)) {
            $json = @file_get_contents($cacheFile);
            if ($json) return json_decode($json, true);
        }

        // 2. Se não tem cache, conecta no Asterisk
        try {
            $this->connect();
            
            // Prepara array de resposta
            $data = [];
            foreach ($queues as $id => $name) {
                $data[$id] = [
                    'name' => $name, 'calls' => [], 'members' => [], 'count' => 0,
                    'stats' => ['free' => 0, 'busy' => 0, 'paused' => 0, 'unavailable' => 0]
                ];
            }

            // Manda o comando
            $this->send("Action: QueueStatus\r\n\r\n");
            
            // Lê o "bombardeio" de eventos que o Asterisk manda
            $buffer = "";
            $start = microtime(true);
            while (!feof($this->socket)) {
                $line = fgets($this->socket, 4096);
                $buffer .= $line;
                if (strpos($line, 'QueueStatusComplete') !== false) break; // Parar quando terminar
                if ((microtime(true) - $start) > 3) break; // Timeout segurança
            }
            $this->disconnect();

            // Processa o texto bruto (Parse Manual Leve)
            $blocks = explode("\r\n\r\n", $buffer);
            foreach ($blocks as $block) {
                if (strpos($block, 'Event: QueueMember') !== false) {
                    $m = $this->parseBlock($block);
                    $qid = $m['Queue'] ?? '';
                    if (isset($data[$qid])) {
                        $iface = $m['Name'] ?? $m['Location'] ?? 'Unknown';
                        $status = intval($m['Status'] ?? 0);
                        $paused = ($m['Paused'] ?? '0') == '1';
                        
                        // Nome bonito
                        $num = preg_replace('/[^0-9]/', '', $iface);
                        $name = $this->agentMap[$num] ?? $iface;

                        $data[$qid]['members'][$iface] = [
                            'display_name' => $name,
                            'paused' => $paused,
                            'status' => $status
                        ];
                    }
                }
                elseif (strpos($block, 'Event: QueueEntry') !== false) {
                    $c = $this->parseBlock($block);
                    $qid = $c['Queue'] ?? '';
                    if (isset($data[$qid])) {
                        $data[$qid]['calls'][] = [
                            'caller_id' => $c['CallerIDNum'] ?? 'Anonimo',
                            'wait_time' => $c['Wait'] ?? 0
                        ];
                    }
                }
            }

            // Recalcula totais e stats
            foreach ($data as &$q) {
                $q['count'] = count($q['calls']);
                // Ordena chamadas (maior tempo primeiro)
                usort($q['calls'], function($a, $b) { return $b['wait_time'] - $a['wait_time']; });
                
                // Contagem de Status
                foreach ($q['members'] as $m) {
                    if ($m['paused']) $q['stats']['paused']++;
                    elseif ($m['status'] == 1) $q['stats']['free']++;
                    elseif (in_array($m['status'], [2,6])) $q['stats']['busy']++;
                    else $q['stats']['unavailable']++;
                }
            }

            // 3. Salva no Cache
            @file_put_contents($cacheFile, json_encode($data));
            return $data;

        } catch (\Exception $e) {
            // Em caso de erro, entrega o cache velho se existir
            if (file_exists($cacheFile)) return json_decode(file_get_contents($cacheFile), true);
            return []; 
        }
    }

    // Helper para transformar texto do AMI em Array
    private function parseBlock($text) {
        $lines = explode("\n", $text);
        $res = [];
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) == 2) $res[trim($parts[0])] = trim($parts[1]);
        }
        return $res;
    }

    public function agentAction($type, $queue, $interface) {
        try {
            $this->connect();
            $cmd = "";
            if ($type == 'login') $cmd = "Action: QueueAdd\r\nQueue: $queue\r\nInterface: $interface\r\nPenalty: 0\r\nPaused: false\r\n\r\n";
            if ($type == 'logout') $cmd = "Action: QueueRemove\r\nQueue: $queue\r\nInterface: $interface\r\n\r\n";
            if ($type == 'pause') $cmd = "Action: QueuePause\r\nQueue: $queue\r\nInterface: $interface\r\nPaused: true\r\n\r\n";
            if ($type == 'unpause') $cmd = "Action: QueuePause\r\nQueue: $queue\r\nInterface: $interface\r\nPaused: false\r\n\r\n";
            
            $this->send($cmd);
            $res = $this->read();
            $this->disconnect();

            if (strpos($res, 'Error') !== false || strpos($res, 'failed') !== false) {
                return ['success' => false, 'message' => 'Falha no comando Asterisk'];
            }
            return ['success' => true, 'message' => 'Comando aceito'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}