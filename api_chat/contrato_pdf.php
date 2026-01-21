<?php
session_start();

$codigo = isset($_GET['codigoServico']) ? (int)$_GET['codigoServico'] : 0;
if (!$codigo) { header('Content-Type: application/json'); echo json_encode(['error'=>'Código inválido']); exit(); }

// Autoload robusto
$autoloads = [
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php'
];
foreach ($autoloads as $autoload) {
    if (file_exists($autoload)) {
        require_once $autoload;
    }
}

require __DIR__ . '/conectar.php';

$stmt = $pdo->prepare('SELECT s.*, c.ID as clienteID, p.ID as prestadorID FROM Servico s JOIN Cliente c ON c.ID = s.cliente JOIN Prestador p ON p.ID = s.prestador WHERE s.codigoServico = ?');
$stmt->execute([$codigo]);
$serv = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$serv) { header('Content-Type: application/json'); echo json_encode(['error'=>'Serviço não encontrado']); exit(); }

if ($serv['contrato'] !== 'andamento' && $serv['contrato'] !== 'concluido') {
  header('Content-Type: application/json'); echo json_encode(['error'=>'Contrato ainda não aceito por ambos']); exit();
}

$stmtC = $pdo->prepare('SELECT nome, sobrenome, email, telefone, bairro, logradouro, numero, complemento FROM Cliente WHERE ID = ?');
$stmtC->execute([$serv['clienteID']]);
$cli = $stmtC->fetch(PDO::FETCH_ASSOC);

$stmtP = $pdo->prepare('SELECT nome, sobrenome, email, telefone, bairro, logradouro, numero, complemento, tipoServico FROM Prestador WHERE ID = ?');
$stmtP->execute([$serv['prestadorID']]);
$pre = $stmtP->fetch(PDO::FETCH_ASSOC);

$html = '<html><head><meta charset="utf-8"><style>body{font-family:Arial; font-size:12px;} h1{font-size:18px;} table{width:100%; border-collapse:collapse;} td{padding:6px; border:1px solid #ccc;} .sec{margin:10px 0;} .footer{margin-top:20px; font-size:11px;}</style></head><body>';
$html .= '<h1>Contrato de Prestação de Serviços - LookEmploy</h1>';
$html .= '<div class="sec"><strong>Código:</strong> '.$codigo.' | <strong>Status:</strong> '.htmlspecialchars($serv['contrato']).' | <strong>Data do Serviço:</strong> '.htmlspecialchars($serv['dataServico']).'</div>';
$html .= '<div class="sec"><table><tr><td style="width:50%"><strong>Cliente</strong><br>'.htmlspecialchars($cli['nome'].' '.$cli['sobrenome']).'<br>Email: '.htmlspecialchars($cli['email']).'<br>Telefone: '.htmlspecialchars($cli['telefone']).'<br>Endereço: '.htmlspecialchars($cli['bairro'].', '.$cli['logradouro'].', '.$cli['numero'].' '.$cli['complemento']).'</td><td><strong>Prestador</strong><br>'.htmlspecialchars($pre['nome'].' '.$pre['sobrenome']).'<br>Serviço: '.htmlspecialchars($pre['tipoServico']).'<br>Email: '.htmlspecialchars($pre['email']).'<br>Telefone: '.htmlspecialchars($pre['telefone']).'<br>Endereço: '.htmlspecialchars($pre['bairro'].', '.$pre['logradouro'].', '.$pre['numero'].' '.$pre['complemento']).'</td></tr></table></div>';
$html .= '<div class="sec"><strong>Descrição do Serviço</strong><br>'.nl2br(htmlspecialchars($serv['descricao'] ?? '')).'</div>';
$html .= '<div class="sec"><strong>Pagamento</strong><br>Método: '.htmlspecialchars($serv['tipoPagamento']).'</div>';
$html .= '<div class="footer">Este contrato é gerado automaticamente pela plataforma LookEmploy após aceite de ambas as partes.</div>';
$html .= '</body></html>';

use Dompdf\Dompdf;
$useDompdf = class_exists(Dompdf::class);
if ($useDompdf) {
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Contrato_LookEmploy_'.$codigo.'.pdf"');
    echo $dompdf->output();
} else {
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
}
