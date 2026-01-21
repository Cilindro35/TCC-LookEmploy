<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Autoload e .env
$autoloads = [
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php'
];
foreach ($autoloads as $autoload) { if (file_exists($autoload)) { require_once $autoload; } }
if (class_exists('Dotenv\\Dotenv')) { $dotenv = Dotenv\Dotenv::createImmutable(__DIR__); $dotenv->load(); }

$retentionDays = (int)($_ENV['UPLOAD_RETENTION_DAYS'] ?? $_SERVER['UPLOAD_RETENTION_DAYS'] ?? getenv('UPLOAD_RETENTION_DAYS') ?: 30);
$now = time();
$deleted = 0; $skipped = 0; $dirsRemoved = 0; $errors = [];

$root = dirname(__DIR__);
$base = $root . '/uploads/chat';
if (!is_dir($base)) { echo json_encode(['ok' => true, 'deleted' => 0, 'dirs_removed' => 0]); exit(); }

function safeUnlink($path, &$deleted, &$errors) {
    try {
        if (@unlink($path)) { $deleted++; } else { $errors[] = 'Falha ao remover: ' . $path; }
    } catch (\Throwable $e) { $errors[] = 'Erro ao remover ' . $path . ': ' . $e->getMessage(); }
}

// Percorrer pastas YYYY/MM/DD
$itYear = @scandir($base);
foreach ($itYear ?: [] as $year) {
    if ($year === '.' || $year === '..') continue;
    $yearPath = $base . '/' . $year;
    if (!preg_match('/^\d{4}$/', $year) || !is_dir($yearPath)) { $skipped++; continue; }

    $itMonth = @scandir($yearPath);
    foreach ($itMonth ?: [] as $month) {
        if ($month === '.' || $month === '..') continue;
        $monthPath = $yearPath . '/' . $month;
        if (!preg_match('/^(0[1-9]|1[0-2])$/', $month) || !is_dir($monthPath)) { $skipped++; continue; }

        $itDay = @scandir($monthPath);
        foreach ($itDay ?: [] as $day) {
            if ($day === '.' || $day === '..') continue;
            $dayPath = $monthPath . '/' . $day;
            if (!preg_match('/^(0[1-9]|[12][0-9]|3[01])$/', $day) || !is_dir($dayPath)) { $skipped++; continue; }

            $files = @scandir($dayPath);
            foreach ($files ?: [] as $f) {
                if ($f === '.' || $f === '..') continue;
                $full = $dayPath . '/' . $f;
                if (!is_file($full)) { $skipped++; continue; }
                $ageDays = ($now - @filemtime($full)) / 86400;
                if ($ageDays >= $retentionDays) {
                    safeUnlink($full, $deleted, $errors);
                }
            }
            // Remover diretório do dia se vazio
            @rmdir($dayPath) && $dirsRemoved++;
        }
        // Remover diretório do mês se vazio
        @rmdir($monthPath) && $dirsRemoved++;
    }
    // Remover diretório do ano se vazio
    @rmdir($yearPath) && $dirsRemoved++;
}

echo json_encode(['ok' => true, 'deleted' => $deleted, 'dirs_removed' => $dirsRemoved, 'skipped' => $skipped, 'retention_days' => $retentionDays, 'errors' => $errors]);
