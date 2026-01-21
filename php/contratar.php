<?php
include_once 'conexaoMySQL.php';
session_start();

$desc = $_POST['descricao'] ?? '';
$dataServico = $_POST['dataServico'] ?? '';
$bairro = $_POST['bairro'] ?? '';
$logradouro = $_POST['logradouro'] ?? '';
$numero = $_POST['numero'] ?? '';
$complemento = $_POST['complemento'] ?? '';
$metodo = $_POST['metodo'] ?? '';
$assunto = $_POST['assunto'] ?? '';
$cliente = $_POST['cliente'];
$prestador = $_POST['prestador'] ?? '';

if (!isset($_SESSION['usuario'])) {
    header('location: login.html');
    exit();
} else {

    // CONVERTER PARA FORMATO MYSQL
    date_default_timezone_set('America/Sao_Paulo');
    $data = date("Y-m-d H:i", strtotime(str_replace('/', '-', $dataServico)));

    if (!empty($_POST['codigoServico'])) {
        if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'Cliente') {
            echo "Apenas clientes podem editar serviços.";
            exit();
        }

        $codigoServico = $_POST['codigoServico'];
        $stmtChk = $pdo->prepare("SELECT cliente FROM Servico WHERE codigoServico = ? LIMIT 1");
        $stmtChk->execute([$codigoServico]);
        $rowChk = $stmtChk->fetch(PDO::FETCH_ASSOC);
        if (!$rowChk) {
            echo "Serviço não encontrado.";
            exit();
        }
        if (strval($rowChk['cliente']) !== strval($cliente)) {
            echo "Permissão negada para editar este serviço.";
            exit();
        }
        $stmtChk = null;

        // Bloquear edição se serviço estiver concluído ou cancelado
        $stmtSt = $pdo->prepare("SELECT contrato FROM Servico WHERE codigoServico = ? LIMIT 1");
        $stmtSt->execute([$codigoServico]);
        $stRow = $stmtSt->fetch(PDO::FETCH_ASSOC);
        if ($stRow && in_array($stRow['contrato'], ['concluido','cancelado'])) {
            echo "Serviço inativo (concluído/cancelado).";
            exit();
        }
        $stmtSt = null;

        $sql_update = "UPDATE Servico SET bairro=?, logradouro=?, numero=?, complemento=?, dataServico=?, tipoPagamento=?, assunto=?, descricao=? WHERE codigoServico=?";
        $stmt = $pdo->prepare($sql_update);
        $ok = $stmt->execute([
            $bairro,
            $logradouro,
            $numero,
            $complemento,
            $data,
            $metodo,
            $assunto,
            $desc,
            $codigoServico
        ]);

        if ($ok) {
            echo "EXITO";
            $stmt = null;
            $pdo = null;
            exit();
        } else {
            echo "Erro ao atualizar o serviço.";
            exit();
        }
    }

    $sql_insert = "INSERT INTO Servico 
        (bairro, logradouro, numero, complemento, dataServico, tipoPagamento, assunto, descricao, contrato, prestador, cliente) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendente', ?, ?)";

    $stmt = $pdo->prepare($sql_insert);
    $ok = $stmt->execute([
        $bairro,
        $logradouro,
        $numero,
        $complemento,
        $data,
        $metodo,
        $assunto,
        $desc,
        $prestador,
        $cliente
    ]);

    if ($ok) {
        // Garantir vínculo com tabela usuarios para contatos do chat
        // Cliente
        $stmtCli = $pdo->prepare("SELECT usuario_id, nome, email, senha FROM Cliente WHERE ID = ?");
        $stmtCli->execute([$cliente]);
        $rowCli = $stmtCli->fetch(PDO::FETCH_ASSOC);
        if ($rowCli) {
            if (empty($rowCli['usuario_id'])) {
                // Criar usuário de chat se não existir
                $stmtChkU = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND tipo = 'cliente' LIMIT 1");
                $stmtChkU->execute([$rowCli['email']]);
                $rowU = $stmtChkU->fetch(PDO::FETCH_ASSOC);
                if ($rowU) {
                    $uid = $rowU['id'];
                } else {
                    $stmtInsU = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo, online) VALUES (?, ?, ?, 'cliente', 0)");
                    $stmtInsU->execute([$rowCli['nome'], $rowCli['email'], $rowCli['senha']]);
                    $uid = $pdo->lastInsertId();
                }
                $stmtUpdCli = $pdo->prepare("UPDATE Cliente SET usuario_id = ? WHERE ID = ?");
                $stmtUpdCli->execute([$uid, $cliente]);
            }
        }
        $stmtCli = null;

        // Prestador
        $stmtPre = $pdo->prepare("SELECT usuario_id, nome, email, senha FROM Prestador WHERE ID = ?");
        $stmtPre->execute([$prestador]);
        $rowPre = $stmtPre->fetch(PDO::FETCH_ASSOC);
        if ($rowPre) {
            if (empty($rowPre['usuario_id'])) {
                $stmtChkU2 = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND tipo = 'prestador' LIMIT 1");
                $stmtChkU2->execute([$rowPre['email']]);
                $rowU2 = $stmtChkU2->fetch(PDO::FETCH_ASSOC);
                if ($rowU2) {
                    $uid2 = $rowU2['id'];
                } else {
                    $stmtInsU2 = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo, online) VALUES (?, ?, ?, 'prestador', 0)");
                    $stmtInsU2->execute([$rowPre['nome'], $rowPre['email'], $rowPre['senha']]);
                    $uid2 = $pdo->lastInsertId();
                }
                $stmtUpdPre = $pdo->prepare("UPDATE Prestador SET usuario_id = ? WHERE ID = ?");
                $stmtUpdPre->execute([$uid2, $prestador]);
            }
        }
        $stmtPre = null;

        echo "EXITO";
        $stmt = null;
        $pdo = null;
    } else {
        echo "Erro ao contratar o serviço.";
        exit();
    }
}
?>
