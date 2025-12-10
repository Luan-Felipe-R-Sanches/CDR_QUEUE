<?php
namespace Netmaxxi;

class NetmaxxiCore
{
    private $socket;
    private $host;
    private $port;
    private $user;
    private $pass;
    
    // 1. Nova propriedade para guardar a lista de nomes
    private $agentMap = []; 

    public function __construct($config)
    {
        // Ajuste para ler a chave 'ami' se ela existir, ou usar a raiz (compatibilidade)
        $amiConfig = $config['ami'] ?? $config;

        $this->host = $amiConfig['host'] ?? '127.0.0.1';
        $this->port = $amiConfig['port'] ?? 5038;
        $this->user = $amiConfig['username'] ?? 'netmaxxi_ws';
        $this->pass = $amiConfig['secret'] ?? '';
        
        // 2. Carrega a lista de agentes do config.php
        $this->agentMap = $config['agentes'] ?? [];
    }

    private function connect()
    {
        $target = str_replace('tcp://', '', $this->host);
        $this->socket = @fsockopen($target, $this->port, $errno, $errstr, 3);
        if (!$this->socket) throw new \Exception("Sem conexão AMI");

        // Login
        fwrite($this->socket, "Action: Login\r\nUsername: {$this->user}\r\nSecret: {$this->pass}\r\n\r\n");
        $this->readResponse();
    }

    private function readResponse()
    {
        $response = "";
        $start = microtime(true);
        while (!feof($this->socket)) {
            $line = fgets($this->socket, 4096);
            $response .= $line;
            if (trim($line) == "" && strlen($response) > 5) break;
            if ((microtime(true) - $start) > 2) break;
        }
        return $response;
    }

    private function readQueueStatusEvents()
    {
        $events = [];
        $currentEvent = [];
        $start = microtime(true);
        while (!feof($this->socket)) {
            $line = fgets($this->socket, 4096);
            $trimLine = trim($line);
            if ($trimLine == "") {
                if (!empty($currentEvent)) {
                    if (isset($currentEvent['Event']) && $currentEvent['Event'] == 'QueueStatusComplete') break;
                    $events[] = $currentEvent;
                    $currentEvent = [];
                }
            } else {
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $currentEvent[trim($key)] = trim($value);
                }
            }
            if ((microtime(true) - $start) > 3) break;
        }
        return $events;
    }

    // 3. Função auxiliar para limpar o ramal e achar o nome
    private function getAgentName($interface) {
        // Remove letras e símbolos, deixa só o número (ex: PJSIP/9034 -> 9034)
        $number = preg_replace('/[^0-9]/', '', $interface);
        
        // Retorna o nome se existir na lista, senão retorna o número
        return $this->agentMap[$number] ?? $number;
    }

    public function agentAction($action, $queue, $interface, $reason = '')
    {
        try {
            $this->connect();
            $packet = "";

            switch ($action) {
                case 'login':
                    $packet .= "Action: QueueAdd\r\nQueue: $queue\r\nInterface: $interface\r\nPenalty: 0\r\nPaused: false\r\nStateInterface: $interface\r\n";
                    break;
                case 'logout':
                    $packet .= "Action: QueueRemove\r\nQueue: $queue\r\nInterface: $interface\r\n";
                    break;
                case 'pause':
                    $packet .= "Action: Command\r\nCommand: queue pause member $interface queue $queue\r\n";
                    break;
                case 'unpause':
                    $packet .= "Action: Command\r\nCommand: queue unpause member $interface queue $queue\r\n";
                    break;
            }

            $packet .= "\r\n";
            fwrite($this->socket, $packet);
            $rawResponse = $this->readResponse();
            $this->disconnect();

            if (stripos($rawResponse, 'No such') !== false) {
                return ['success' => false, 'message' => "Agente não encontrado na fila."];
            }
            return ['success' => true, 'message' => "Comando realizado."];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getQueueLiveStatus($queues)
    {
        try {
            $this->connect();
            $data = [];
            foreach ($queues as $id => $name) {
                $data[$id] = [
                    'name' => $name,
                    'calls' => [],
                    'members' => [],
                    'count' => 0,
                    'stats' => ['free' => 0, 'busy' => 0, 'paused' => 0, 'unavailable' => 0]
                ];
            }

            fwrite($this->socket, "Action: QueueStatus\r\n\r\n");
            $events = $this->readQueueStatusEvents();
            $this->disconnect();

            foreach ($events as $ev) {
                if (!isset($ev['Queue']) || !isset($data[$ev['Queue']])) continue;
                $qid = $ev['Queue'];

                if ($ev['Event'] == 'QueueMember') {
                    $interface = $ev['Name'] ?? $ev['Location'];
                    $isPaused = ($ev['Paused'] == '1');
                    $status = intval($ev['Status']);
                    
                    // --- MUDANÇA AQUI: Busca o nome bonito ---
                    $displayName = $this->getAgentName($interface);

                    $data[$qid]['members'][$interface] = [
                        'display_name' => $displayName, // Novo campo
                        'paused' => $isPaused,
                        'status' => $status
                    ];

                    // Estatísticas
                    if ($isPaused) {
                        $data[$qid]['stats']['paused']++;
                    } elseif ($status == 2 || $status == 6) {
                        $data[$qid]['stats']['busy']++;
                    } elseif ($status == 1) {
                        $data[$qid]['stats']['free']++;
                    } else {
                        $data[$qid]['stats']['unavailable']++;
                    }
                }

                if ($ev['Event'] == 'QueueEntry') {
                    $data[$qid]['calls'][] = [
                        'caller_id' => $ev['CallerIDNum'],
                        'wait_time' => $ev['Wait']
                    ];
                }
            }

            foreach ($data as &$q) {
                $q['count'] = count($q['calls']);
                usort($q['calls'], function ($a, $b) {
                    return $b['wait_time'] - $a['wait_time'];
                });
            }

            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function disconnect()
    {
        @fwrite($this->socket, "Action: Logoff\r\n\r\n");
        @fclose($this->socket);
    }
}