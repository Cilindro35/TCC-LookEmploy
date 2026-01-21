<?php
require_once __DIR__ . '/vendor/autoload.php';
if (class_exists('Dotenv\\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$aes = $_ENV['AES_KEY'] ?? $_SERVER['AES_KEY'] ?? getenv('AES_KEY');
if (!$aes) {
    $def = $_ENV['ENCRYPTION_KEY'] ?? $_SERVER['ENCRYPTION_KEY'] ?? getenv('ENCRYPTION_KEY') ?? 'lookemploy_default_aes_key_32_chars_min_secure!!';
    $_ENV['AES_KEY'] = $def;
    $_SERVER['AES_KEY'] = $def;
    putenv("AES_KEY=$def");
}

$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbName = $_ENV['DB_NAME'] ?? 'LookEmploy';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? '';
$dbCharset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '-03:00'");
} catch (Exception $e) {
    die("Erro ao conectar: " . $e->getMessage());
}
?>
