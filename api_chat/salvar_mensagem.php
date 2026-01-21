<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// Autoload robusto
$autoloads = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php'
];
foreach ($autoloads as $autoload) {
    if (file_exists($autoload)) {
        require_once $autoload;
    }
}

use Api\Security\MessageEncryption;
use Api\Security\PermissionManager;

require "conectar.php";

$remetenteId = isset($_POST["remetente"]) ? (int)$_POST["remetente"] : 0;
$destinatarioId = isset($_POST["destinatario"]) ? (int)$_POST["destinatario"] : 0;
$mensagem = trim($_POST["mensagem"] ?? '');

if (!$remetenteId || !$destinatarioId || $mensagem === '') {
    echo json_encode(["error" => "Dados inválidos"]);
    exit();
}

try {
    $permissionManager = new PermissionManager($pdo);
    if (!$permissionManager->canCommunicate($remetenteId, $destinatarioId)) {
        echo json_encode(["error" => "Sem permissão para enviar mensagens"]);
        exit();
    }

    $encryption = new MessageEncryption();
    $mensagemCriptografada = $encryption->encrypt($mensagem);

    $sql = $pdo->prepare("\n        INSERT INTO mensagens (remetente_id, destinatario_id, mensagem, data_envio, entregue, lido)\n        VALUES (?, ?, ?, NOW(), 0, 0)\n    ");
    $sql->execute([$remetenteId, $destinatarioId, $mensagemCriptografada]);

    echo json_encode(["ok" => true]);

} catch (Exception $e) {
    echo json_encode(["error" => "Erro ao salvar mensagem"]);
}
