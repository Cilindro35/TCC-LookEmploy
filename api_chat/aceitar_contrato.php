<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'Não autenticado']);
    exit();
}

if (!isset($_POST['codigoServico']) || !is_numeric($_POST['codigoServico'])) {
    echo json_encode(['error' => 'Código de serviço inválido']);
    exit();
}

$codigoServico = (int)$_POST['codigoServico'];
$meuId = (int)$_SESSION['usuario'];
$meuTipo = $_SESSION['tipo'] ?? '';

try {
    require_once __DIR__ . '/conectar.php';
    
    // Buscar serviço atual
    $stmt = $pdo->prepare("\n        SELECT contrato, prestador, cliente\n        FROM Servico\n        WHERE codigoServico = ?\n    ");
    
    $stmt->execute([$codigoServico]);
    $servico = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$servico) {
        echo json_encode(['error' => 'Serviço não encontrado']);
        exit();
    }
    
    $statusAtual = $servico['contrato'];

    if ($statusAtual === 'cancelado' || $statusAtual === 'concluido') {
        echo json_encode(['error' => 'Serviço inativo']);
        exit();
    }

    if (strcasecmp($meuTipo, 'Prestador') === 0) {
        if ((int)$servico['prestador'] !== $meuId) {
            echo json_encode(['error' => 'Somente o prestador do serviço pode aceitar']);
            exit();
        }
        // Prestador aceita e coloca direto em andamento
        if ($statusAtual === 'pendente') {
            $novoStatus = 'andamento';
        } else if ($statusAtual === 'andamento') {
            echo json_encode(['ok' => true, 'contrato' => 'andamento', 'message' => 'Serviço em andamento']);
            exit();
        } else {
            echo json_encode(['error' => 'Ação inválida para este estado']);
            exit();
        }
    } else if (strcasecmp($meuTipo, 'Cliente') === 0) {
        if ((int)$servico['cliente'] !== $meuId) {
            echo json_encode(['error' => 'Somente o cliente do serviço pode alterar']);
            exit();
        }
        // Cliente: somente conclui se já estiver em andamento
        if ($statusAtual === 'andamento') {
            $novoStatus = 'concluido';
        } else if ($statusAtual === 'pendente') {
            echo json_encode(['error' => 'Aguardando aceite do prestador']);
            exit();
        } else {
            echo json_encode(['error' => 'Ação não permitida para este estado']);
            exit();
        }
    } else {
        echo json_encode(['error' => 'Tipo de usuário inválido']);
        exit();
    }

    $stmt = $pdo->prepare("\n        UPDATE Servico\n        SET contrato = ?\n        WHERE codigoServico = ?\n    ");
    $stmt->execute([$novoStatus, $codigoServico]);

    echo json_encode(['ok' => true, 'contrato' => $novoStatus, 'message' => 'Status atualizado']);
    
} catch (Exception $e) {
    error_log("Erro em aceitar_contrato.php: " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao processar: ' . $e->getMessage()]);
}
