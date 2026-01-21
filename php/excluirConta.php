<?php

include_once 'conexaoMySQL.php';
session_start();

// Verificar login
if (!isset($_SESSION['usuario'])) {
    header('location: ../login.html');
    exit();
}

$tipo = $_SESSION['tipo'];
$ID = $_SESSION['usuario'];

$allowed_tables = ['Cliente', 'Prestador'];
if (!in_array($tipo, $allowed_tables, true)) {
    echo "<script>alert('Tipo de conta inválido.'); window.location.replace('../login.html');</script>";
    exit;
}

$sql = "DELETE FROM {$tipo} WHERE ID = ?";
$stmt = $pdo->prepare($sql);

if (!$stmt) {
    echo "<script>alert('Erro ao preparar comando SQL!');</script>";
    exit;
}

$ok = $stmt->execute([$ID]);

// Execução
if ($ok) {
    echo "<script>
            alert('Conta deletada.');
            window.location.replace('../login.html');
          </script>";
    exit;
} else {
    echo "<script>
            alert('Erro ao deletar! Tente novamente.');
            window.location.replace('../login.html');
          </script>";
    exit;
}

$stmt = null;
$pdo = null;
?>
