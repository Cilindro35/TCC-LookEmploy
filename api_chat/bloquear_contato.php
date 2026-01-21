<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'Não autenticado']);
    exit();
}

if (!isset($_POST['contato_id']) || !is_numeric($_POST['contato_id'])) {
    echo json_encode(['error' => 'Contato inválido']);
    exit();
}

$contatoId = (int)$_POST['contato_id'];
$rawSessionId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : (int)$_SESSION['usuario'];
$tipoSessao = $_SESSION['tipo'] ?? '';

try {
    require_once __DIR__ . '/conectar.php';

    // Mapear sessão para usuarios.id
    $usuarioId = 0;
    if (strcasecmp($tipoSessao, 'Cliente') === 0) {
        $stmt = $pdo->prepare('SELECT usuario_id FROM Cliente WHERE ID = ?');
        $stmt->execute([$rawSessionId]);
        $usuarioId = (int)$stmt->fetchColumn();
    } else if (strcasecmp($tipoSessao, 'Prestador') === 0) {
        $stmt = $pdo->prepare('SELECT usuario_id FROM Prestador WHERE ID = ?');
        $stmt->execute([$rawSessionId]);
        $usuarioId = (int)$stmt->fetchColumn();
    } else {
        $usuarioId = $rawSessionId;
    }
    if (!$usuarioId) { $usuarioId = $rawSessionId; }

    if ($usuarioId === $contatoId) {
        echo json_encode(['error' => 'Operação inválida']);
        exit();
    }

    // Inserir bloqueio (ignorar duplicados)
    $stmtIns = $pdo->prepare('INSERT IGNORE INTO usuarios_bloqueados (usuario_id, bloqueado_id) VALUES (?, ?)');
    $stmtIns->execute([$usuarioId, $contatoId]);

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    error_log('Erro bloquear_contato: ' . $e->getMessage());
    echo json_encode(['error' => 'Erro ao bloquear contato']);
}

