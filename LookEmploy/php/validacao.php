<?php
  function test_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
  }

  $nome = $sobrenome = $email = $mensagem = "";
  $nomeErr = $sobrenomeErr = $emailErr = $mensagemErr = "";

  if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty($_POST["nome"])) {
      $nomeErr = "Nome é obrigatório";
    } else {
      $nome = test_input($_POST["nome"]);
      if (!preg_match("/^[a-zA-ZÀ-ÿ\s]+$/", $nome)) {
        $nomeErr = "Apenas letras e espaços são permitidos";
      }
    }

    if (empty($_POST["sobrenome"])) {
      $sobrenomeErr = "Sobrenome é obrigatório";
    } else {
      $sobrenome = test_input($_POST["sobrenome"]);
      if (!preg_match("/^[a-zA-ZÀ-ÿ\s]+$/", $sobrenome)) {
        $sobrenomeErr = "Apenas letras e espaços são permitidos";
      }
    }

    if (empty($_POST["email"])) {
      $emailErr = "E-mail é obrigatório";
    } else {
      $email = test_input($_POST["email"]);
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Formato de e-mail inválido";
      }
    }

    if (empty($_POST["mensagem"])) {
      $mensagemErr = "Mensagem é obrigatória";
    } else {
      $mensagem = test_input($_POST["mensagem"]);
    }

    if (empty($nomeErr) && empty($sobrenomeErr) && empty($emailErr) && empty($mensagemErr)) {
      // Aqui você pode salvar no banco, enviar por e-mail, etc.
      echo "<h3 style='color:green;'>Mensagem enviada com sucesso!</h3>";
    } else {
      echo "<h3 style='color:rsed;'>Erro no envio do formulário. Verifique os campos.</h3>";
    }
  }
?>
