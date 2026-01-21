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
                $stmt = $conn->prepare("SELECT nome, sobrenome, tipoServico, descricao, avaliacao, caminhoImagemPerfil, usuario_id FROM Prestador WHERE ID = ?");
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
                    $usuarioIdPrestador = $row["usuario_id"] ?? null;
                }

                $avaliacoes = [];
                $stmtAv = $conn->prepare("SELECT ap.nota, ap.comentario, ap.criado_em, c.nome AS cliente_nome, c.sobrenome AS cliente_sobrenome, c.caminhoImagemPerfil AS cliente_foto FROM AvaliacaoPrestador ap JOIN Cliente c ON c.ID = ap.cliente_id WHERE ap.prestador_id = ? ORDER BY ap.criado_em DESC");
                $stmtAv->bind_param("s", $id);
                $stmtAv->execute();
                $resAv = $stmtAv->get_result();
                while ($r = $resAv->fetch_assoc()) { $avaliacoes[] = $r; }
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
    <title>VisualizarPrestador</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">
    <link rel="stylesheet" href="css/design_visualizarPrestador.css">
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
            <a target="_self" href="telaInicial.php"><button>Voltar</button></a>

            <div class="informacoes">
                <!--FOTO E NOME-->
                <div class="inicio">
                    <img class="fotoPerfil" src="<?= $caminhoImagemPerfil ?>" alt="Foto de perfil" object-fit:cover; border-radius:50%;">
                    <div class="column">
                        <!--NOME E DESCRIÇÃO-->
                        <?php echo "<h1>". htmlspecialchars($nome). " " . htmlspecialchars($sobrenome). "</h1>"; ?>

                        <!--avaliação-->
                        <?php 
                            $media = (float)$avaliacao; 
                            $full = (int)floor($media);
                            $half = ($media - $full) >= 0.5 ? 1 : 0;
                            $empty = 5 - $full - $half;
                        ?>
                        <div class="avaliacao">
                            <?php for($i=0;$i<$full;$i++){ echo "<i class='fa-solid fa-star' style='color: #5CE1E6;'></i>"; } ?>
                            <?php if($half){ echo "<i class='fa-solid fa-star-half-stroke' style='color: #5CE1E6;'></i>"; } ?>
                            <?php for($i=0;$i<$empty;$i++){ echo "<i class='fa-regular fa-star' style='color: #5CE1E6;'></i>"; } ?>
                            <?php 
                                $totalAv = 0; 
                                if (isset($avaliacoes)) { $totalAv = count($avaliacoes); }
                            ?>
                            <span style="margin-left:8px; color:#5CE1E6; font-weight:600;"><?= number_format($media, 2, ',', '.') ?> (<?= (int)$totalAv ?> avaliações)</span>
                        </div>
                    </div>
                </div>

                <!--BOTÕES-->
                <div class="row">
                    <?php
                        echo "<a href='contratarServico.php?id=" . $id . "'><button>Contratar</button></a>";
                        $openParam = $usuarioIdPrestador ? (int)$usuarioIdPrestador : '';
                    ?>
                </div>
            </div>
        </section>

        <!--TIPO DE SERVIÇO-->
        <section class="secao">
            <?php
                if($servico != null) echo "<h3>". htmlspecialchars($servico)."</h3>";
            ?>
        </section>
        <hr class="line">
        
        <!--DESCRIÇÃO E NECESSIDADE-->
        <section class="secao">
            <div class="descricao">
                <h1>Descrição</h1>
                <?php
                    if($descricao != null) echo "<p>". htmlspecialchars($descricao)."</p>";
                ?>
            </div>
        </section>
        <section class="secao">
            <div class="descricao">
                <h1>Avaliações</h1>
                <?php if (count($avaliacoes) === 0): ?>
                    <p>Este prestador ainda não possui avaliações.</p>
                <?php else: ?>
                    <?php foreach ($avaliacoes as $av): ?>
                        <?php 
                            $n = (int)$av['nota'];
                            $clienteNome = trim(($av['cliente_nome'] ?? '').' '.($av['cliente_sobrenome'] ?? ''));
                            $foto = 'img/img_perfil/' . (($av['cliente_foto'] ?? 'default.png'));
                            $dataFmt = date('d/m/Y H:i', strtotime($av['criado_em']));
                        ?>
                        <div style="display:flex; gap:10px; align-items:flex-start; margin-bottom:12px;">
                            <img src="<?= htmlspecialchars($foto) ?>" alt="Cliente" style="width:40px;height:40px;border-radius:20px;object-fit:cover;">
                            <div>
                                <div style="font-weight:600; color:#0f223b;"><?= htmlspecialchars($clienteNome) ?> <span style="color:#8EB6C9; font-weight:400;"><?= $dataFmt ?></span></div>
                                <div>
                                    <?php for($i=0;$i<$n;$i++){ echo "<i class='fa-solid fa-star' style='color: #5CE1E6;'></i>"; } ?>
                                    <?php for($i=$n;$i<5;$i++){ echo "<i class='fa-regular fa-star' style='color: #5CE1E6;'></i>"; } ?>
                                </div>
                                <?php if (!empty($av['comentario'])): ?>
                                    <div style="color:#334b68; max-width:720px;"><?= htmlspecialchars($av['comentario']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </section>
    <script src="js/menuLateral.js"></script>
</body>
</html>
