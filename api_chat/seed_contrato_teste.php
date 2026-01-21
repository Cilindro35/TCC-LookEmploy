<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

$autoloads = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php'
];
foreach ($autoloads as $autoload) {
    if (file_exists($autoload)) {
        require_once $autoload;
    }
}

require __DIR__ . '/conectar.php';

try {
    $rawSessionId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : (isset($_SESSION['usuario']) ? (int)$_SESSION['usuario'] : 0);
    if (!$rawSessionId) {
        echo json_encode(['error' => 'Não autenticado']);
        exit();
    }

    $tipoSessao = $_SESSION['tipo'] ?? '';
    $tipoAtual = null;
    $usuarioId = 0;
    if (strcasecmp($tipoSessao, 'Cliente') === 0) {
        $stmtMap = $pdo->prepare('SELECT usuario_id FROM Cliente WHERE ID = ?');
        $stmtMap->execute([$rawSessionId]);
        $usuarioId = (int)$stmtMap->fetchColumn();
        $tipoAtual = 'cliente';
    } else if (strcasecmp($tipoSessao, 'Prestador') === 0) {
        $stmtMap = $pdo->prepare('SELECT usuario_id FROM Prestador WHERE ID = ?');
        $stmtMap->execute([$rawSessionId]);
        $usuarioId = (int)$stmtMap->fetchColumn();
        $tipoAtual = 'prestador';
    } else {
        // fallback: tentar diretamente
        $usuarioId = $rawSessionId;
        $stmtTipo = $pdo->prepare('SELECT tipo FROM usuarios WHERE id = ?');
        $stmtTipo->execute([$usuarioId]);
        $tipoAtual = $stmtTipo->fetchColumn();
    }
    if (!$usuarioId || !$tipoAtual) {
        echo json_encode(['error' => 'Sessão inválida']);
        exit();
    }

    if ($tipoAtual === 'cliente') {
        $stmtCli = $pdo->prepare('SELECT ID FROM Cliente WHERE usuario_id = ?');
        $stmtCli->execute([$usuarioId]);
        $clienteId = $stmtCli->fetchColumn();
        if (!$clienteId) {
            echo json_encode(['error' => 'Cliente não encontrado']);
            exit();
        }

        $stmtPrest = $pdo->prepare('SELECT ID, usuario_id FROM Prestador WHERE usuario_id <> ? ORDER BY ID LIMIT 1');
        $stmtPrest->execute([$usuarioId]);
        $prest = $stmtPrest->fetch(PDO::FETCH_ASSOC);
        if (!$prest) {
            echo json_encode(['error' => 'Nenhum prestador disponível']);
            exit();
        }

        $stmtIns = $pdo->prepare('INSERT INTO Servico (bairro, logradouro, numero, complemento, dataServico, tipoPagamento, descricao, contrato, prestador, cliente) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmtIns->execute(['Centro', 'Rua Teste', '100', '', date('Y-m-d H:i:s', time()+86400), 'pix', 'Contrato de teste', 'pendente', (int)$prest['ID'], (int)$clienteId]);
        echo json_encode(['ok' => true]);
        exit();
    }

    if ($tipoAtual === 'prestador') {
        $stmtPrest = $pdo->prepare('SELECT ID FROM Prestador WHERE usuario_id = ?');
        $stmtPrest->execute([$usuarioId]);
        $prestadorId = $stmtPrest->fetchColumn();
        if (!$prestadorId) {
            echo json_encode(['error' => 'Prestador não encontrado']);
            exit();
        }

        $stmtCli = $pdo->prepare('SELECT ID, usuario_id FROM Cliente WHERE usuario_id <> ? ORDER BY ID LIMIT 1');
        $stmtCli->execute([$usuarioId]);
        $cli = $stmtCli->fetch(PDO::FETCH_ASSOC);
        if (!$cli) {
            echo json_encode(['error' => 'Nenhum cliente disponível']);
            exit();
        }

        $stmtIns = $pdo->prepare('INSERT INTO Servico (bairro, logradouro, numero, complemento, dataServico, tipoPagamento, descricao, contrato, prestador, cliente) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmtIns->execute(['Centro', 'Rua Teste', '200', '', date('Y-m-d H:i:s', time()+86400), 'pix', 'Contrato de teste', 'pendente', (int)$prestadorId, (int)$cli['ID']]);
        echo json_encode(['ok' => true]);
        exit();
    }

    echo json_encode(['error' => 'Tipo de usuário inválido']);

} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao criar contrato de teste']);
}
