<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL); // Mantendo o debug ativado

session_start();
use Api\Security\MessageEncryption;

// Compatibilidade de chaves de sessão
$rawSessionId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : (isset($_SESSION['usuario']) ? (int)$_SESSION['usuario'] : 0);
if (!$rawSessionId) {
    echo json_encode(['error' => 'Não autenticado']);
    exit();
}

try {
    require_once __DIR__ . '/conectar.php';
    
    $tipoSessao = $_SESSION['tipo'] ?? '';
    $contratoStatus = isset($_GET['contrato']) ? $_GET['contrato'] : null;
    $openId = isset($_GET['open']) ? (int)$_GET['open'] : 0;
    $defaultStatuses = ['pendente','aceito_cliente','aceito_prestador','andamento','concluido','cancelado'];

    // Mapear ID da sessão (Cliente.ID/Prestador.ID) para usuarios.id
    $usuarioId = 0;
    $tipoAtual = '';
    if (strcasecmp($tipoSessao, 'Cliente') === 0) {
        $stmtMap = $pdo->prepare("SELECT usuario_id FROM Cliente WHERE ID = ?");
        $stmtMap->execute([$rawSessionId]);
        $usuarioId = (int)$stmtMap->fetchColumn();
        $tipoAtual = 'cliente';
        if (!$usuarioId) {
            // Fallback: se não encontrou em Cliente, assume que $_SESSION['usuario'] já é o usuario.id
            $usuarioId = $rawSessionId;
            $tipoAtual = 'cliente'; // Mantém o tipo para a lógica SQL
        }
    } else if (strcasecmp($tipoSessao, 'Prestador') === 0) {
        $stmtMap = $pdo->prepare("SELECT usuario_id FROM Prestador WHERE ID = ?");
        $stmtMap->execute([$rawSessionId]);
        $usuarioId = (int)$stmtMap->fetchColumn();
        $tipoAtual = 'prestador';
        if (!$usuarioId) {
            // Fallback: se não encontrou em Prestador, assume que $_SESSION['usuario'] já é o usuario.id
            $usuarioId = $rawSessionId;
            $tipoAtual = 'prestador'; // Mantém o tipo para a lógica SQL
        }
    } else {
        // fallback: tentar via usuarios diretamente
        $stmtTipo = $pdo->prepare("SELECT tipo FROM usuarios WHERE id = ?");
        $stmtTipo->execute([$rawSessionId]);
        $tipoAtual = $stmtTipo->fetchColumn() ?: '';
        $usuarioId = $rawSessionId;
    }
    
    // Se o tipo não foi definido, mas o ID sim, tenta buscar o tipo
    if (!$tipoAtual && $usuarioId) {
        $stmtTipo = $pdo->prepare("SELECT tipo FROM usuarios WHERE id = ?");
        $stmtTipo->execute([$usuarioId]);
        $tipoAtual = $stmtTipo->fetchColumn() ?: '';
    }

    $hasUsuarioId = (bool)$usuarioId;

    if ($tipoAtual === 'cliente') {
        $baseSql = " 
            SELECT DISTINCT
                u.id,
                u.nome,
                u.online,
                p.caminhoImagemPerfil AS foto_perfil,
                (SELECT mensagem 
                 FROM mensagens m2 
                 WHERE (m2.remetente_id = u.id AND m2.destinatario_id = :usuario_id)
                    OR (m2.remetente_id = :usuario_id AND m2.destinatario_id = u.id)
                 ORDER BY m2.data_envio DESC 
                 LIMIT 1) as ultima_mensagem,
                (SELECT data_envio 
                 FROM mensagens m2 
                 WHERE (m2.remetente_id = u.id AND m2.destinatario_id = :usuario_id)
                    OR (m2.remetente_id = :usuario_id AND m2.destinatario_id = u.id)
                 ORDER BY m2.data_envio DESC 
                 LIMIT 1) as ultima_data,
                (SELECT COUNT(*) 
                 FROM mensagens m3 
                 WHERE m3.remetente_id = u.id 
                   AND m3.destinatario_id = :usuario_id
                   AND m3.lido = 0) as nao_lidas
            FROM Servico s
            JOIN Cliente c ON c.ID = s.cliente
            JOIN Prestador p ON p.ID = s.prestador
            JOIN usuarios u ON u.id = p.usuario_id
            WHERE " . ($hasUsuarioId ? "c.usuario_id = :usuario_id" : "c.ID = :raw_id") . "
              AND u.id != :usuario_id
              AND u.id NOT IN (SELECT bloqueado_id FROM usuarios_bloqueados WHERE usuario_id = :usuario_id)
              AND EXISTS (
                  SELECT 1 FROM mensagens mx
                  WHERE (mx.remetente_id = u.id AND mx.destinatario_id = :usuario_id)
                     OR (mx.remetente_id = :usuario_id AND mx.destinatario_id = u.id)
              )
            GROUP BY u.id, u.nome, u.online, p.caminhoImagemPerfil
            ORDER BY (ultima_data IS NULL), ultima_data DESC
        ";
        if ($contratoStatus) {
            $sql = str_replace('ORDER BY (ultima_data IS NULL), ultima_data DESC', 'AND s.contrato = :contrato ORDER BY (ultima_data IS NULL), ultima_data DESC', $baseSql);
            $stmt = $pdo->prepare($sql);
            $params = $hasUsuarioId ? ['usuario_id' => $usuarioId, 'contrato' => $contratoStatus] : ['raw_id' => $rawSessionId, 'usuario_id' => $usuarioId, 'contrato' => $contratoStatus];
            $stmt->execute($params);
        } else {
            // CORREÇÃO APLICADA AQUI
            $sql = str_replace('ORDER BY (ultima_data IS NULL), ultima_data DESC', 'AND s.contrato IN (:s1,:s2,:s3,:s4,:s5,:s6) ORDER BY (ultima_data IS NULL), ultima_data DESC', $baseSql);
            $stmt = $pdo->prepare($sql);
            $params = ['usuario_id' => $usuarioId, 's1' => $defaultStatuses[0], 's2' => $defaultStatuses[1], 's3' => $defaultStatuses[2], 's4' => $defaultStatuses[3], 's5' => $defaultStatuses[4], 's6' => $defaultStatuses[5]];
            if (!$hasUsuarioId) { $params['raw_id'] = $rawSessionId; }
            $stmt->execute($params);
        }
    } else if ($tipoAtual === 'prestador') {
        $baseSql = " 
            SELECT DISTINCT
                u.id,
                u.nome,
                u.online,
                c.caminhoImagemPerfil AS foto_perfil,
                (SELECT mensagem 
                 FROM mensagens m2 
                 WHERE (m2.remetente_id = u.id AND m2.destinatario_id = :usuario_id)
                    OR (m2.remetente_id = :usuario_id AND m2.destinatario_id = u.id)
                 ORDER BY m2.data_envio DESC 
                 LIMIT 1) as ultima_mensagem,
                (SELECT data_envio 
                 FROM mensagens m2 
                 WHERE (m2.remetente_id = u.id AND m2.destinatario_id = :usuario_id)
                    OR (m2.remetente_id = :usuario_id AND m2.destinatario_id = u.id)
                 ORDER BY m2.data_envio DESC 
                 LIMIT 1) as ultima_data,
                (SELECT COUNT(*) 
                 FROM mensagens m3 
                 WHERE m3.remetente_id = u.id 
                   AND m3.destinatario_id = :usuario_id
                   AND m3.lido = 0) as nao_lidas
            FROM Servico s
            JOIN Prestador p ON p.ID = s.prestador
            JOIN Cliente c ON c.ID = s.cliente
            JOIN usuarios u ON u.id = c.usuario_id
            WHERE " . ($hasUsuarioId ? "p.usuario_id = :usuario_id" : "p.ID = :raw_id") . "
              AND u.id != :usuario_id
              AND u.id NOT IN (SELECT bloqueado_id FROM usuarios_bloqueados WHERE usuario_id = :usuario_id)
              AND EXISTS (
                  SELECT 1 FROM mensagens mx
                  WHERE (mx.remetente_id = u.id AND mx.destinatario_id = :usuario_id)
                     OR (mx.remetente_id = :usuario_id AND mx.destinatario_id = u.id)
              )
            GROUP BY u.id, u.nome, u.online, c.caminhoImagemPerfil
            ORDER BY (ultima_data IS NULL), ultima_data DESC
        ";
        if ($contratoStatus) {
            $sql = str_replace('ORDER BY (ultima_data IS NULL), ultima_data DESC', 'AND s.contrato = :contrato ORDER BY (ultima_data IS NULL), ultima_data DESC', $baseSql);
            $stmt = $pdo->prepare($sql);
            $params = $hasUsuarioId ? ['usuario_id' => $usuarioId, 'contrato' => $contratoStatus] : ['raw_id' => $rawSessionId, 'usuario_id' => $usuarioId, 'contrato' => $contratoStatus];
            $stmt->execute($params);
        } else {
            // CORREÇÃO APLICADA AQUI
            $sql = str_replace('ORDER BY (ultima_data IS NULL), ultima_data DESC', 'AND s.contrato IN (:s1,:s2,:s3,:s4,:s5,:s6) ORDER BY (ultima_data IS NULL), ultima_data DESC', $baseSql);
            $stmt = $pdo->prepare($sql);
            $params = ['usuario_id' => $usuarioId, 's1' => $defaultStatuses[0], 's2' => $defaultStatuses[1], 's3' => $defaultStatuses[2], 's4' => $defaultStatuses[3], 's5' => $defaultStatuses[4], 's6' => $defaultStatuses[5]];
            if (!$hasUsuarioId) { $params['raw_id'] = $rawSessionId; }
            $stmt->execute($params);
        }
    } else {
        $stmt = $pdo->prepare("SELECT id, nome, online FROM usuarios WHERE 1=0");
        $stmt->execute();
    }
    
    $contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $enc = new MessageEncryption();
    foreach ($contatos as &$c) {
        if (!empty($c['ultima_mensagem'])) {
            try { $c['ultima_mensagem'] = $enc->decrypt($c['ultima_mensagem']); } catch (\Exception $e) { $c['ultima_mensagem'] = '[Mensagem criptografada]'; }
        }
        if (!empty($c['foto_perfil'])) {
            $fname = trim($c['foto_perfil']);
            if ($fname && strpos($fname, 'img/') !== 0 && strpos($fname, 'uploads/') !== 0) {
                $c['foto_perfil'] = 'img/img_perfil/' . $fname;
            }
        }
    }

    if ($openId) {
            $exists = false;
            foreach ($contatos as $c) {
                if ((int)$c['id'] === (int)$openId) { $exists = true; break; }
            }
            // Só adicionar via 'open' se já houver histórico de mensagens entre os dois
            if (!$exists && (int)$openId !== (int)$usuarioId && (int)$openId > 0) {
                $usuarioAtualId = 0;
                if ($tipoAtual === 'cliente' && $hasUsuarioId) { $usuarioAtualId = (int)$usuarioId; }
                if ($tipoAtual === 'prestador' && $hasUsuarioId) { $usuarioAtualId = (int)$usuarioId; }
                if (!$usuarioAtualId) { $usuarioAtualId = (int)$rawSessionId; }

                $stCnt = $pdo->prepare('SELECT COUNT(*) FROM mensagens WHERE (remetente_id = ? AND destinatario_id = ?) OR (remetente_id = ? AND destinatario_id = ?)');
                $stCnt->execute([$openId, $usuarioAtualId, $usuarioAtualId, $openId]);
                $msgCount = (int)$stCnt->fetchColumn();

                if ($msgCount > 0) {
                    $bk1 = $pdo->prepare('SELECT 1 FROM usuarios_bloqueados WHERE usuario_id = ? AND bloqueado_id = ? LIMIT 1');
                    $bk1->execute([$usuarioAtualId, $openId]);
                    $bk2 = $pdo->prepare('SELECT 1 FROM usuarios_bloqueados WHERE usuario_id = ? AND bloqueado_id = ? LIMIT 1');
                    $bk2->execute([$openId, $usuarioAtualId]);
                    $isBlockedEither = (bool)$bk1->fetchColumn() || (bool)$bk2->fetchColumn();
                    if (!$isBlockedEither) {
                        $stU = $pdo->prepare('SELECT id, nome, online FROM usuarios WHERE id = ?');
                        $stU->execute([$openId]);
                        $u = $stU->fetch(PDO::FETCH_ASSOC);
                        if ($u) { $contatos[] = $u; }
                    }
                }
            }
    }

	    // Fallback: incluir contatos com histórico de mensagens mesmo sem vínculo em Servico
	    // Determinar usuarios.id do atual
	    $usuarioAtualId = 0;
	    if ($tipoAtual === 'cliente' && $hasUsuarioId) { $usuarioAtualId = (int)$usuarioId; }
	    if ($tipoAtual === 'prestador' && $hasUsuarioId) { $usuarioAtualId = (int)$usuarioId; }
	    if (!$usuarioAtualId) { $usuarioAtualId = (int)$rawSessionId; }

	    $sqlMsg = "SELECT DISTINCT CASE WHEN m.remetente_id = ? THEN m.destinatario_id ELSE m.remetente_id END AS id FROM mensagens m WHERE m.remetente_id = ? OR m.destinatario_id = ?";
	    $stMsg = $pdo->prepare($sqlMsg);
	    $stMsg->execute([$usuarioAtualId, $usuarioAtualId, $usuarioAtualId]);
	    $ids = $stMsg->fetchAll(PDO::FETCH_COLUMN, 0);

	    if ($ids && count($ids) > 0) {
	        $place = implode(',', array_fill(0, count($ids), '?'));
            $stUsers = $pdo->prepare("SELECT id, nome, online FROM usuarios WHERE id IN ($place) AND id <> ?");
            $stUsers->execute([...$ids, $usuarioAtualId]);
            $rows = $stUsers->fetchAll(PDO::FETCH_ASSOC);
            // Filtrar bloqueados
            $stBlk = $pdo->prepare('SELECT bloqueado_id FROM usuarios_bloqueados WHERE usuario_id = ?');
            $stBlk->execute([$usuarioAtualId]);
            $bloq = $stBlk->fetchAll(PDO::FETCH_COLUMN, 0);
            $bloqSet = array_flip($bloq ?: []);

            // Adicionar contatos do histórico que ainda não estão na lista
            $existingIds = array_column($contatos, 'id');
            foreach ($rows as $row) {
                if (!in_array($row['id'], $existingIds) && !isset($bloqSet[$row['id']])) {
                    // Buscar imagem de perfil do alvo (pode estar em Cliente ou Prestador)
                    $foto = null;
                    $stFoto = $pdo->prepare('SELECT caminhoImagemPerfil FROM Cliente WHERE usuario_id = ?');
                    $stFoto->execute([$row['id']]);
                    $foto = $stFoto->fetchColumn();
                    if (!$foto) {
                        $stFoto = $pdo->prepare('SELECT caminhoImagemPerfil FROM Prestador WHERE usuario_id = ?');
                        $stFoto->execute([$row['id']]);
                        $foto = $stFoto->fetchColumn();
                    }
                    if ($foto) {
                        $row['foto_perfil'] = (strpos($foto, 'img/') === 0 || strpos($foto, 'uploads/') === 0) ? $foto : ('img/img_perfil/' . $foto);
                    } else {
                        $row['foto_perfil'] = null;
                    }
                    $contatos[] = $row;
                }
            }
	    }
    
    // Remover duplicidades por id (garantia extra contra duplicados provenientes de joins/fallbacks)
    $uniq = [];
    $out = [];
    foreach ($contatos as $c) {
        $cid = (int)$c['id'];
        if (isset($uniq[$cid])) { continue; }
        $uniq[$cid] = true;
        $out[] = $c;
    }
    echo json_encode($out);
    
} catch (Exception $e) {
    // Retorna a mensagem de erro real para o console
    error_log("Erro ao listar contatos: " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao carregar contatos: ' . $e->getMessage()]);
}
