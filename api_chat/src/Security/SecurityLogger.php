<?php

namespace Api\Security;

use PDO;
use Exception;

class SecurityLogger
{
    private $pdo;
    private $logFile;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->logFile = __DIR__ . '/../../logs/security.log';
        
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    public function logEvent($eventType, $userId, $details = [], $severity = 'INFO')
    {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $logEntry = [
            'timestamp' => $timestamp,
            'event_type' => $eventType,
            'user_id' => $userId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'details' => json_encode($details),
            'severity' => $severity
        ];

        $this->logToFile($logEntry);

        if ($severity === 'WARNING' || $severity === 'ERROR' || $severity === 'CRITICAL') {
            $this->logToDatabase($logEntry);
        }
    }

    private function logToFile($logEntry)
    {
        $line = sprintf(
            "[%s] [%s] User:%s IP:%s Event:%s - %s\n",
            $logEntry['timestamp'],
            $logEntry['severity'],
            $logEntry['user_id'] ?? 'anonymous',
            $logEntry['ip_address'],
            $logEntry['event_type'],
            $logEntry['details']
        );

        file_put_contents($this->logFile, $line, FILE_APPEND);
    }

    private function logToDatabase($logEntry)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO security_logs 
                (timestamp, event_type, user_id, ip_address, user_agent, details, severity)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $logEntry['timestamp'],
                $logEntry['event_type'],
                $logEntry['user_id'],
                $logEntry['ip_address'],
                $logEntry['user_agent'],
                $logEntry['details'],
                $logEntry['severity']
            ]);

        } catch (Exception $e) {
            error_log("Erro ao salvar log no banco: " . $e->getMessage());
        }
    }

    private function getClientIp()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'Invalid IP';
    }

    public function logFailedAuthentication($userId, $reason)
    {
        $this->logEvent('FAILED_AUTH', $userId, ['reason' => $reason], 'WARNING');
    }

    public function logSuccessfulAuthentication($userId)
    {
        $this->logEvent('SUCCESS_AUTH', $userId, [], 'INFO');
    }

    public function logMessageSent($userId, $recipientId)
    {
        $this->logEvent('MESSAGE_SENT', $userId, ['recipient' => $recipientId], 'INFO');
    }

    public function logSuspiciousActivity($userId, $activity)
    {
        $this->logEvent('SUSPICIOUS_ACTIVITY', $userId, ['activity' => $activity], 'CRITICAL');
    }

    public function logUnauthorizedAccess($userId, $resource)
    {
        $this->logEvent('UNAUTHORIZED_ACCESS', $userId, ['resource' => $resource], 'ERROR');
    }
}
