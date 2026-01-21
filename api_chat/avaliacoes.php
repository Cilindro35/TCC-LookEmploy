<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'Não autenticado']);
    exit();
}

$tipoSessao = $_SESSION['tipo'] ?? '';
if (strcasecmp($tipoSessao, 'Cliente') !== 0) {
    echo json_encode(['error' => 'Somente clientes podem avaliar']);
    exit();
}

if (!isset($_POST['codigoServico']) || !is_numeric($_POST['codigoServico'])) {
    echo json_encode(['error' => 'Serviço inválido']);
    exit();
}
if (!isset($_POST['nota']) || !is_numeric($_POST['nota'])) {
    echo json_encode(['error' => 'Nota inválida']);
    exit();
}

$codigoServico = (int)$_POST['codigoServico'];
$nota = max(1, min(5, (int)$_POST['nota']));
$comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';
$clienteSessionId = (int)$_SESSION['usuario'];

try {
    require_once __DIR__ . '/conectar.php';

    // Garantir tabela de avaliações
    $pdo->exec("CREATE TABLE IF NOT EXISTS AvaliacaoPrestador (
        id INT AUTO_INCREMENT PRIMARY KEY,
        prestador_id INT NOT NULL,
        cliente_id INT NOT NULL,
        servico_id INT NOT NULL,
        nota INT NOT NULL,
        comentario TEXT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY ux_avaliacao_servico_cliente (servico_id, cliente_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Buscar serviço e validar pertencimento/estado
    $st = $pdo->prepare('SELECT contrato, prestador, cliente FROM Servico WHERE codigoServico = ?');
    $st->execute([$codigoServico]);
    $serv = $st->fetch(PDO::FETCH_ASSOC);
    if (!$serv) { echo json_encode(['error' => 'Serviço não encontrado']); exit(); }
    if ($serv['contrato'] !== 'concluido') { echo json_encode(['error' => 'Serviço ainda não concluído']); exit(); }

    // Validar que o cliente da sessão é o dono do serviço
    $stCli = $pdo->prepare('SELECT usuario_id FROM Cliente WHERE ID = ?');
    $stCli->execute([(int)$serv['cliente']]);
    $clienteUsuarioId = (int)$stCli->fetchColumn();
    if (!$clienteUsuarioId) { echo json_encode(['error' => 'Cliente inválido']); exit(); }
    // Sessões podem ter usuário_id mapeado ou id cru
    $sessUsuario = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : $clienteSessionId;
    if ($sessUsuario !== $clienteUsuarioId && $sessUsuario !== (int)$serv['cliente']) {
        echo json_encode(['error' => 'Permissão negada']);
        exit();
    }

    $prestadorId = (int)$serv['prestador'];

    // Inserir avaliação (evita duplicidade por serviço/cliente)
    $stIns = $pdo->prepare('INSERT INTO AvaliacaoPrestador (prestador_id, cliente_id, servico_id, nota, comentario) VALUES (?,?,?,?,?)');
    $stIns->execute([$prestadorId, (int)$serv['cliente'], $codigoServico, $nota, $comentario ?: null]);

    // Recalcular média ponderada das últimas 20 avaliações
    $stLast = $pdo->prepare('SELECT nota FROM AvaliacaoPrestador WHERE prestador_id = ? ORDER BY criado_em DESC LIMIT 20');
    $stLast->execute([$prestadorId]);
    $notas = $stLast->fetchAll(PDO::FETCH_COLUMN, 0);
    $media = 0.0;
    if ($notas && count($notas) > 0) {
        $n = count($notas);
        $sumW = 0.0;
        $sumWN = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $w = $n - $i;
            $sumW += $w;
            $sumWN += ((float)$notas[$i]) * $w;
        }
        $media = $sumWN / $sumW;
    }
    $media = round($media, 2);

    $stUpd = $pdo->prepare('UPDATE Prestador SET avaliacao = ? WHERE ID = ?');
    $stUpd->execute([$media, $prestadorId]);

    $stCount = $pdo->prepare('SELECT COUNT(*) FROM AvaliacaoPrestador WHERE prestador_id = ?');
    $stCount->execute([$prestadorId]);
    $total = (int)$stCount->fetchColumn();
    echo json_encode(['ok' => true, 'media' => $media, 'total' => $total]);
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'ux_avaliacao_servico_cliente') !== false) {
        echo json_encode(['error' => 'Você já avaliou este serviço']);
    } else {
        error_log('Erro avaliacoes.php: ' . $e->getMessage());
        echo json_encode(['error' => 'Erro ao salvar avaliação']);
    }
}
