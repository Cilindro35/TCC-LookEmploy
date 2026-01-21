<?php

namespace Api\WebSocket;

use Api\Security\JWTHandler;
use Api\Security\MessageEncryption;
use Api\Security\RateLimiter;
use Api\Security\PermissionManager;
use Api\Security\SecurityLogger;
use Exception;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PDO;

class SistemaChatSeguro implements MessageComponentInterface 
{
    protected $clientes;
    protected $pdo;
    protected $jwtHandler;
    protected $encryption;
    protected $rateLimiter;
    protected $permissionManager;
    protected $logger;
    protected $authenticatedUsers;

    public function __construct()
    {
        date_default_timezone_set('America/Sao_Paulo');
        $this->clientes = new \SplObjectStorage;
        $this->authenticatedUsers = [];

        try {
            $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
            $dbName = $_ENV['DB_NAME'] ?? 'LookEmploy';
            $dbUser = $_ENV['DB_USER'] ?? 'root';
            $dbPass = $_ENV['DB_PASS'] ?? '';
            $dbCharset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

            $this->pdo = new PDO(
                "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}",
                $dbUser,
                $dbPass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            try { $this->pdo->exec("SET time_zone='-03:00'"); } catch (\Exception $e) {}
            echo "Conexão com banco estabelecida com sucesso\n";
        } catch (Exception $e) {
            die("ERRO ao conectar ao banco: " . $e->getMessage() . "\n");
        }

        $this->jwtHandler = new JWTHandler();
        $this->encryption = new MessageEncryption();
        $this->rateLimiter = new RateLimiter();
        $this->permissionManager = new PermissionManager($this->pdo);
        $this->logger = new SecurityLogger($this->pdo);

        echo "Sistema de segurança inicializado\n";
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $conn->userId = null;
        $conn->userName = null;
        $conn->userType = null;
        $conn->authenticated = false;

        $this->clientes->attach($conn);
        
        $ip = $conn->remoteAddress;
        echo "Nova conexão: {$conn->resourceId} de IP: {$ip}\n";

        $conn->send(json_encode([
            'tipo' => 'sistema',
            'mensagem' => 'Conectado ao servidor. Aguardando autenticação...'
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        try {
            $data = json_decode($msg, true);

            if (!$data || !isset($data['tipo'])) {
                $this->sendError($from, "Formato de mensagem inválido");
                return;
            }

            if ($data['tipo'] === 'auth') {
                $this->handleAuthentication($from, $data);
                return;
            }

            if (!$from->authenticated) {
                $this->sendError($from, "Não autenticado. Faça login primeiro.");
                $this->logger->logUnauthorizedAccess(null, 'websocket_message');
                return;
            }

            switch ($data['tipo']) {
                case 'mensagem':
                    $this->handleMessage($from, $data);
                    break;

                case 'lido':
                    $this->handleReadReceipt($from, $data);
                    break;

                case 'digitando':
                case 'parou':
                    $this->handleTyping($from, $data);
                    break;

                default:
                    $this->sendError($from, "Tipo de mensagem desconhecido");
            }

        } catch (Exception $e) {
            echo "Erro ao processar mensagem: " . $e->getMessage() . "\n";
            $this->sendError($from, "Erro ao processar mensagem");
            $this->logger->logEvent('MESSAGE_ERROR', $from->userId ?? null, [
                'error' => $e->getMessage()
            ], 'ERROR');
        }
    }

    private function handleAuthentication(ConnectionInterface $conn, $data)
    {
        if (!isset($data['token'])) {
            $this->sendError($conn, "Token não fornecido");
            $this->logger->logFailedAuthentication(null, 'missing_token');
            return;
        }

        $payload = $this->jwtHandler->validateToken($data['token']);

        if (!$payload) {
            $this->sendError($conn, "Token inválido ou expirado");
            $this->logger->logFailedAuthentication(null, 'invalid_token');
            $conn->close();
            return;
        }

        if (isset($this->authenticatedUsers[$payload['user_id']])) {
            $oldConn = $this->authenticatedUsers[$payload['user_id']];
            if ($oldConn !== $conn && $this->clientes->contains($oldConn)) {
                $oldConn->send(json_encode([
                    'tipo' => 'sistema',
                    'mensagem' => 'Você foi desconectado (nova sessão iniciada)'
                ]));
                $oldConn->close();
            }
        }

        $conn->userId = $payload['user_id'];
        $conn->userName = $payload['user_name'];
        $conn->userType = $payload['user_type'];
        $conn->authenticated = true;

        $this->authenticatedUsers[$payload['user_id']] = $conn;

        try {
            $this->pdo->prepare("UPDATE usuarios SET online = 1 WHERE id = ?")
                ->execute([$conn->userId]);
        } catch (Exception $e) {
            echo "Erro ao atualizar status online: " . $e->getMessage() . "\n";
        }

        echo "Usuário autenticado: {$conn->userName} (ID: {$conn->userId})\n";
        $this->logger->logSuccessfulAuthentication($conn->userId);

        $conn->send(json_encode([
            'tipo' => 'auth_success',
            'mensagem' => 'Autenticado com sucesso',
            'usuario' => [
                'id' => $conn->userId,
                'nome' => $conn->userName,
                'tipo' => $conn->userType
            ]
        ]));

        $this->broadcastOnlineStatus($conn->userId, 'online');
    }

    private function handleMessage(ConnectionInterface $from, $data)
    {
        if (!isset($data['destinatario_id']) || !isset($data['mensagem'])) {
            $this->sendError($from, "Dados incompletos");
            return;
        }

        $destinatarioId = (int)$data['destinatario_id'];
        $mensagem = trim($data['mensagem']);

        if (empty($mensagem)) {
            $this->sendError($from, "Mensagem vazia");
            return;
        }

        if (strlen($mensagem) > 5000) {
            $this->sendError($from, "Mensagem muito longa (máximo 5000 caracteres)");
            return;
        }

        $rateCheck = $this->rateLimiter->checkLimit($from->userId);
        if (!$rateCheck['allowed']) {
            $this->sendError($from, $rateCheck['reason']);
            $this->logger->logEvent('RATE_LIMIT_HIT', $from->userId, $rateCheck, 'WARNING');
            return;
        }

        if (!$this->permissionManager->canCommunicate($from->userId, $destinatarioId)) {
            $this->sendError($from, "Você não tem permissão para enviar mensagens a este usuário");
            $this->logger->logUnauthorizedAccess($from->userId, "message_to_$destinatarioId");
            return;
        }

        if ($this->permissionManager->isUserBlocked($destinatarioId, $from->userId)) {
            $this->sendError($from, "Você está bloqueado por este usuário");
            return;
        }

        try {
            $a = min($from->userId, $destinatarioId);
            $b = max($from->userId, $destinatarioId);

            $stmt = $this->pdo->prepare("SELECT id FROM conversas WHERE user_a = ? AND user_b = ?");
            $stmt->execute([$a, $b]);
            $conversaId = (int)($stmt->fetchColumn() ?: 0);
            if (!$conversaId) {
                $stmt = $this->pdo->prepare("INSERT INTO conversas (user_a, user_b) VALUES (?, ?)");
                $stmt->execute([$a, $b]);
                $conversaId = (int)$this->pdo->lastInsertId();
            }

            $mensagemCriptografada = $this->encryption->encrypt($mensagem);

            $stmt = $this->pdo->prepare("
                INSERT INTO mensagens (remetente_id, destinatario_id, conversa_id, mensagem, data_envio, entregue, lido)
                VALUES (?, ?, ?, ?, NOW(), 0, 0)
            ");

            $stmt->execute([$from->userId, $destinatarioId, $conversaId, $mensagemCriptografada]);
            $mensagemId = $this->pdo->lastInsertId();

            echo "Mensagem salva: User {$from->userId} -> User {$destinatarioId}\n";
            $this->logger->logMessageSent($from->userId, $destinatarioId);

            $dataEnvio = date("Y-m-d H:i:s");

            $responseData = [
                'tipo' => 'mensagem',
                'id' => $mensagemId,
                'remetente_id' => $from->userId,
                'remetente_nome' => $from->userName,
                'destinatario_id' => $destinatarioId,
                'mensagem' => $mensagem,
                'data_envio' => $dataEnvio,
                'entregue' => false,
                'lido' => false
            ];

            $from->send(json_encode(array_merge($responseData, ['echo' => true])));

            if (isset($this->authenticatedUsers[$destinatarioId])) {
                $destinatarioConn = $this->authenticatedUsers[$destinatarioId];
                
                $this->pdo->prepare("UPDATE mensagens SET entregue = 1 WHERE id = ?")
                    ->execute([$mensagemId]);

                $responseData['entregue'] = true;
                $destinatarioConn->send(json_encode($responseData));
            }

        } catch (Exception $e) {
            echo "Erro ao salvar mensagem: " . $e->getMessage() . "\n";
            $this->sendError($from, "Erro ao enviar mensagem");
            $this->logger->logEvent('MESSAGE_SAVE_ERROR', $from->userId, [
                'error' => $e->getMessage()
            ], 'ERROR');
        }
    }

    private function handleReadReceipt(ConnectionInterface $from, $data)
    {
        if (!isset($data['remetente_id'])) {
            return;
        }

        $remetenteId = (int)$data['remetente_id'];

        try {
            $this->pdo->prepare("
                UPDATE mensagens
                SET lido = 1
                WHERE remetente_id = ? AND destinatario_id = ? AND lido = 0
            ")->execute([$remetenteId, $from->userId]);

            if (isset($this->authenticatedUsers[$remetenteId])) {
                $remetenteConn = $this->authenticatedUsers[$remetenteId];
                $remetenteConn->send(json_encode([
                    'tipo' => 'lido',
                    'usuario_id' => $from->userId,
                    'usuario_nome' => $from->userName
                ]));
            }

        } catch (Exception $e) {
            echo "Erro ao marcar como lido: " . $e->getMessage() . "\n";
        }
    }

    private function handleTyping(ConnectionInterface $from, $data)
    {
        if (!isset($data['destinatario_id'])) {
            return;
        }

        $destinatarioId = (int)$data['destinatario_id'];

        if (isset($this->authenticatedUsers[$destinatarioId])) {
            $destinatarioConn = $this->authenticatedUsers[$destinatarioId];
            $destinatarioConn->send(json_encode([
                'tipo' => $data['tipo'],
                'usuario_id' => $from->userId,
                'usuario_nome' => $from->userName
            ]));
        }
    }

    private function broadcastOnlineStatus($userId, $status)
    {
        $message = json_encode([
            'tipo' => 'status',
            'usuario_id' => $userId,
            'status' => $status
        ]);

        foreach ($this->clientes as $cliente) {
            if ($cliente->authenticated && $cliente->userId != $userId) {
                $cliente->send($message);
            }
        }
    }

    private function sendError(ConnectionInterface $conn, $message)
    {
        $conn->send(json_encode([
            'tipo' => 'error',
            'mensagem' => $message
        ]));
    }

    public function onClose(ConnectionInterface $conn)
    {
        if ($conn->authenticated && $conn->userId) {
            try {
                $this->pdo->prepare("UPDATE usuarios SET online = 0 WHERE id = ?")
                    ->execute([$conn->userId]);
                
                echo "Usuário desconectado: {$conn->userName} (ID: {$conn->userId})\n";
                
                unset($this->authenticatedUsers[$conn->userId]);
                
                $this->broadcastOnlineStatus($conn->userId, 'offline');

            } catch (Exception $e) {
                echo "Erro ao atualizar status offline: " . $e->getMessage() . "\n";
            }
        }

        $this->clientes->detach($conn);
        echo "Conexão encerrada: {$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        echo "Erro na conexão {$conn->resourceId}: {$e->getMessage()}\n";
        
        if ($conn->authenticated && $conn->userId) {
            $this->logger->logEvent('CONNECTION_ERROR', $conn->userId, [
                'error' => $e->getMessage()
            ], 'ERROR');
        }

        $conn->close();
    }
}
