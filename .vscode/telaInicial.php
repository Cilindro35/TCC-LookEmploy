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
        //conexão com o banco de dados
        $conn = new mysqli('localhost', 'root', '', 'lookemploy');
        if ($conn->connect_error) {
            die("Erro de conexão: " . $conn->connect_error);
        }
        else {
            //filtro
            $categoria = $_GET['categoria'] ?? 'todos';
            $isPrestador = (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'Prestador');
            if ($isPrestador) {
                // Prestador não deve ver recomendações de outros prestadores
                $result = $conn->query("SELECT ID FROM Prestador WHERE 1=0");
            } else {
                if ($categoria != 'todos') {
                    $stmt = $conn->prepare("SELECT ID, nome, sobrenome, descricao, avaliacao, caminhoImagemPerfil FROM Prestador WHERE tipoServico = ?");
                    $stmt->bind_param("s", $categoria);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $result = $conn->query("SELECT ID, nome, sobrenome, descricao, avaliacao, caminhoImagemPerfil FROM Prestador");
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TelaInicial</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/design_telaInicial.css">
    <link rel="icon" type="image/png" href="img/logo_icon.png">
</head>
<body>
    <!--Menu lateral-->
    <div id="inserirMenuLateral"></div>

    <!--BARRA DE PESQUISA-->
    <section class="telaInicial">
        <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'Prestador'): ?>
            <div style="background:#fff3cd;color:#664d03;padding:12px 16px;border:1px solid #ffecb5;border-radius:6px;margin-bottom:16px;">
                Para visualizar outros prestadores, faça login como cliente.
            </div>
        <?php endif; ?>
        <?php if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'Prestador'): ?>
        <!--Categorias-->
        <h1>Categorias</h1>
        <a href="?categoria=todos"><button>Todos</button></a>

        <!--Banners-->
        <section class="banners">
            <a href="?categoria=pedreiro" class="card">
                <img src="img/telaInicial/pedreiro.webp" alt="Pedreiro">
                <p>Pedreiro</p>
            </a>

            <a href="?categoria=encanador" class="card">
                <img src="img/telaInicial/encanador.webp" alt="Encanador">
                <p>Encanador</p>
            </a>

            <a href="?categoria=eletricista" class="card">
                <img src="img/telaInicial/eletricista.webp" alt="Eletricista">
                <p>Eletricista</p>
            </a>

            <a href="?categoria=marceneiro" class="card">
                <img src="img/telaInicial/marceneiro.jpg" alt="Marceneiro">
                <p>Marceneiro</p>
            </a>
        </section>
        <?php endif; ?>

        <?php if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'Prestador'): ?>
        <h1>Serviços recomendados</h1>
        <section class="recomendados">
            <?php
if ($result && $result->num_rows > 0) {
    while($item = $result->fetch_assoc()) {

        $imgPerfil = !empty($item['caminhoImagemPerfil']) 
                     ? "img/img_perfil/" . rawurlencode($item['caminhoImagemPerfil']) 
                     : "img/img_perfil/default.png";

        echo "<a href='visualizarPrestador.php?id={$item['ID']}'>
                <div class='Item'>
                    <img class='perfilImg' src='{$imgPerfil}' alt='Usuario' style='border-radius: 100px; border-color: whitesmoke;'>

                    <div class='descricao'>
                        <div style='display: flex; align-items: center; gap: 8px;'>
                            <h2>" . 
                                htmlspecialchars($item['nome']) . " " . 
                                htmlspecialchars($item['sobrenome']) . 
                            "</h2>";
                                
                        for ($i = 0; $i < $item['avaliacao']; $i++) {
                            echo "<i class='fa-solid fa-star' style='color: #5CE1E6;'></i>";
                        }

        echo           "</div>
                        <p style='
                            display: -webkit-box;
                            -webkit-line-clamp: 2;
                            -webkit-box-orient: vertical;
                            overflow: hidden;
                        '>" . htmlspecialchars($item['descricao']) . "</p>
                    </div>
                </div>
            </a>";
    }
} else {
    echo "<p>Nenhum item encontrado.</p>";
}

$conn->close();
?>

        </section>
        <?php endif; ?>
    </section>
    <script src="js/menuLateral.js"></script>
</body>
</html>
