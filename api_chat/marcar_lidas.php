<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require "conectar.php";

$rawSessionId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : (isset($_SESSION['usuario']) ? (int)$_SESSION['usuario'] : 0);
$contatoId = isset($_POST["contato"]) ? (int)$_POST["contato"] : 0;

if (!$rawSessionId || !$contatoId) {
    echo json_encode(["error" => "Dados inválidos"]);
    exit();
}

// Mapear sessão para usuarios.id
$tipoSessao = $_SESSION['tipo'] ?? '';
$usuarioId = 0;
if (strcasecmp($tipoSessao, 'Cliente') === 0) {
    $stmtMap = $pdo->prepare("SELECT usuario_id FROM Cliente WHERE ID = ?");
    $stmtMap->execute([$rawSessionId]);
    $usuarioId = (int)$stmtMap->fetchColumn();
} else if (strcasecmp($tipoSessao, 'Prestador') === 0) {
    $stmtMap = $pdo->prepare("SELECT usuario_id FROM Prestador WHERE ID = ?");
    $stmtMap->execute([$rawSessionId]);
    $usuarioId = (int)$stmtMap->fetchColumn();
} else {
    $usuarioId = $rawSessionId;
}
if (!$usuarioId) {
    echo json_encode(["error" => "Usuário inválido"]);
    exit();
}

$sql = $pdo->prepare("\n    UPDATE mensagens \n    SET lido = 1\n    WHERE remetente_id = ? AND destinatario_id = ?\n");
$sql->execute([$contatoId, $usuarioId]);

echo json_encode(["ok" => true]);
