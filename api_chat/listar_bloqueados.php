<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) { echo json_encode(['error'=>'Não autenticado']); exit(); }

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
    } else {
        $usuarioId = $rawSessionId;
    }
    if (!$usuarioId) { $usuarioId = $rawSessionId; }

    $stmt = $pdo->prepare('SELECT ub.bloqueado_id AS id, u.nome, u.online, ub.data_bloqueio FROM usuarios_bloqueados ub JOIN usuarios u ON u.id = ub.bloqueado_id WHERE ub.usuario_id = ? ORDER BY ub.data_bloqueio DESC');
    $stmt->execute([$usuarioId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $foto = null;
        $stF = $pdo->prepare('SELECT caminhoImagemPerfil FROM Cliente WHERE usuario_id = ?');
        $stF->execute([(int)$r['id']]);
        $foto = $stF->fetchColumn();
        if (!$foto) {
            $stF = $pdo->prepare('SELECT caminhoImagemPerfil FROM Prestador WHERE usuario_id = ?');
            $stF->execute([(int)$r['id']]);
            $foto = $stF->fetchColumn();
        }
        if ($foto) {
            $r['foto_perfil'] = (strpos($foto, 'img/') === 0 || strpos($foto, 'uploads/') === 0) ? $foto : ('img/img_perfil/' . $foto);
        } else {
            $r['foto_perfil'] = null;
        }
    }

    echo json_encode($rows);
} catch (Exception $e) {
    echo json_encode(['error'=>'Erro ao listar bloqueados']);
}

