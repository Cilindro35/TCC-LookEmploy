<?php
  session_start();
  include_once 'conexaoMySQL.php';

  $tipo = $_POST['tipo'] ?? '';
  $email = $_POST['email'] ?? '';
  $senha = $_POST['senha'] ?? '';
  $nome = $_POST['nome'] ?? '';
  $sobrenome = $_POST['sobrenome'] ?? '';
  $telefone = $_POST['telefone'] ?? '';
  $data_nascimento = $_POST['dataNascimento'] ?? '';
  $bairro = $_POST['bairro'] ?? '';
  $logradouro = $_POST['logradouro'] ?? '';
  $numero = $_POST['numero'] ?? '';
  $complemento = $_POST['complemento'] ?? '';
  $sexo = $_POST['sexo'] ?? '';
  if($tipo == "Prestador") { 
    $servico = $_POST['servico'] ?? ''; 
    $descricao = $_POST['descricao'] ?? '';
  }

  function test_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
  }

  $tipo = test_input($tipo);
  $email = test_input($email);
  $senha = test_input($senha);
  $nome = test_input($nome);
  $sobrenome = test_input($sobrenome);
  $telefone = test_input($telefone);
  $data_nascimento = test_input($data_nascimento);
  $bairro = test_input($bairro);
  $logradouro = test_input($logradouro);
  $numero = test_input($numero);
  $complemento = test_input($complemento);
  $sexo = test_input($sexo);
  if(isset($descricao)) { $descricao = test_input($descricao); }

  $erros = [];
  if (empty($tipo)) $erros[] = 'tipo';
  if (empty($nome)) $erros[] = 'nome';
  if (empty($sobrenome)) $erros[] = 'sobrenome';
  if (empty($email)) $erros[] = 'email';
  if (empty($senha)) $erros[] = 'senha';
  if (empty($telefone)) $erros[] = 'telefone';
  if (empty($data_nascimento)) $erros[] = 'dataNascimento';
  if (empty($bairro)) $erros[] = 'bairro';
  if (empty($logradouro)) $erros[] = 'logradouro';
  if (empty($numero)) $erros[] = 'numero';
  if (empty($sexo)) $erros[] = 'sexo';
  if ($tipo === 'Prestador' && (empty($servico))) $erros[] = 'servico';
  if ($tipo === 'Prestador' && (empty($descricao))) $erros[] = 'descricao';

  if (!empty($erros)) {
    echo 'Erro: dados incompletos (' . implode(', ', $erros) . ').';
    exit;
  }

  $allowed_tables = ['Cliente', 'Prestador'];
  if (!in_array($tipo, $allowed_tables, true)) {
    echo'Tipo de conta inválido.';
    exit;
  }

  // Normalizar telefone (apenas dígitos)
  $telefone = preg_replace('/\D+/', '', $telefone);
  $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

  $sql_check = "SELECT email FROM {$tipo} WHERE email = ? LIMIT 1";
  if (!$pdo) {
    echo "Erro de conexão com o banco.";
    exit;
  }
  $stmt = $pdo->prepare($sql_check);
  if ($stmt === false) {
    echo"Erro no servidor.";
    exit;
  }
  if (!$stmt->execute([$email])) {
    echo'Erro ao verificar email';
    $stmt = null;
    $pdo = null;
    exit;
  }
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($result) {
    echo "Uma conta com este e-mail já existe. Utilize outro e-mail.";
    $stmt = null;
    $pdo = null;
    exit;
  }
  $stmt = null;
  $other = $tipo === 'Prestador' ? 'Cliente' : 'Prestador';
  $sql_other = "SELECT nome, sobrenome, email, telefone, dataNascimento, bairro, logradouro, numero, complemento, sexo, descricao FROM {$other} WHERE email = ? LIMIT 1";
  $stmt = $pdo->prepare($sql_other);
  if ($stmt) {
    if ($stmt->execute([$email])) {
      $rowOther = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($rowOther) {
        $telOther = preg_replace('/\D+/', '', $rowOther['telefone'] ?? '');
        $fieldsEqual = (
          ($rowOther['nome'] ?? '') === $nome &&
          ($rowOther['sobrenome'] ?? '') === $sobrenome &&
          $telOther === $telefone &&
          ($rowOther['dataNascimento'] ?? '') === $data_nascimento &&
          ($rowOther['bairro'] ?? '') === $bairro &&
          ($rowOther['logradouro'] ?? '') === $logradouro &&
          ($rowOther['numero'] ?? '') === $numero &&
          (string)($rowOther['complemento'] ?? '') === (string)$complemento &&
          ($rowOther['sexo'] ?? '') === $sexo
        );
        if (!$fieldsEqual) {
          echo 'E-mail já usado em outra conta com dados diferentes. Para usar o mesmo e-mail, mantenha os dados iguais (exceto serviço e descrição).';
          $stmt = null;
          $pdo = null;
          exit;
        }
      }
    }
    $stmt = null;
  }

  switch($sexo) {
    case "Masculino":
    $caminhoImagemPerfil = "img_icone_masculino.png";
    break;
    case "Feminino":
    $caminhoImagemPerfil = "img_icone_feminino.png";
    break;
    default:
    $caminhoImagemPerfil = "img_icone_neutro.png";
  }

  switch ($tipo) {
    case 'Prestador':
      if ($complemento === '') {
        $sql_insert = "INSERT INTO {$tipo} (nome, sobrenome, email, senha, telefone, dataNascimento, bairro, logradouro, numero, sexo, tipoServico, descricao, caminhoImagemPerfil) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql_insert);
        if ($stmt === false) {
          echo'Erro no servidor (prepare insert).';
          
          exit;
        }
        $ok = $stmt->execute([$nome, $sobrenome, $email, $senhaHash, $telefone, $data_nascimento, $bairro, $logradouro, $numero, $sexo, $servico, $descricao, $caminhoImagemPerfil]);
      } else {
        $sql_insert = "INSERT INTO {$tipo} (nome, sobrenome, email, senha, telefone, dataNascimento, bairro, logradouro, numero, complemento, sexo, tipoServico, descricao, caminhoImagemPerfil) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql_insert);
        if ($stmt === false) {
          echo'Erro no servidor (prepare insert).';
          
          exit;
        }
        $ok = $stmt->execute([$nome, $sobrenome, $email, $senhaHash, $telefone, $data_nascimento, $bairro, $logradouro, $numero, $complemento, $sexo, $servico, $descricao, $caminhoImagemPerfil]);
      }
      break;
    default:
      if ($complemento === '') {
        $sql_insert = "INSERT INTO {$tipo} (nome, sobrenome, email, senha, telefone, dataNascimento, bairro, logradouro, numero, sexo, caminhoImagemPerfil) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql_insert);
        if ($stmt === false) {
          echo'Erro no servidor (prepare insert).';
          
          exit;
        }
        $ok = $stmt->execute([$nome, $sobrenome, $email, $senhaHash, $telefone, $data_nascimento, $bairro, $logradouro, $numero, $sexo, $caminhoImagemPerfil]);
      } else {
        $sql_insert = "INSERT INTO {$tipo} (nome, sobrenome, email, senha, telefone, dataNascimento, bairro, logradouro, numero, complemento, sexo, caminhoImagemPerfil) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql_insert);
        if ($stmt === false) {
          echo'Erro no servidor (prepare insert).';
          
          exit;
        }
        $ok = $stmt->execute([$nome, $sobrenome, $email, $senhaHash, $telefone, $data_nascimento, $bairro, $logradouro, $numero, $complemento, $sexo, $caminhoImagemPerfil]);
      }
      break;
  }
  if ($ok ?? false) {
      $stmt = null;
      $sql = "SELECT * FROM {$tipo} WHERE email = ?";
      $stmt = $pdo->prepare($sql);

    if (!$stmt) {
        echo "Erro ao logar";
        exit;
    }

    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {

            $_SESSION['tipo'] = $tipo;
            $_SESSION['usuario'] = $usuario['ID'];
            $_SESSION['nome'] = $usuario['nome'];
            $_SESSION['sobrenome'] = $usuario['sobrenome'];
            $_SESSION['dataNascimento'] = $usuario['dataNascimento'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['telefone'] = $usuario['telefone'];
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
            $stmt = null;
            $pdo = null;

      } else {
        echo'Erro ao cadastrar! Tente novamente.';
        $stmt = null;
        $pdo = null;
        exit;
      }
  }
?>
