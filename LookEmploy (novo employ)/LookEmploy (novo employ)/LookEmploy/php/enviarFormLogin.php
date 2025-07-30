<?php
  include_once 'conexaoMySQL.php';

  //$tipo = $_POST['tipo'] ?? '';
  $tipo = 'cliente';//sempre vai buscar na tabela dos clientes.
  $senha = $_POST['senha'] ?? '';
  $email = $_POST['email'] ?? '';

  // Validação básica
  if ($tipo != '' || $senha != '' && $email != '') {
    
    //Comando de busca do email
    $sql = "SELECT * FROM $tipo WHERE email = ?";
    $stmt = $conexao->prepare($sql);

    if (!$stmt) {//EM CASO DE ERRO NO BANCO DE DADOS
        echo "Erro ao preparar o SQL: " . $conexao->error;
        exit;
    }

    // Associa o parâmetro (s = string) e executa a operação
    $stmt->bind_param("s", $email);
    $stmt->execute();

    //pega o resultado
    $resultado = $stmt->get_result();

    if ($resultado && $resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();

        // Verifica a senha (criptografada)
        if (password_verify($senha, $usuario['senha']) == true) {//SENHA CORRETA
            echo "Login realizado com sucesso!<br>";

            /*pode iniciar uma sessão contendo as informações do usuario. Por exemplo:
            session_start();
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            ...
            */
        }
        else {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);//SENHA INCORRETA
            echo "Senha incorreta";
        }
    } 
    else {
        echo "Usuário não encontrado.";
    }

    $stmt->close();
    $conexao->close();
  }
  else {
    echo("Erro: dados incompletos.");
    exit;
  }
?>
