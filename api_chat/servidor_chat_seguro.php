<?php

date_default_timezone_set('America/Sao_Paulo');

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

use Api\WebSocket\SistemaChatSeguro;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
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

$aes = $_ENV['AES_KEY'] ?? $_SERVER['AES_KEY'] ?? getenv('AES_KEY');
if (!$aes) {
    $def = $_ENV['ENCRYPTION_KEY'] ?? $_SERVER['ENCRYPTION_KEY'] ?? getenv('ENCRYPTION_KEY') ?? 'lookemploy_default_aes_key_32_chars_min_secure!!';
    $_ENV['AES_KEY'] = $def;
    $_SERVER['AES_KEY'] = $def;
    putenv("AES_KEY=$def");
}

echo "==========================================\n";
echo "  SERVIDOR DE CHAT SEGURO - LOOKEMPLOY\n";
echo "==========================================\n\n";

echo "Iniciando servidor WebSocket na porta 8080...\n";
echo "Sistema de segurança:\n";
echo "  [✓] Autenticação JWT\n";
echo "  [✓] Criptografia AES-256-GCM\n";
echo "  [✓] Rate Limiting\n";
echo "  [✓] Controle de Permissões\n";
echo "  [✓] Logs de Auditoria\n\n";

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new SistemaChatSeguro()
        )
    ),
    8080
);

echo "Servidor pronto! Aguardando conexões...\n";
echo "==========================================\n\n";

$server->run();
