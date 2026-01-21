<?php
  include_once 'conexaoMySQL.php';

$tipo = $_POST['tipo'] ?? '';
$senha = $_POST['senha'] ?? '';
$email = $_POST['email'] ?? '';

// Validação básica
$allowed_tables = ['Cliente', 'Prestador'];
if (!in_array($tipo, $allowed_tables, true)) {
    echo "Tipo de conta inválido.";
    exit;
}

if ($senha != '' && $email != '') {
    
    //Comando de busca do email
    $sql = "SELECT * FROM {$tipo} WHERE email = ?";
    $stmt = $pdo->prepare($sql);

    if (!$stmt) {
        echo "Erro ao preparar o SQL";
        exit;
    }

    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {

        // Verifica a senha (criptografada)
        if (password_verify($senha, $usuario['senha']) == true) {
            $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
            session_regenerate_id(true);
            $_SESSION['tipo'] = $tipo;
            $_SESSION['usuario'] = $usuario['ID'];
            $_SESSION['nome'] = $usuario['nome'];
            $_SESSION['sobrenome'] = $usuario['sobrenome'];
            $_SESSION['dataNascimento'] = $usuario['dataNascimento'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['telefone'] = $usuario['telefone'];
            $_SESSION['descricao'] = $usuario['descricao'];
            $_SESSION['bairro'] = $usuario['bairro'];
            $_SESSION['logradouro'] = $usuario['logradouro'];
            $_SESSION['numero'] = $usuario['numero'];
            $_SESSION['complemento'] = $usuario['complemento'];
            $_SESSION['sexo'] = $usuario['sexo'];
            $_SESSION['descricao'] = $usuario['descricao'];
            if($tipo == 'Prestador'){
              $_SESSION['tipoServico'] = $usuario['tipoServico']; 
              $_SESSION['avaliacao'] = $usuario['avaliacao'];
            }
            $_SESSION['caminhoImagemPerfil'] = $usuario['caminhoImagemPerfil'];

            echo "EXITO";
        }
        else {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);//SENHA INCORRETA
            echo "Senha incorreta";        }
    } 
    else {
        echo "Usuário não encontrado.";
    }

    $stmt = null;
    $pdo = null;
  }
  else {
    echo("Erro: dados incompletos.");
    exit;
  }
?>
