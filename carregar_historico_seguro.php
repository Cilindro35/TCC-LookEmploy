<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

session_start();

require_once __DIR__ . '/api_chat/vendor/autoload.php';

use Api\Security\MessageEncryption;
use Api\Security\PermissionManager;

// Compatibilidade de chaves de sessão
$rawSessionId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : (isset($_SESSION['usuario']) ? (int)$_SESSION['usuario'] : 0);
if (!$rawSessionId) {
    echo json_encode(['error' => 'Não autenticado']);
    exit();
}

if (!isset($_GET['contato_id']) || !is_numeric($_GET['contato_id'])) {
    echo json_encode(['error' => 'ID de contato inválido']);
    exit();
}

$contatoId = (int)$_GET['contato_id'];
// já obtido acima
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    require_once __DIR__ . '/api_chat/conectar.php';

    // Mapear sessão (Cliente.ID/Prestador.ID) para usuarios.id
    $tipoSessao = $_SESSION['tipo'] ?? '';
    $usuarioAtualId = 0;
    if (strcasecmp($tipoSessao, 'Cliente') === 0) {
        $stmtMap = $pdo->prepare("SELECT usuario_id FROM Cliente WHERE ID = ?");
        $stmtMap->execute([$rawSessionId]);
        $usuarioAtualId = (int)$stmtMap->fetchColumn();
    } else if (strcasecmp($tipoSessao, 'Prestador') === 0) {
        $stmtMap = $pdo->prepare("SELECT usuario_id FROM Prestador WHERE ID = ?");
        $stmtMap->execute([$rawSessionId]);
        $usuarioAtualId = (int)$stmtMap->fetchColumn();
    } else {
        $usuarioAtualId = $rawSessionId;
    }
    if (!$usuarioAtualId) {
        echo json_encode(['error' => 'Usuário inválido']);
        exit();
    }

    $permissionManager = new PermissionManager($pdo);
    
    if (!$permissionManager->canCommunicate($usuarioAtualId, $contatoId)) {
        echo json_encode(['error' => 'Sem permissão para acessar esta conversa']);
        exit();
    }

    if ($permissionManager->isUserBlocked($contatoId, $usuarioAtualId)) {
        echo json_encode(['error' => 'Você foi bloqueado por este usuário']);
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.remetente_id,
            m.destinatario_id,
            m.mensagem,
            m.data_envio,
            m.entregue,
            m.lido,
            u.nome as remetente_nome
        FROM mensagens m
        JOIN usuarios u ON u.id = m.remetente_id
        WHERE (m.remetente_id = :usuario AND m.destinatario_id = :contato)
           OR (m.remetente_id = :contato AND m.destinatario_id = :usuario)
        ORDER BY m.data_envio ASC
        LIMIT :limit OFFSET :offset
    ");
    
    $stmt->bindValue(':usuario', $usuarioAtualId, PDO::PARAM_INT);
    $stmt->bindValue(':contato', $contatoId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $encryption = new MessageEncryption();
    
    foreach ($mensagens as &$msg) {
        try {
            $msg['mensagem'] = $encryption->decrypt($msg['mensagem']);
        } catch (Exception $e) {
            $raw = $msg['mensagem'];
            if (preg_match('/^[A-Za-z0-9+\/=]+$/', $raw)) {
                $msg['mensagem'] = '[Mensagem criptografada - erro ao descriptografar]';
            } else {
                $msg['mensagem'] = $raw;
            }
        }
    }
    
    echo json_encode($mensagens);
    
} catch (Exception $e) {
    error_log("Erro ao carregar histórico: " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao carregar mensagens']);
}
