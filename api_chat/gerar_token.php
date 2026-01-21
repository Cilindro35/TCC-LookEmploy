<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Autoload robusto: tenta autoload local e, se não existir, o da raiz do projeto
$autoloads = [
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php'
];
foreach ($autoloads as $autoload) {
    if (file_exists($autoload)) {
        require_once $autoload;
    }
}

use Api\Security\JWTHandler;
use Dotenv\Dotenv;

if (class_exists(Dotenv::class)) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} else {
    // Fallback: carregar .env manualmente
    $envFile = __DIR__ . '/.env';
    if (is_file($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim($parts[1], " \"\'" );
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
                putenv("$key=$val");
            }
        }
    }
}

// Verificar sessão e mapear para usuarios.id
$rawSessionId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : (isset($_SESSION['usuario']) ? (int)$_SESSION['usuario'] : 0);
$userName = $_SESSION['nome'] ?? ($_SESSION['user_name'] ?? '');
$userType = $_SESSION['tipo'] ?? ($_SESSION['user_type'] ?? '');

// Mapear IDs de Cliente/Prestador para usuarios.id
$mappedUserId = 0;
try {
    require __DIR__ . '/conectar.php';
    $tipoSessao = $_SESSION['tipo'] ?? '';
    if ($rawSessionId) {
        if (strcasecmp($tipoSessao, 'Cliente') === 0) {
            $st = $pdo->prepare('SELECT usuario_id FROM Cliente WHERE ID = ?');
            $st->execute([$rawSessionId]);
            $mappedUserId = (int)$st->fetchColumn();
        } else if (strcasecmp($tipoSessao, 'Prestador') === 0) {
            $st = $pdo->prepare('SELECT usuario_id FROM Prestador WHERE ID = ?');
            $st->execute([$rawSessionId]);
            $mappedUserId = (int)$st->fetchColumn();
        } else {
            $mappedUserId = $rawSessionId;
        }
        if (!$mappedUserId) {
            // Fallback 1: tentar resolver via e-mail da sessão
            $emailSessao = $_SESSION['email'] ?? '';
            if ($emailSessao) {
                $stE = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
                $stE->execute([$emailSessao]);
                $mappedUserId = (int)$stE->fetchColumn();
            }
            // Fallback 2: join por e-mail entre tabelas de perfil e usuarios
            if (!$mappedUserId) {
                if (strcasecmp($tipoSessao, 'Cliente') === 0) {
                    $stJ = $pdo->prepare('SELECT u.id FROM usuarios u JOIN Cliente c ON c.email = u.email WHERE c.ID = ?');
                    $stJ->execute([$rawSessionId]);
                    $mappedUserId = (int)$stJ->fetchColumn();
                } else if (strcasecmp($tipoSessao, 'Prestador') === 0) {
                    $stJ = $pdo->prepare('SELECT u.id FROM usuarios u JOIN Prestador p ON p.email = u.email WHERE p.ID = ?');
                    $stJ->execute([$rawSessionId]);
                    $mappedUserId = (int)$stJ->fetchColumn();
                }
            }
            // Fallback 3: usar ID cru como último recurso
            if (!$mappedUserId) {
                $mappedUserId = $rawSessionId;
            }
        }
    }
} catch (\Exception $e) {
    // Fallback silencioso: usa ID cru
    $mappedUserId = $rawSessionId;
}

if (!$mappedUserId || !$userName || !$userType) {
    echo json_encode([
        'error' => 'Usuário não autenticado',
        'details' => [
            'usuario' => $_SESSION['usuario'] ?? null,
            'usuario_id' => $_SESSION['usuario_id'] ?? null,
            'nome' => $_SESSION['nome'] ?? null,
            'tipo' => $_SESSION['tipo'] ?? null
        ]
    ]);
    exit();
}

try {
    $jwtHandler = new JWTHandler();
    
    // Gerar token com usuarios.id mapeado
    $token = $jwtHandler->generateToken(
        $mappedUserId,
        $userName,
        $userType
    );

    echo json_encode([
        'success' => true,
        'token' => $token,
        'usuario' => [
            'id' => $mappedUserId,
            'nome' => $userName,
            'tipo' => $userType
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => 'Erro ao gerar token',
        'details' => $e->getMessage()
    ]);
}
