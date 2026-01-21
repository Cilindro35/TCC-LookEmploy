<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/conectar.php';

try {
    $rawSessionId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : (isset($_SESSION['usuario']) ? (int)$_SESSION['usuario'] : 0);
    if (!$rawSessionId) {
        echo json_encode(['error' => 'Não autenticado']);
        exit();
    }

    $tipoSessao = $_SESSION['tipo'] ?? '';
    $usuarioId = 0;
    if (strcasecmp($tipoSessao, 'Cliente') === 0) {
        $stmtMap = $pdo->prepare('SELECT usuario_id FROM Cliente WHERE ID = ?');
        $stmtMap->execute([$rawSessionId]);
        $usuarioId = (int)$stmtMap->fetchColumn();
    } else if (strcasecmp($tipoSessao, 'Prestador') === 0) {
        $stmtMap = $pdo->prepare('SELECT usuario_id FROM Prestador WHERE ID = ?');
        $stmtMap->execute([$rawSessionId]);
        $usuarioId = (int)$stmtMap->fetchColumn();
    } else {
        $usuarioId = $rawSessionId;
    }
    if (!$usuarioId) {
        echo json_encode(['count' => 0]);
        exit();
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM mensagens WHERE destinatario_id = ? AND lido = 0');
    $stmt->execute([$usuarioId]);
    $count = (int)$stmt->fetchColumn();

    echo json_encode(['count' => $count]);
} catch (Exception $e) {
    echo json_encode(['count' => 0]);
}

