<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Autoload e .env
$autoloads = [
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php'
];
foreach ($autoloads as $autoload) {
    if (file_exists($autoload)) { require_once $autoload; }
}
if (class_exists('Dotenv\\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'Não autenticado']);
    exit();
}

if (!isset($_FILES['arquivo']) || !is_uploaded_file($_FILES['arquivo']['tmp_name'])) {
    echo json_encode(['error' => 'Nenhum arquivo enviado']);
    exit();
}

$file = $_FILES['arquivo'];
$size = (int)$file['size'];
$tmp = $file['tmp_name'];
$origName = $file['name'];

// Limites
$maxImage = 8 * 1024 * 1024; // 8MB
$maxDoc = 16 * 1024 * 1024; // 16MB

// Detectar MIME real
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($tmp) ?: 'application/octet-stream';

$allowedImages = ['image/jpeg','image/png','image/gif','image/webp'];
$allowedDocs = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','text/plain'];

$isImage = in_array($mime, $allowedImages, true);
$isDoc = in_array($mime, $allowedDocs, true);

if (!$isImage && !$isDoc) {
    echo json_encode(['error' => 'Tipo de arquivo não permitido']);
    exit();
}

if ($isImage && $size > $maxImage) {
    echo json_encode(['error' => 'Imagem muito grande (máx 8MB)']);
    exit();
}
if ($isDoc && $size > $maxDoc) {
    echo json_encode(['error' => 'Documento muito grande (máx 16MB)']);
    exit();
}

// Diretório destino
$root = dirname(__DIR__); // .../LookEmploy
$subDir = 'uploads/chat/' . date('Y/m/d');
$destDir = $root . '/' . $subDir;
if (!is_dir($destDir)) {
    @mkdir($destDir, 0755, true);
}

// Nome seguro
$ext = pathinfo($origName, PATHINFO_EXTENSION);
$safeExt = preg_replace('/[^a-zA-Z0-9]+/', '', $ext);
$base = bin2hex(random_bytes(8));
$safeName = $base . ($safeExt ? ('.' . strtolower($safeExt)) : '');
$destPath = $destDir . '/' . $safeName;

if (!move_uploaded_file($tmp, $destPath)) {
    echo json_encode(['error' => 'Falha ao salvar arquivo']);
    exit();
}

// URL pública relativa
$rel = $subDir . '/' . $safeName;
$secret = $_ENV['JWT_SECRET'] ?? $_SERVER['JWT_SECRET'] ?? getenv('JWT_SECRET');
$exp = time() + 86400;
$sig = rtrim(strtr(base64_encode(hash_hmac('sha256', $rel.'|'.$exp, $secret, true)), '+/', '-_'), '=');
$signedUrl = 'api_chat/download_anexo.php?p=' . rawurlencode($rel) . '&e=' . $exp . '&s=' . $sig;

echo json_encode([
    'ok' => true,
    'meta' => [
        'nome' => $origName,
        'mime' => $mime,
        'tamanho' => $size,
        'url' => $signedUrl
    ]
]);
