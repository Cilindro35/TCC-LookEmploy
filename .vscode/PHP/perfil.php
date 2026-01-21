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
    <title>Perfil</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">
    <link rel="stylesheet" href="css/design_perfil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="img/logo_icon.png">
</head>
<body>
    <!--Menu lateral-->
    <div id="inserirMenuLateral"></div>

    <!--PERFIL-->    
    <section class="perfil">
        <!--FOTO E OPÇÕES-->
        <section class="secao" style="background: linear-gradient(to top, white 45%, whitesmoke 45%);">
            <div class="informacoes">
                <!--FOTO E NOME-->
                <div class="inicio">
                    <img class="fotoPerfil" src="
                    <?php 
                        echo "img/img_perfil/" . rawurlencode($_SESSION['caminhoImagemPerfil']);
                    ?>" alt="Foto de perfil">
                    <div class="column">
                        <h1><?= htmlspecialchars(($_SESSION["nome"] ?? "") . " " . ($_SESSION["sobrenome"] ?? "")) ?></h1>
 
                            <div class='row'>
                                <?php
                                if($_SESSION['tipo'] == 'Prestador'){
                                    if($_SESSION['avaliacao'] == null) {
                                        echo "Ainda não há avaliações suas.";
                                    } else {
                                        for($i = 0; $i < $_SESSION['avaliacao']; $i++) {
                                        echo"<i class='fa-solid fa-star' style='color: #5CE1E6;'></i>";
                                        }
                                    }
                                }
                                ?>
                            </div>
                    </div>
                </div>

                <!--BOTÕES-->

                <div class="row">
                    <button style="display: <?php if($_SESSION['tipo'] == "Prestador") { 
                        echo "block";
                     } else {
                        echo "none";
                     }
                      ?>">Avaliações</button>
                    <a href="editarPerfil.php"><button>Editar perfil</button></a>
                    <a href="php/realizarLogout.php"><button>Sair da conta</button></a>
                    <button id="btnBloqueados">Bloqueados</button>
                </div>
            </div>
        </section>

        <!--TIPO DE SERVIÇO-->
        <section class="secao">
            <?php
            if($_SESSION['tipo'] == "Prestador") {
                if (isset($_SESSION["tipoServico"])) {
                    if($_SESSION['tipoServico'] != "")echo "<h3>". htmlspecialchars($_SESSION["tipoServico"])."</h3>"; 
                    }
                }
            ?>
        </section>
        <hr class="line">
        
        <!--DESCRIÇÃO E NECESSIDADE-->
        <section class="secao">
            <div class="descricao">
                <h1>Descrição</h1>
                <?php 
                    if($_SESSION['descricao'] == null) {
                        if($_SESSION['tipo'] == "Prestador") {
                            echo "<p style='color: red;'>Adicione uma descrição sobre você</p>";
                        } else {
                            echo "<p style='color: red;'>Adicione uma descrição sobre o serviço que você necessita</p>";
                        }
                    } else {
                        echo "<p>" . nl2br(htmlspecialchars($_SESSION['descricao'])) . "</p>";
                    }
                ?>
            </div>
        </section>

        <!--ENDEREÇO-->
        <section class="secao">
            <h1>Endereço</h1>
            <div class="endereco">
                <div class="item"><h3>Bairro</h3>
                    <p><?= htmlspecialchars($_SESSION['bairro']) ?></p>
                </div>
                <div class="item"><h3>Logradouro</h3>
                    <p><?= htmlspecialchars($_SESSION['logradouro']) ?></p>
                </div>
                <div class="item"><h3>Numero</h3>
                    <p><?= htmlspecialchars($_SESSION['numero']) ?></p>
                </div>
                <div class="item"><h3>Complemento</h3>
                    <p>
                        <?php 
                            if($_SESSION['complemento'] != "") {
                                echo htmlspecialchars($_SESSION['complemento']);
                            }
                        ?>
                    </p>
                </div>
            </div>
        </section>

        <!--DADOS DA CONTA-->
        <section class="secao">
            <h1>Dados</h1>
            <div class="dados">
                <div class="item"><h3>Código ID</h3>
                    <p><?= htmlspecialchars($_SESSION['usuario']) ?></p>
                </div>
                <div class="item"><h3>Data de nascimento</h3>
                    <p><?= htmlspecialchars($_SESSION['dataNascimento']) ?></p>
                </div>
                <div class="item"><h3>E-mail</h3>
                    <p><?= htmlspecialchars($_SESSION['email']) ?></p>
                </div>
                <div class="item"><h3>Telefone</h3>
                    <p><?= htmlspecialchars($_SESSION['telefone']) ?></p>
                </div>
            </div>
        </section>

        <div id="modalBloqueados" style="position:fixed; inset:0; background:rgba(0,0,0,0.45); display:none; align-items:center; justify-content:center; z-index:2000;">
            <div style="background:#fff; padding:16px; border-radius:8px; width:90%; max-width:600px; box-shadow:0 8px 24px rgba(0,0,0,0.2);">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                    <h2 style="margin:0;">Usuários bloqueados</h2>
                    <button id="fecharBloqueados">Fechar</button>
                </div>
                <div id="listaBloqueados" class="dados"></div>
            </div>
        </div>
    </section>
    <script src="js/menuLateral.js"></script>
    <script>
    (function(){
        const lista = document.getElementById('listaBloqueados');
        const modal = document.getElementById('modalBloqueados');
        const btn = document.getElementById('btnBloqueados');
        const fechar = document.getElementById('fecharBloqueados');
        function render(rows){
            lista.innerHTML = '';
            if (!rows || rows.length === 0) {
                lista.innerHTML = '<p style="color:#666b7a;">Nenhum usuário bloqueado.</p>';
                return;
            }
            rows.forEach(r => {
                const div = document.createElement('div');
                div.className = 'item';
                const foto = r.foto_perfil ? r.foto_perfil : 'img/user.png';
                div.innerHTML = `
                    <div style="display:flex; align-items:center; gap:10px;">
                        <img src="${foto}" alt="Foto" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                        <div>
                            <h3>${r.nome}</h3>
                            <p>Bloqueado em ${new Date(r.data_bloqueio).toLocaleString('pt-BR')}</p>
                        </div>
                        <div style="margin-left:auto;">
                            <button data-id="${r.id}" class="btnDesbloquear">Desbloquear</button>
                        </div>
                    </div>
                `;
                lista.appendChild(div);
            });
            document.querySelectorAll('.btnDesbloquear').forEach(btn => {
                btn.addEventListener('click', async (ev) => {
                    const id = parseInt(ev.currentTarget.getAttribute('data-id'));
                    if (!id) return;
                    try {
                        const fd = new FormData(); fd.append('contato_id', String(id));
                        const res = await fetch('api_chat/desbloquear_contato.php', { method:'POST', body: fd, credentials: 'same-origin' });
                        const data = await res.json();
                        if (data && data.ok) { load(); }
                        else { alert(data.error || 'Erro ao desbloquear'); }
                    } catch(e){ alert('Erro de rede'); }
                });
            });
        }
        async function load(){
            try {
                const res = await fetch('api_chat/listar_bloqueados.php', { credentials: 'same-origin' });
                const rows = await res.json();
                if (rows.error) { lista.innerHTML = '<p style="color:red;">' + rows.error + '</p>'; return; }
                render(rows);
            } catch(e) { lista.innerHTML = '<p style="color:red;">Erro ao carregar bloqueados</p>'; }
        }
        btn?.addEventListener('click', () => { modal.style.display = 'flex'; load(); });
        fechar?.addEventListener('click', () => { modal.style.display = 'none'; });
    })();
    </script>
</body>
</html>
