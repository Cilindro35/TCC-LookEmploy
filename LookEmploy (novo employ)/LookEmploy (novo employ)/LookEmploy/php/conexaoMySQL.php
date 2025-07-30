<?php
  $servidor = "localhost";
  $user = "root";
  $senha = "";
  $banco = "LookEmploy";

  $conexao = mysqli_connect($servidor, $user, $senha, $banco);
  mysqli_set_charset($conexao, 'utf8');
  
  if(!$conexao) {
    die("Falha na conexão" . mysqli_connect_error());
  }
?>