<?php
// Iniciar a sessão
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    // Redirecionar para a página de login se não estiver logado
    header('location: login.html');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EditarPerfil</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">
    <link rel="stylesheet" href="css/design_editarPerfil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="img/logo_icon.png">
</head>
<body>
    <!--Menu lateral-->
    <div id="inserirMenuLateral"></div>

    <!--PERFIL-->
    <section class="perfil">

        <!-- Tela de confirmar exclusao da conta-->
        <div id="exibirExclusao">
            <div id="modalex">
                <div class="mocontem" id="contex">


                </div>

            </div>
        </div>
        <!--FOTO E OPÇÕES-->

        <section class="secao" id="fundo" style="background: linear-gradient(to top, white 45%, whitesmoke 45%);">
            <!--Botão de voltar-->
            <a href="perfil.php"><button>Voltar</button></a>

            <div class="informacoes">
                <!--FOTO E NOME-->
                <div class="inicio">
                    <img class="fotoPerfil" id="foto" src="<?php echo "img/img_perfil/" . rawurlencode($_SESSION['caminhoImagemPerfil']); ?>" alt="Foto de perfil">
                    <h1><?= htmlspecialchars(($_SESSION["nome"] ?? "") . " " . ($_SESSION["sobrenome"] ?? "")) ?></h1>
                </div>
            </div>
        </section>

        <hr style="margin: 16px 10% 16px 10%">

        <!--FORMULARIO DE EDIÇÃO-->
        <section class="secao">
            <form class="formulario" id="formulario" enctype="multipart/form-data">
                
                <!--envia o tipo de conta (nao aparece para o usuario)-->
                <input type="hidden" id="tipo" name="tipo" value="<?= $_SESSION['tipo']; ?>">

                <div class="col">
                    <!--Imagem de perfil-->
                    <button type="button" id="btnTrocar">Trocar Foto</button>
                    <input type="file" id="imagemPerfil" name="imagemPerfil"  accept="image/*" style="display: none;">
                    <span id="fotoInvalida" style="color: red;"></span>

                    <h2>Nome</h2>
                    <input type="text" id="nome" name="nome" placeholder="Nome" value="<?= htmlspecialchars($_SESSION['nome']); ?>"/>
                    <span id="nomeInvalido" style="color: red;"></span>
                    <input type="text" id="sobrenome" name="sobrenome" placeholder="Sobrenome" value="<?= htmlspecialchars($_SESSION['sobrenome']); ?>"/>
                    <span id="sobrenomeInvalido" style="color: red;"></span>

                    <h2>Endereço</h2>
                    <input type="text" id="bairro" name="bairro" placeholder="Bairro" value="<?= htmlspecialchars($_SESSION['bairro']); ?>"/>
                    <span id="bairroInvalido" style="color: red;"></span>
                    <input type="text" id="logradouro" name="logradouro" placeholder="Logradouro" value="<?= htmlspecialchars($_SESSION["logradouro"]); ?>"/>
                    <span id="logradouroInvalido" style="color: red;"></span>

                    <div class="raw">
                        <input type="text" id="numero" name="numero" placeholder="Número" value="<?= htmlspecialchars($_SESSION["numero"]); ?>"/>
                        <span id="numeroInvalido" style="color: red;"></span>
                        <input type="text" id="complemento" name="complemento" placeholder="Complemento" value="<?= htmlspecialchars($_SESSION["complemento"]); ?>"/>
                        <span id="complementoInvalido" style="color: red;"></span>
                    </div>

                    <h2>Telefone</h2>
                    <input type="text" id="telefone" name="telefone" placeholder="Celular" value="<?= htmlspecialchars($_SESSION['telefone']); ?>"/>
                    <span id="telefoneInvalido" style="color: red;"></span>

                    <h2>Descrição</h2>
                    <textarea style="resize: none;" rows="10" id="desc" name="desc" placeholder="Descrição"><?php if($_SESSION['descricao'] != null) { echo htmlspecialchars($_SESSION['descricao']); } ?></textarea>
                    <span id="descInvalida" style="color: red;"></span>

                    <h2 style="display: <?php echo ($_SESSION['tipo'] == 'Prestador') ? 'block' : 'none'; ?>;">Serviço</h2>
                    <div style="display: <?php echo ($_SESSION['tipo'] == 'Prestador') ? 'block' : 'none'; ?>;">
                        <label for="pedreiro">Pedreiro</label>
                        <input type="radio" id="pedreiro" name="servico" value="Pedreiro" <?= ($_SESSION['tipoServico'] == "Pedreiro") ? "checked" : ""; ?>/>

                        <label for="marceneiro">Marceneiro</label>
                        <input type="radio" id="marceneiro" name="servico" value="Marceneiro" <?= ($_SESSION['tipoServico'] == "Marceneiro") ? "checked" : ""; ?>/>

                        <label for="eletricista">Eletricista</label>
                        <input type="radio" id="eletricista" name="servico" value="Eletricista" <?= ($_SESSION['tipoServico'] == "Eletricista") ? "checked" : ""; ?>/>

                        <label for="encanador">Encanador</label>
                        <input type="radio" id="encanador" name="servico" value="Encanador" <?= ($_SESSION['tipoServico'] == "Encanador") ? "checked" : ""; ?>/>
                    </div>
                </div>
                <div class="column">
                    <!--Botão de salvar-->
                    <div class="row">
                        <button>Salvar</button>
                        <p id="msgSucesso"></p><br>
                    </div>
                </div>
            </form>
            <!--Excluir a conta-->
            <div class="excot">
            <button id="excluirConta" class="exbut">Excluir Conta</button>
            </div>
        </section>
    </section>
    <script src="js/atualizarDados.js" defer></script>
    <script src="js/menuLateral.js"></script>
    <script src="js/opcaoExtra.js"></script>
</body>
</html>
