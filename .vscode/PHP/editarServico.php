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
                $stmt = $conn->prepare("SELECT bairro, logradouro, numero, complemento, dataServico, tipoPagamento, prestador, cliente, contrato, descricao, assunto FROM Servico WHERE codigoServico = ?");
                $stmt->bind_param("s", $id);
                $stmt->execute();
                $result = $stmt->get_result();

                $row = $result->fetch_assoc();
                if ($row) {
                    $bairro = $row["bairro"];
                    $logradouro =  $row["logradouro"];
                    $numero = $row["numero"];
                    $complemento = $row["complemento"];

                    // Separar data/hora
                    $dt = new DateTime($row["dataServico"]);
                    $data = $dt->format("Y-m-d");
                    $hora = $dt->format("H:i");

                    $tipoPagamento = $row["tipoPagamento"];
                    $prestador = $row["prestador"];
                    $clienteId = $row["cliente"];
                    $statusContrato = $row["contrato"];
                    $descricaoServico = $row["descricao"];
                    $assunto = $row["assunto"] ?? '';
                }

                $isCliente = (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'Cliente');
                if ($isCliente) {
                    $stmt = $conn->prepare("SELECT nome, sobrenome, tipoServico, descricao, avaliacao, caminhoImagemPerfil, usuario_id FROM Prestador WHERE ID = ?");
                    $stmt->bind_param("s", $prestador);
                } else {
                    $stmt = $conn->prepare("SELECT nome, sobrenome, email, caminhoImagemPerfil, usuario_id FROM Cliente WHERE ID = ?");
                    $stmt->bind_param("s", $clienteId);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                if ($row) {
                    $nome = $row["nome"];
                    $sobrenome = $row["sobrenome"] ?? '';
                    $avaliacao = $row["avaliacao"] ?? 0;
                    $caminhoImagemPerfil = "img/img_perfil/" . ($row["caminhoImagemPerfil"] ?? 'default.png');
                    $usuarioIdAlvo = (int)($row["usuario_id"] ?? 0);
                    $servico = $row["tipoServico"] ?? '';
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
            <button onclick="window.location.href='pedidos.php'">Voltar</button>

            <div class="informacoes">
                <!--FOTO E NOME -->
                <div class="inicio" style="align-items: center">
                    <img class="fotoPerfil" src="<?= $caminhoImagemPerfil ?>" alt="Foto de perfil" style="width:100px; height:100px; object-fit:cover; border-radius:50%;">
                    <div class="column">
                        <!--NOME E DESCRIÇÃO-->
                        <h1><?= htmlspecialchars($nome) . " " . htmlspecialchars($sobrenome) ?></h1>

                        <!--avaliação-->
                        <?php if ($isCliente): ?>
                        <div class="avaliacao">
                            <?php for($i = 0; $i < $avaliacao; $i++) { echo "<i class='fa-solid fa-star' style='color: #5CE1E6;'></i>"; } ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:8px; margin-top:8px;">
                <button id="btnAceitar"><?= $isCliente ? 'Concluir serviço' : 'Aceitar serviço' ?></button>
                <button id="btnCancelar" style="background:#ff4d4f;color:#fff;">Cancelar serviço</button>
                <a id="btnContrato" href="api_chat/contrato_pdf.php?codigoServico=<?= (int)$id ?>" target="_blank"><button type="button">Baixar contrato</button></a>
                <button id="btnConversar" type="button">Conversar</button>
            </div>
        </section>

        <hr class="line">

        <!--FORMULARIO DE SERVICO-->
        <section class="secao">
            <?php $isCliente = (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'Cliente'); ?>
            <form class="formulario" id="formulario">
                
                <!--ID do prestador e cliente-->
                <input type="hidden" id="cliente" name="cliente" value="<?= (int)$clienteId ?>">
                <input type="hidden" id="prestador" name="prestador" value="<?= (int)$prestador ?>">
                <input type="hidden" id="codigoServico" value="<?= (int)$id ?>">
                <input type="hidden" id="statusContrato" value="<?= htmlspecialchars($statusContrato) ?>">

                <!-- ETAPA 1 - ENDEREÇO -->
                <div class="column" id="endereco">
                    <h2>Data de realização do serviço</h2>
                    <div>
                        <input type="date" id="data" name="data" value="<?= $data ?>" <?= $isCliente ? '' : 'disabled' ?>>
                        <span id="dataInvalida" style="color: red;"></span>

                        <input type="time" id="horario" name="horario" value="<?= $hora ?>" <?= $isCliente ? '' : 'disabled' ?>>
                        <span id="horarioInvalido" style="color: red;"></span>
                    </div>

                    <h2>Local de realização do serviço</h2>
                    <div>
                        <input type="text" id="bairro" name="bairro" placeholder="Bairro" value="<?= htmlspecialchars($bairro) ?>" <?= $isCliente ? '' : 'disabled' ?>>
                        <span id="bairroInvalido" style="color: red;"></span>

                        <input type="text" id="logradouro" name="logradouro" placeholder="Logradouro" value="<?= htmlspecialchars($logradouro) ?>" <?= $isCliente ? '' : 'disabled' ?>>
                        <span id="logradouroInvalido" style="color: red;"></span>

                        <input type="text" id="numero" name="numero" placeholder="Número" value="<?= htmlspecialchars($numero) ?>" <?= $isCliente ? '' : 'disabled' ?>>
                        <span id="numeroInvalido" style="color: red;"></span>

                        <input type="text" id="complemento" name="complemento" placeholder="Complemento" <?= $isCliente ? '' : 'disabled' ?> value="<?php
                         if(!empty($complemento)) {
                            echo htmlspecialchars($complemento);
                         }
                         ?>">
                        <span id="complementoInvalido" style="color: red;"></span>
                    </div>

                    <h2>Assunto</h2>
                    <input type="text" id="assunto" name="assunto" placeholder="Assunto do serviço" value="<?= htmlspecialchars($assunto ?? '') ?>" <?= $isCliente ? '' : 'disabled' ?>>
                    <span id="assuntoInvalido" style="color: red;"></span>

                    <h2>Descrição</h2>
                    
                    <textarea style="resize: none;" rows="4" id="desc" name="desc" placeholder="Descrição" <?= $isCliente ? '' : 'disabled' ?>><?php if(!empty($descricaoServico)) { echo htmlspecialchars($descricaoServico);} ?></textarea>
                    <span id="descInvalida" style="color: red;"></span>
                </div>

                <!-- ETAPA 2 - MÉTODO DE PAGAMENTO -->
                <div class="column" id="metodo" style="margin-top:20px;">
                    <h2>Método de pagamento</h2>
                    <div class="metodosPagamento">
                        <label>
                            <input type="radio" name="metodo" value="pix" <?php if($tipoPagamento == "pix")echo "checked"; ?> <?= $isCliente ? '' : 'disabled' ?>> PIX
                        </label>

                        <label>
                            <input type="radio" name="metodo" value="credito" <?php if($tipoPagamento == "credito")echo "checked"; ?> <?= $isCliente ? '' : 'disabled' ?>> Cartão de Crédito
                        </label>

                        <label>
                            <input type="radio" name="metodo" value="debito" <?php if($tipoPagamento == "debito")echo "checked"; ?> <?= $isCliente ? '' : 'disabled' ?>> Cartão de Débito
                        </label>

                        <label>
                            <input type="radio" name="metodo" value="dinheiro" <?php if($tipoPagamento == "dinheiro")echo "checked"; ?> <?= $isCliente ? '' : 'disabled' ?>> Dinheiro
                        </label>
                    </div>
                    <span id="pagamentoInvalido" style="color: red;"></span><br>

                    <?php if ($isCliente): ?>
                    <button type="submit" id="btnPagamento" class="btpaga" style=" scale: (0.2); width: (50px);">Atualizar</button>
                    <?php else: ?>
                    <p style="color:#666b7a;">Somente clientes podem editar os serviços.</p>
                    <?php endif; ?>
                    <p id="mensagemFinal" style="color:green;"></p>
                </div>
            </form>
        </section>
    </section>
    <script src="js/validarContratacao.js"></script>
    <script src="js/menuLateral.js"></script>
    <script src="js/avaliacao.js"></script>
    <script>
    (function(){
        const btnAceitar = document.getElementById('btnAceitar');
        const btnCancelar = document.getElementById('btnCancelar');
        const btnConversar = document.getElementById('btnConversar');
        const btnContrato = document.getElementById('btnContrato');
        const statusInicial = document.getElementById('statusContrato').value;
        const isCliente = <?= $isCliente ? 'true' : 'false' ?>;
        (function(){
            const enableContratoStatuses = ['andamento','concluido'];
            if (!enableContratoStatuses.includes(statusInicial)) {
                btnContrato.style.pointerEvents = 'none';
                btnContrato.style.opacity = '0.6';
            }
            if (isCliente && statusInicial !== 'andamento') {
                btnAceitar.disabled = true;
                btnAceitar.style.opacity = '0.6';
                btnAceitar.style.cursor = 'not-allowed';
            }
            if (statusInicial === 'concluido' || statusInicial === 'cancelado') {
                btnAceitar.disabled = true;
                btnAceitar.style.opacity = '0.6';
                btnAceitar.style.cursor = 'not-allowed';
                btnCancelar.disabled = true;
                btnCancelar.style.opacity = '0.6';
                btnCancelar.style.cursor = 'not-allowed';
                document.getElementById('data').disabled = true;
                document.getElementById('horario').disabled = true;
                document.getElementById('bairro').disabled = true;
                document.getElementById('logradouro').disabled = true;
                document.getElementById('numero').disabled = true;
                document.getElementById('complemento').disabled = true;
                document.querySelectorAll('input[name="metodo"]').forEach(el => el.disabled = true);
                const btnPag = document.getElementById('btnPagamento');
                if (btnPag) { btnPag.disabled = true; btnPag.style.opacity = '0.6'; btnPag.style.cursor = 'not-allowed'; }
            }
        })();
        btnAceitar.addEventListener('click', async function(){
            try {
                if (isCliente) {
                    if (statusInicial !== 'andamento') {
                        alert('Aguarde o prestador aceitar o trabalho.');
                        return;
                    }
                    const confirmed = confirm('Tem certeza que deseja finalizar o serviço?');
                    if (!confirmed) return;
                }
                const codigo = document.getElementById('codigoServico').value;
                const fd = new FormData();
                fd.append('codigoServico', codigo);
                const res = await fetch('api_chat/aceitar_contrato.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                if (data.ok) {
                    if (data.contrato === 'andamento' || data.contrato === 'concluido') {
                        btnContrato.style.pointerEvents = 'auto';
                        btnContrato.style.opacity = '1';
                    }
                    alert('Status do contrato: ' + data.contrato);
                    if (!isCliente) {
                        if (data.contrato === 'aceito_prestador' || data.contrato === 'andamento') {
                            btnAceitar.disabled = true;
                            btnAceitar.style.opacity = '0.6';
                            btnAceitar.style.cursor = 'not-allowed';
                        }
                    }
                    if (isCliente && data.contrato === 'concluido') {
                        const prestadorId = parseInt(document.getElementById('prestador').value);
                        const codigoServico = parseInt(document.getElementById('codigoServico').value);
                        if (prestadorId && codigoServico) {
                            abrirModalAvaliacao(prestadorId, codigoServico);
                        }
                    }
                } else {
                    alert(data.error || 'Erro ao aceitar');
                }
            } catch(e){ alert('Erro de rede'); }
        });

        btnCancelar.addEventListener('click', async function(){
            try {
                const codigo = document.getElementById('codigoServico').value;
                const fd = new FormData();
                fd.append('codigoServico', codigo);
                const res = await fetch('api_chat/cancelar_contrato.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                if (data.ok) {
                    alert('Serviço cancelado.' + (data.reembolsoElegivel ? ' Reembolso elegível.' : ''));
                    btnContrato.style.pointerEvents = 'none';
                    btnContrato.style.opacity = '0.6';
                } else {
                    alert(data.error || 'Erro ao cancelar');
                }
            } catch(e){ alert('Erro de rede'); }
        });

        btnConversar.addEventListener('click', async function(){
            try {
                const codigo = document.getElementById('codigoServico').value;
                const fd = new FormData();
                fd.append('codigoServico', codigo);
                const res = await fetch('api_chat/garantir_contato.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                if (data && data.ok && data.openId) {
                    window.location.href = 'contatos.php?open=' + encodeURIComponent(data.openId);
                } else {
                    alert(data.error || 'Não foi possível abrir o chat. Crie um pedido primeiro.');
                }
            } catch(e){ alert('Erro de rede'); }
        });
    })();
    </script>
</body>
</html>
