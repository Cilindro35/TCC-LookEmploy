<?php

namespace Api\Security;

use PDO;
use Exception;

class PermissionManager
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function canCommunicate($userId, $targetUserId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    u1.tipo as user_type,
                    u2.tipo as target_type
                FROM usuarios u1
                JOIN usuarios u2 ON u2.id = ?
                WHERE u1.id = ?
            ");
            
            $stmt->execute([$targetUserId, $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return false;
            }

            if ($result['user_type'] === $result['target_type']) {
                return false;
            }

            return true;

        } catch (Exception $e) {
            error_log("Erro ao verificar permissões: " . $e->getMessage());
            return false;
        }
    }

    private function hasExistingConversation($userId, $targetUserId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM mensagens
                WHERE (remetente_id = ? AND destinatario_id = ?)
                   OR (remetente_id = ? AND destinatario_id = ?)
            ");
            
            $stmt->execute([$userId, $targetUserId, $targetUserId, $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['count'] > 0;

        } catch (Exception $e) {
            error_log("Erro ao verificar conversa existente: " . $e->getMessage());
            return false;
        }
    }

    private function hasContractBetween($userId, $targetUserId)
    {
        try {
            // Status permitidos que representam um vínculo existente
            $allowed = ['pendente', 'aceito_cliente', 'aceito_prestador', 'andamento', 'concluido', 'cancelado'];

            $placeholders = implode(',', array_fill(0, count($allowed), '?'));

            $sql = "
                SELECT COUNT(*) as count
                FROM Servico s
                JOIN Cliente c ON c.ID = s.cliente
                JOIN Prestador p ON p.ID = s.prestador
                WHERE (
                    c.usuario_id = ? AND p.usuario_id = ?
                ) OR (
                    c.usuario_id = ? AND p.usuario_id = ?
                )
                AND s.contrato IN ($placeholders)
            ";

            $stmt = $this->pdo->prepare($sql);
            $params = [$userId, $targetUserId, $targetUserId, $userId, ...$allowed];
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)$result['count'] > 0;

        } catch (Exception $e) {
            error_log("Erro ao verificar contrato: " . $e->getMessage());
            return false;
        }
    }

    public function isUserBlocked($userId, $blockedUserId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM usuarios_bloqueados
                WHERE usuario_id = ? AND bloqueado_id = ?
            ");
            
            $stmt->execute([$userId, $blockedUserId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['count'] > 0;

        } catch (Exception $e) {
            error_log("Erro ao verificar bloqueio: " . $e->getMessage());
            return false;
        }
    }

    public function blockUser($userId, $userToBlockId)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO usuarios_bloqueados (usuario_id, bloqueado_id, data_bloqueio)
                VALUES (?, ?, NOW())
            ");
            
            return $stmt->execute([$userId, $userToBlockId]);

        } catch (Exception $e) {
            error_log("Erro ao bloquear usuário: " . $e->getMessage());
            return false;
        }
    }

    public function unblockUser($userId, $userToUnblockId)
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM usuarios_bloqueados
                WHERE usuario_id = ? AND bloqueado_id = ?
            ");
            
            return $stmt->execute([$userId, $userToUnblockId]);

        } catch (Exception $e) {
            error_log("Erro ao desbloquear usuário: " . $e->getMessage());
            return false;
        }
    }
}
