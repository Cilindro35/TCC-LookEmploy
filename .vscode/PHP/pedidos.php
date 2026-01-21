<?php
    // Iniciar a sessão
    session_start();
    $usuario = $_SESSION['usuario'];

    // Verificar se o usuário está logado
    if (!isset($_SESSION['usuario'])) {
        header('location: login.html');
        exit();
    }

    // Conexão com o banco
    $conn = new mysqli('localhost', 'root', '', 'lookemploy');
    if ($conn->connect_error) {
        die("Erro de conexão: " . $conn->connect_error);
    }

    $categoria = $_GET['categoria'] ?? 'todos';
    $isPrestador = (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'Prestador');

    if ($isPrestador) {
        if ($categoria != 'todos') {
            $stmt = $conn->prepare("
                SELECT 
                    s.codigoServico,
                    s.assunto,
                    s.descricao,
                    s.dataServico,
                    s.contrato AS status,
                    c.nome AS alvo_nome,
                    c.sobrenome AS alvo_sobrenome,
                    c.caminhoImagemPerfil AS caminhoImagemPerfil
                FROM Servico s
                JOIN Cliente c ON c.ID = s.cliente
                WHERE s.prestador = ? " . ($categoria === 'cancelados' ? "AND s.contrato = 'cancelado'" : ($categoria === 'pendente' ? "AND s.contrato = 'pendente'" : "AND s.contrato = ?")) . "
            ");
            if ($categoria === 'cancelados') {
                $stmt->bind_param("s", $usuario);
            } else if ($categoria === 'pendente') {
                $stmt->bind_param("s", $usuario);
            } else {
                $stmt->bind_param("ss", $usuario, $categoria);
            }
        } else {
            $stmt = $conn->prepare("
               SELECT 
                    s.codigoServico,
                    s.assunto,
                    s.descricao,
                    s.dataServico,
                    s.contrato AS status,
                    c.nome AS alvo_nome,
                    c.sobrenome AS alvo_sobrenome,
                    c.caminhoImagemPerfil AS caminhoImagemPerfil
                FROM Servico s
                JOIN Cliente c ON c.ID = s.cliente
                WHERE s.prestador = ? AND s.contrato IN ('pendente','andamento','concluido')
            ");
            $stmt->bind_param("s", $usuario);
        }
    } else {
        if ($categoria != 'todos') {
            $stmt = $conn->prepare("
                SELECT 
                    s.codigoServico, 
                    s.assunto, 
                    s.descricao, 
                    s.dataServico, 
                    s.contrato AS status,
                    p.nome AS alvo_nome,
                    p.sobrenome AS alvo_sobrenome,
                    p.caminhoImagemPerfil AS caminhoImagemPerfil
                FROM Servico s
                JOIN Prestador p ON p.ID = s.prestador
                WHERE s.cliente = ? " . ($categoria === 'cancelados' ? "AND s.contrato = 'cancelado'" : ($categoria === 'pendente' ? "AND s.contrato = 'pendente'" : "AND s.contrato = ?")) . "
            ");
            if ($categoria === 'cancelados') {
                $stmt->bind_param("s", $usuario);
            } else if ($categoria === 'pendente') {
                $stmt->bind_param("s", $usuario);
            } else {
                $stmt->bind_param("ss", $usuario, $categoria);
            }
        } else {
            $stmt = $conn->prepare("
               SELECT 
                    s.codigoServico, 
                    s.assunto, 
                    s.descricao, 
                    s.dataServico, 
                    s.contrato AS status,
                    p.nome AS alvo_nome,
                    p.sobrenome AS alvo_sobrenome,
                    p.caminhoImagemPerfil AS caminhoImagemPerfil
                FROM Servico s
                JOIN Prestador p ON p.ID = s.prestador
                WHERE s.cliente = ? AND s.contrato IN ('pendente','andamento','concluido')
            ");
            $stmt->bind_param("s", $usuario);
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">
    <link rel="stylesheet" href="css/design_pedidos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="img/logo_icon.png">
</head>
<body>
    <!--Menu lateral-->
    <div id="inserirMenuLateral"></div>
    
    <!--PEDIDOS-->
    <section class="pedidos">
        <h1>Pedidos</h1>

        <!-- Botões de filtro -->
        <section class="filtros">
            <a href="pedidos.php?categoria=todos"><button>Todos</button></a>
            <a href="pedidos.php?categoria=pendente"><button>Pendentes</button></a>
            <a href="pedidos.php?categoria=andamento"><button>Em Andamento</button></a>
            <a href="pedidos.php?categoria=concluido"><button>Concluídos</button></a>
            <a href="pedidos.php?categoria=cancelados"><button>Cancelados</button></a>
        </section>

        <!-- Lista de pedidos -->
        <section class="listaPedidos">
            <?php
                if ($result->num_rows > 0) {
                    while($item = $result->fetch_assoc()) {
                        echo "<a href='editarServico.php?id={$item['codigoServico']}'>";
                            echo "<div class='Item'>";
                                $imgPerfil = !empty($item['caminhoImagemPerfil']) 
                                ? "img/img_perfil/" . rawurlencode($item['caminhoImagemPerfil']) 
                                : "img/img_perfil/default.png";

                                echo "<img class='perfilImg' src='{$imgPerfil}' alt='Usuario'>";
                                echo "<div class='descricao'>";
                                    echo "<h2>" . htmlspecialchars($item['alvo_nome']). " " . htmlspecialchars($item['alvo_sobrenome']) . "</h2>";
                                    if (!empty($item['assunto'])) { echo "<p>Assunto: " . htmlspecialchars($item['assunto']) . "</p>"; }
                                    echo "<p>" . htmlspecialchars($item['dataServico']) . "</p>";
                                    $st = strtolower($item['status'] ?? '');
                                    $label = $st === 'andamento' ? 'em andamento' : ($st ?: '');
                                    $class = 'status-chip ' . ($st ? ('status-' . $st) : '');
                                    echo "<p>" . htmlspecialchars($item['descricao']) . " <span class='" . $class . "'>" . htmlspecialchars($label) . "</span></p>";
                                echo "</div>";
                            echo "</div>";
                        echo "</a>";
                    }
                } else {
                    echo "<p>Nenhum item encontrado.</p>";
                }
                $conn->close();
            ?>
        </section>
    </section>
    <script src="js/menuLateral.js"></script>
</body>
</html>
