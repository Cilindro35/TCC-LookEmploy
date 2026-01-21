<?php
    // Iniciar a sessão
    session_start();

    // Verificar se o usuário está logado
    if (!isset($_SESSION['usuario'])) {
        // Redirecionar para a página de login se não estiver logado
        header('location: login.html');
        exit();
    }
    else {
        //pegando o id da URL
        $id = $_GET['id'] ?? 1;

        if(is_numeric($id)) {
            //buscando os dados do prestador no banco de dados
            $conn = new mysqli('localhost', 'root', '', 'lookemploy');
            if ($conn->connect_error) {
                die("Erro de conexão: " . $conn->connect_error);
            }
            else {
                $stmt = $conn->prepare("SELECT nome, sobrenome, tipoServico,descricao, avaliacao, caminhoImagemPerfil FROM Prestador WHERE ID = ?");
                $stmt->bind_param("s", $id);
                $stmt->execute();
                $result = $stmt->get_result();

                $row = $result->fetch_assoc();
                if ($row) {
                    $nome = $row["nome"];
                    $sobrenome = $row["sobrenome"];
                    $servico =  $row["tipoServico"];
                    $descricao = $row["descricao"];
                    $avaliacao = $row["avaliacao"];
                    $caminhoImagemPerfil = "img/img_perfil/" . $row["caminhoImagemPerfil"];

                }
            }
        }
        else exit();
    }                    
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EditarPerfil</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">
    <link rel="stylesheet" href="css/design_contratarServico.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="img/logo_icon.png">
</head>
<body>
    <!--Menu lateral-->
    <div id="inserirMenuLateral"></div>

    <!--PERFIL-->
    <section class="pedido">
        <!--FOTO E OPÇÕES-->
        <section class="secao">
            <div class="informacoes">
                <!--FOTO E NOME -->
                <div class="inicio" style="align-items: center">
                    <img class="fotoPerfil" src="<?= $caminhoImagemPerfil ?>" alt="Foto de perfil" style="width:100px; height:100px; object-fit:cover; border-radius:50%;">
                    <div class="column">
                        <!--NOME E DESCRIÇÃO-->
                        <h1><?= htmlspecialchars($nome) . " ". htmlspecialchars($sobrenome)?></h1>

                        <!--avaliação-->
                        <div class="avaliacao">
                            <?php
                                for($i = 0; $i < $avaliacao; $i++) {
                                    echo "<i class='fa-solid fa-star' style='color: #5CE1E6;'></i>";
                                }
                            ?>  
                        </div>
                    </div>
                </div>
                <button onclick=<?="window.location.href='visualizarPrestador.php?id=$id'"?>>Cancelar</button>
            </div>
        </section>

        <hr class="line">

        <!--FORMULARIO DE SERVICO-->
        <section class="secao">
            <form class="formulario" id="formulario">
                
                <!--ID do prestador e cliente-->
                <input type="hidden" id="cliente" name="cliente" value="<?= $_SESSION['usuario'] ?>">
                <input type="hidden" id="prestador" name="prestador" value="<?= $id ?>">

                <!-- ETAPA 1 - ENDEREÇO -->
                <div class="column" id="endereco">
                    <h2>Data de realização do serviço</h2>
                    <div>
                        <input type="date" id="data" name="data">
                        <span id="dataInvalida" style="color: red;"></span>

                        <input type="time" id="horario" name="horario">
                        <span id="horarioInvalido" style="color: red;"></span>
                    </div>

                    <h2>Local de realização do serviço</h2>

                    <input type="text" id="bairro" name="bairro" placeholder="Bairro" value="<?= htmlspecialchars($_SESSION['bairro']) ?>">
                    <span id="bairroInvalido" style="color: red;"></span>

                    <input type="text" id="logradouro" name="logradouro" placeholder="Logradouro" value="<?= htmlspecialchars($_SESSION['logradouro']) ?>">
                    <span id="logradouroInvalido" style="color: red;"></span>

                    <div>
                        <input type="text" id="numero" name="numero" placeholder="Número" value="<?= htmlspecialchars( $_SESSION['numero']) ?>">
                        <span id="numeroInvalido" style="color: red;"></span>

                        <input type="text" id="complemento" name="complemento" placeholder="Complemento" value="<?php
                         if(!empty($_SESSION['complemento'])) {
                            echo htmlspecialchars($_SESSION['complemento']);
                         }
                         ?>">
                        <span id="complementoInvalido" style="color: red;"></span>
                    </div>

                    <h2>Assunto</h2>
                    <input type="text" id="assunto" name="assunto" placeholder="Assunto do serviço">
                    <span id="assuntoInvalido" style="color: red;"></span>

                    <h2>Descrição</h2>
                    <textarea style="resize: none;" rows="4" id="desc" name="desc" placeholder="Descrição"><?php if($_SESSION['tipo'] == "Cliente" && !empty($_SESSION['descricao'])) { echo htmlspecialchars($_SESSION['descricao']);} ?></textarea>
                    <span id="descInvalida" style="color: red;"></span>

                </div>

                <!-- ETAPA 2 - MÉTODO DE PAGAMENTO -->
                <div class="column" id="metodo" style="margin-top:20px;">
                    <h2>Método de pagamento</h2>
                    <div class="metodosPagamento">
                        <label>
                            <input type="radio" name="metodo" value="pix"> PIX
                        </label>

                        <label>
                            <input type="radio" name="metodo" value="credito"> Cartão de Crédito
                        </label>

                        <label>
                            <input type="radio" name="metodo" value="debito"> Cartão de Débito
                        </label>

                        <label>
                            <input type="radio" name="metodo" value="dinheiro"> Dinheiro
                        </label>
                    </div>
                    <span id="pagamentoInvalido" style="color: red;"></span><br>

                    <button type="submit" id="btnPagamento">Concluir</button>
                    <p id="mensagemFinal" style="color:green;"></p>
                </div>
            </form>
        </section>
    </section>
    <script src="js/validarContratacao.js"></script>
    <script src="js/menuLateral.js"></script>
</body>
</html>
