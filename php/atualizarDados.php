<?php
session_start();
include_once 'conexaoMySQL.php';

$tipo = $_SESSION['tipo'] ?? "";

$nome        = trim($_POST['nome'] ?? '');
$sobrenome   = trim($_POST['sobrenome'] ?? '');
$telefone    = trim($_POST['telefone'] ?? '');
$bairro      = trim($_POST['bairro'] ?? '');
$logradouro  = trim($_POST['logradouro'] ?? '');
$numero      = trim($_POST['numero'] ?? '');
$complemento = trim($_POST['complemento'] ?? '');
$descricao   = trim($_POST['descricao'] ?? '');

$servico = ($_SESSION['tipo'] === "Prestador") ? ($_POST['servico'] ?? '') : null;

$idUsuario = $_SESSION['usuario'] ?? null;

// ===== VALIDAR DADOS =====

if (!$idUsuario || !$tipo || !$nome || !$sobrenome || !$telefone || !$bairro || !$logradouro || !$numero || !$descricao) {
    echo "Dados incompletos.";
    exit;
}

if ($tipo === "Prestador" && !$servico) {
    echo "Serviço não informado.";
    exit;
}

// ===== UPLOAD =====

$caminhoImagemPerfil = $_SESSION['caminhoImagemPerfil']; // valor atual

if (isset($_FILES['imagemPerfil']) && $_FILES['imagemPerfil']['error'] === 0) {

    $arquivoTmp   = $_FILES['imagemPerfil']['tmp_name'];
    $nomeOriginal = $_FILES['imagemPerfil']['name'];

    $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

    $novoNome = uniqid("foto_", true) . "." . $extensao;

    $destino = "../img/img_perfil/" . $novoNome;

    if (move_uploaded_file($arquivoTmp, $destino)) {
        $caminhoImagemPerfil = $novoNome;
    } else {
        echo "Falha ao mover a imagem.";
        exit;
    }
}

// ===== SQL =====

if ($tipo === "Prestador") {
    $sql = "UPDATE Prestador SET nome=?, sobrenome=?, telefone=?, bairro=?, logradouro=?, numero=?, complemento=?, descricao=?, tipoServico=?, caminhoImagemPerfil=? WHERE ID=?";
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        $nome, $sobrenome, $telefone, $bairro, $logradouro, $numero,
        $complemento, $descricao, $servico, $caminhoImagemPerfil, $idUsuario
    ]);

} else {
    $sql = "UPDATE Cliente SET nome=?, sobrenome=?, telefone=?, bairro=?, logradouro=?, numero=?, complemento=?, descricao=?, caminhoImagemPerfil=? WHERE ID=?";
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        $nome, $sobrenome, $telefone, $bairro, $logradouro, $numero,
        $complemento, $descricao, $caminhoImagemPerfil, $idUsuario
    ]);
}

if ($ok) {

    $_SESSION['nome'] = $nome;
    $_SESSION['sobrenome'] = $sobrenome;
    $_SESSION['telefone'] = $telefone;
    $_SESSION['bairro'] = $bairro;
    $_SESSION['logradouro'] = $logradouro;
    $_SESSION['numero'] = $numero;
    $_SESSION['descricao'] = $descricao;
    $_SESSION['complemento'] = $complemento;
    $_SESSION['caminhoImagemPerfil'] = $caminhoImagemPerfil;

    if ($tipo === "Prestador") {
        $_SESSION['tipoServico'] = $servico;
    }

    echo "Dados atualizados com sucesso!";
} else {
    echo "Erro ao atualizar.";
}

$stmt = null;
$pdo = null;
