<?php
  include_once 'conexaoMySQL.php';

  $tipo = $_POST['tipo'] ?? '';
  $senha = $_POST['senha'] ?? '';
  $email = $_POST['email'] ?? '';

  // Validação básica
  if ($tipo != '' && $senha != '' && $email != '') {

    //criptografia senha
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    //codigo de inserção do sql
    $sql = "INSERT INTO $tipo (email, senha) VALUES (?, ?)";
    $stmt = $conexao->prepare($sql);

    if ($stmt === false) {
        echo("Erro ao preparar comando" . $conexao->error);
        exit;
    }

    // Liga os parâmetros à query
    $stmt->bind_param("ss",  $email, $senhaHash);

    // Executa a query
    if ($stmt->execute()) {
        echo "Cadastro realizado com sucesso!";
    } 
    else {
        echo "Erro ao cadastrar: " . $stmt->error;
    }

    // Fecha o statement e conexão
    $stmt->close();
    $conexao->close();
  }
  else {
    echo("Erro: dados incompletos.");
    exit;
  }
?>
