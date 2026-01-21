<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);
date_default_timezone_set('America/Sao_Paulo');

// Log de debug
file_put_contents(__DIR__ . '/logs/security.log', date('Y-m-d H:i:s') . " - Iniciando debug\n", FILE_APPEND);

if (!isset($_SESSION['usuario'])) {
    file_put_contents(__DIR__ . '/logs/security.log', "ERRO: Não autenticado\n", FILE_APPEND);
    echo json_encode(['error' => 'Não autenticado']);
    exit();
}

file_put_contents(__DIR__ . '/logs/security.log', "Usuário: " . $_SESSION['usuario'] . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/logs/security.log', "POST recebido: " . print_r($_POST, true) . "\n", FILE_APPEND);

if (!isset($_POST['codigoServico']) || !is_numeric($_POST['codigoServico'])) {
    file_put_contents(__DIR__ . '/logs/security.log', "ERRO: Código inválido\n", FILE_APPEND);
    echo json_encode(['error' => 'Código de serviço inválido']);
    exit();
}

$codigoServico = (int)$_POST['codigoServico'];
$meuId = (int)$_SESSION['usuario'];
$meuTipo = $_SESSION['tipo'] ?? '';

file_put_contents(__DIR__ . '/logs/security.log', "Código serviço: $codigoServico, Meu ID: $meuId, Tipo: $meuTipo\n", FILE_APPEND);

try {
    require_once __DIR__ . '/conectar.php';
    
    // Buscar informações do serviço
    $stmt = $pdo->prepare("
        SELECT 
            s.prestador,
            s.cliente,
            p.usuario_id as prestador_usuario_id,
            c.usuario_id as cliente_usuario_id,
            p.nome as prestador_nome,
            c.nome as cliente_nome
        FROM Servico s
        LEFT JOIN Prestador p ON s.prestador = p.ID
        LEFT JOIN Cliente c ON s.cliente = c.ID
        WHERE s.codigoServico = ?
    ");
    
    $stmt->execute([$codigoServico]);
    $servico = $stmt->fetch(PDO::FETCH_ASSOC);
    
    file_put_contents(__DIR__ . '/logs/security.log', "Serviço encontrado: " . print_r($servico, true) . "\n", FILE_APPEND);
    
    if (!$servico) {
        file_put_contents(__DIR__ . '/logs/security.log', "ERRO: Serviço não encontrado\n", FILE_APPEND);
        echo json_encode(['error' => 'Serviço não encontrado']);
        exit();
    }
    
    // Mapear meu ID de sessão para usuarios.id
    $usuarioAtualId = 0;
    if ($meuTipo === 'Cliente') {
        $stMap = $pdo->prepare('SELECT usuario_id FROM Cliente WHERE ID = ?');
        $stMap->execute([$meuId]);
        $usuarioAtualId = (int)$stMap->fetchColumn();
    } else if ($meuTipo === 'Prestador') {
        $stMap = $pdo->prepare('SELECT usuario_id FROM Prestador WHERE ID = ?');
        $stMap->execute([$meuId]);
        $usuarioAtualId = (int)$stMap->fetchColumn();
    }
    if (!$usuarioAtualId) {
        $usuarioAtualId = $meuId; // fallback
    }

    // Determinar o ID do outro usuário
    $outroUsuarioId = null;
    
    if ($meuTipo === 'Cliente') {
        $outroUsuarioId = (int)$servico['prestador_usuario_id'];
        file_put_contents(__DIR__ . '/logs/security.log', "Tipo Cliente - Prestador usuario_id: $outroUsuarioId\n", FILE_APPEND);
    } else if ($meuTipo === 'Prestador') {
        $outroUsuarioId = (int)$servico['cliente_usuario_id'];
        file_put_contents(__DIR__ . '/logs/security.log', "Tipo Prestador - Cliente usuario_id: $outroUsuarioId\n", FILE_APPEND);
    }
    
    if (!$outroUsuarioId) {
        file_put_contents(__DIR__ . '/logs/security.log', "ERRO: usuario_id não encontrado ou é 0\n", FILE_APPEND);
        
        // Tentar pegar direto da tabela usuarios pelo email
        if ($meuTipo === 'Cliente') {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = (SELECT email FROM Prestador WHERE ID = ?)");
            $stmt->execute([$servico['prestador']]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = (SELECT email FROM Cliente WHERE ID = ?)");
            $stmt->execute([$servico['cliente']]);
        }
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($usuario) {
            $outroUsuarioId = (int)$usuario['id'];
            file_put_contents(__DIR__ . '/logs/security.log', "Usuario_id encontrado por email: $outroUsuarioId\n", FILE_APPEND);
        }
    }
    
    if (!$outroUsuarioId) {
        echo json_encode([
            'error' => 'Usuário do chat não encontrado. Execute o SQL popular_usuarios.sql',
            'debug' => $servico
        ]);
        exit();
    }
    
    $stBlk = $pdo->prepare('SELECT 1 FROM usuarios_bloqueados WHERE usuario_id = ? AND bloqueado_id = ? LIMIT 1');
    $stBlk->execute([$usuarioAtualId, $outroUsuarioId]);
    if ($stBlk->fetchColumn()) {
        echo json_encode(['error' => 'Usuário bloqueado. Desbloqueie para conversar.']);
        exit();
    }
    $stBlk2 = $pdo->prepare('SELECT 1 FROM usuarios_bloqueados WHERE usuario_id = ? AND bloqueado_id = ? LIMIT 1');
    $stBlk2->execute([$outroUsuarioId, $usuarioAtualId]);
    if ($stBlk2->fetchColumn()) {
        echo json_encode(['error' => 'Você está bloqueado por este usuário.']);
        exit();
    }
    
    // Verificar se já existe alguma mensagem entre os dois
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM mensagens
        WHERE (remetente_id = ? AND destinatario_id = ?)
           OR (remetente_id = ? AND destinatario_id = ?)
    ");
    
    $stmt->execute([$usuarioAtualId, $outroUsuarioId, $outroUsuarioId, $usuarioAtualId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    file_put_contents(__DIR__ . '/logs/security.log', "Mensagens existentes: " . $result['count'] . "\n", FILE_APPEND);
    
    // Se não existe conversa, criar uma mensagem inicial automática
    if ($result['count'] == 0) {
        $stAs = $pdo->prepare('SELECT assunto FROM Servico WHERE codigoServico = ?');
        $stAs->execute([$codigoServico]);
        $assunto = trim((string)($stAs->fetchColumn() ?: ''));
        if ($assunto !== '') {
            $mensagemInicial = 'Olá! Estou entrando em contato sobre: ' . $assunto . ' (serviço #' . $codigoServico . ')';
        } else {
            $mensagemInicial = 'Olá! Estou entrando em contato sobre o serviço #' . $codigoServico;
        }
        require_once __DIR__ . '/vendor/autoload.php';
        $mensagemCriptografada = $mensagemInicial;
        if (class_exists('Api\\Security\\MessageEncryption')) {
            $enc = new \Api\Security\MessageEncryption();
            $mensagemCriptografada = $enc->encrypt($mensagemInicial);
        }
        
        
        // Garantir conversa_id para o par de usuários
        $a = min($usuarioAtualId, $outroUsuarioId);
        $b = max($usuarioAtualId, $outroUsuarioId);
        $stConv = $pdo->prepare("SELECT id FROM conversas WHERE user_a = ? AND user_b = ?");
        $stConv->execute([$a, $b]);
        $conversaId = (int)($stConv->fetchColumn() ?: 0);
        if (!$conversaId) {
            $insConv = $pdo->prepare("INSERT INTO conversas (user_a, user_b) VALUES (?, ?)");
            $insConv->execute([$a, $b]);
            $conversaId = (int)$pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("\n            INSERT INTO mensagens (remetente_id, destinatario_id, conversa_id, mensagem, data_envio, entregue, lido)\n            VALUES (?, ?, ?, ?, ?, 0, 0)\n        ");
        $agora = date('Y-m-d H:i:s');
        $stmt->execute([$usuarioAtualId, $outroUsuarioId, $conversaId, $mensagemCriptografada, $agora]);
        file_put_contents(__DIR__ . '/logs/security.log', "Mensagem inicial criada (conversa_id=$conversaId)\n", FILE_APPEND);
    }
    
    file_put_contents(__DIR__ . '/logs/security.log', "Sucesso! Redirecionando para: $outroUsuarioId\n", FILE_APPEND);
    
    echo json_encode([
        'ok' => true,
        'openId' => $outroUsuarioId,
        'message' => 'Chat garantido'
    ]);
    
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/logs/security.log', "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
    echo json_encode(['error' => 'Erro ao processar: ' . $e->getMessage()]);
}
