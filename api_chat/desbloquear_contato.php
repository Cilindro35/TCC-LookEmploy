<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) { echo json_encode(['error'=>'Não autenticado']); exit(); }
if (!isset($_POST['contato_id']) || !is_numeric($_POST['contato_id'])) { echo json_encode(['error'=>'Contato inválido']); exit(); }

$contatoId = (int)$_POST['contato_id'];

try {
    require_once __DIR__ . '/conectar.php';

    $rawSessionId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : (int)$_SESSION['usuario'];
    $tipoSessao = $_SESSION['tipo'] ?? '';
    $usuarioId = 0;
    if (strcasecmp($tipoSessao, 'Cliente') === 0) {
        $st = $pdo->prepare('SELECT usuario_id FROM Cliente WHERE ID = ?');
        $st->execute([$rawSessionId]);
        $usuarioId = (int)$st->fetchColumn();
    } else if (strcasecmp($tipoSessao, 'Prestador') === 0) {
        $st = $pdo->prepare('SELECT usuario_id FROM Prestador WHERE ID = ?');
        $st->execute([$rawSessionId]);
        $usuarioId = (int)$st->fetchColumn();
    } else { $usuarioId = $rawSessionId; }
    if (!$usuarioId) { $usuarioId = $rawSessionId; }

    if ($usuarioId === $contatoId) { echo json_encode(['error'=>'Operação inválida']); exit(); }

    $stmt = $pdo->prepare('DELETE FROM usuarios_bloqueados WHERE usuario_id = ? AND bloqueado_id = ?');
    $stmt->execute([$usuarioId, $contatoId]);

    echo json_encode(['ok'=>true]);
} catch (Exception $e) {
    echo json_encode(['error'=>'Erro ao desbloquear']);
}

