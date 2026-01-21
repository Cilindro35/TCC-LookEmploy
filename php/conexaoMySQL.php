<?php
  require_once __DIR__ . '/../api_chat/vendor/autoload.php';
  if (class_exists('Dotenv\\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../api_chat');
    $dotenv->load();
  }

  $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
  $dbName = $_ENV['DB_NAME'] ?? 'LookEmploy';
  $dbUser = $_ENV['DB_USER'] ?? 'root';
  $dbPass = $_ENV['DB_PASS'] ?? '';
  $dbCharset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

  try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}", $dbUser, $dbPass, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
  } catch (Exception $e) {
    die("Falha na conexão: " . $e->getMessage());
  }
?>
