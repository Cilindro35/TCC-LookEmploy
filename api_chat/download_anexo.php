<?php
session_start();

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

$p = isset($_GET['p']) ? $_GET['p'] : '';
$e = isset($_GET['e']) ? (int)$_GET['e'] : 0;
$s = isset($_GET['s']) ? $_GET['s'] : '';

if (!$p || !$e || !$s) { http_response_code(400); exit(); }
if ($e < time()) { http_response_code(403); exit(); }

$secret = $_ENV['JWT_SECRET'] ?? $_SERVER['JWT_SECRET'] ?? getenv('JWT_SECRET');
if (!$secret) { http_response_code(500); exit(); }

$calc = rtrim(strtr(base64_encode(hash_hmac('sha256', $p.'|'.$e, $secret, true)), '+/', '-_'), '=');
if (!hash_equals($calc, $s)) { http_response_code(403); exit(); }

if (strpos($p, '..') !== false) { http_response_code(400); exit(); }
if (strpos($p, 'uploads/chat/') !== 0) { http_response_code(400); exit(); }

$root = dirname(__DIR__);
$full = $root . '/' . $p;
if (!is_file($full)) { http_response_code(404); exit(); }

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($full) ?: 'application/octet-stream';
header('Content-Type: '.$mime);

$disp = (strpos($mime, 'image/') === 0) ? 'inline' : 'attachment';
$fname = basename($full);
header('Content-Disposition: '.$disp.'; filename="'.rawurlencode($fname).'"');
header('Content-Length: '.filesize($full));
readfile($full);
exit();

