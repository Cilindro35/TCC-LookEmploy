<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('location: login.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contatos - Chat</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="css/design_contatos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="img/logo_icon.png">
</head>
<body>

<div id="inserirMenuLateral"></div>

<main class="contatos-container">

    <!-- COLUNA ESQUERDA - CONTATOS -->
    <aside class="coluna-contatos" aria-label="Lista de contatos">

        <div class="topo-contatos">
            <div class="campo-busca">
                <i class="fa fa-search"></i>
                <input type="text" id="buscarContato" placeholder="Buscar..." autocomplete="off">
            </div>
        </div>

        <ul id="listaUsuarios" class="lista-usuarios">
            <!-- Contatos serão carregados dinamicamente -->
        </ul>

    </aside>

    <!-- COLUNA DIREITA - CHAT -->
    <section class="coluna-chat" aria-label="Área de conversas">
        <div class="topo-chat">
            <div id="nomeContatoAtual" class="nome-topo">Selecione um contato</div>
            <div id="digitandoArea" class="digitando"></div>
        </div>

        <div id="chatMensagens" class="chat-mensagens">
            <!-- Mensagens serão carregadas aqui -->
        </div>

        <div class="input-chat">
            <button id="btnAnexo" class="btn-anexo" title="Anexar arquivo">
                <i class="fa-solid fa-paperclip"></i>
            </button>
            <input type="text" id="mensagem" placeholder="Digite a mensagem..." autocomplete="off">
            <button id="btnEnviar" class="btn-enviar" title="Enviar mensagem">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
            <input type="file" id="fileAnexo" style="display:none" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
        </div>

        <div id="imageModal" class="image-modal" style="display:none;">
            <div class="image-modal-backdrop"></div>
            <div class="image-modal-content">
                <button class="image-modal-close" aria-label="Fechar">&times;</button>
                <div class="image-modal-toolbar">
                    <button id="zoomIn" title="Ampliar">+</button>
                    <button id="zoomOut" title="Reduzir">-</button>
                    <button id="zoomReset" title="Resetar">Reset</button>
                </div>
                <div class="image-modal-stage">
                    <img id="modalImg" alt="Imagem" />
                </div>
            </div>
        </div>
    </section>

</main>

<script>
// Definir dados do usuário atual
const CURRENT_USER = {
    id: <?= isset($_SESSION['usuario']) ? (int)$_SESSION['usuario'] : 0 ?>,
    tipo: "<?= isset($_SESSION['tipo']) ? htmlspecialchars($_SESSION['tipo'], ENT_QUOTES, 'UTF-8') : '' ?>",
    nome: "<?= isset($_SESSION['nome']) ? htmlspecialchars($_SESSION['nome'], ENT_QUOTES, 'UTF-8') : '' ?>"
};

console.log('Usuário atual:', CURRENT_USER);
</script>

<script src="js/menuLateral.js"></script>
<script src="js/contatos_seguro.js"></script>

</body>
</html>
