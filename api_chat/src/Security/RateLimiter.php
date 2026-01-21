<?php

namespace Api\Security;

class RateLimiter
{
    private $limits = [];
    private $maxMessagesPerMinute = 30;
    private $maxMessagesPerHour = 500;
    private $cooldownSeconds = 1;

    public function __construct()
    {
        $perMinute = $_ENV['RATE_LIMIT_PER_MINUTE'] ?? $_SERVER['RATE_LIMIT_PER_MINUTE'] ?? getenv('RATE_LIMIT_PER_MINUTE');
        $perHour = $_ENV['RATE_LIMIT_PER_HOUR'] ?? $_SERVER['RATE_LIMIT_PER_HOUR'] ?? getenv('RATE_LIMIT_PER_HOUR');
        $cooldown = $_ENV['RATE_LIMIT_COOLDOWN'] ?? $_SERVER['RATE_LIMIT_COOLDOWN'] ?? getenv('RATE_LIMIT_COOLDOWN');
        $maxAlias = $_ENV['RATE_LIMIT_MAX'] ?? $_SERVER['RATE_LIMIT_MAX'] ?? getenv('RATE_LIMIT_MAX');
        if ($maxAlias && !$perMinute) { $perMinute = $maxAlias; }
        if ($perMinute && is_numeric($perMinute)) { $this->maxMessagesPerMinute = (int)$perMinute; }
        if ($perHour && is_numeric($perHour)) { $this->maxMessagesPerHour = (int)$perHour; }
        if ($cooldown && is_numeric($cooldown)) { $this->cooldownSeconds = (int)$cooldown; }
    }

    public function checkLimit($userId)
    {
        $now = time();
        
        if (!isset($this->limits[$userId])) {
            $this->limits[$userId] = [
                'messages' => [],
                'last_message' => 0
            ];
        }

        $userLimits = &$this->limits[$userId];

        $userLimits['messages'] = array_filter($userLimits['messages'], function($timestamp) use ($now) {
            return ($now - $timestamp) < 3600;
        });

        if (($now - $userLimits['last_message']) < $this->cooldownSeconds) {
            return [
                'allowed' => false,
                'reason' => 'Aguarde alguns segundos entre mensagens',
                'retry_after' => $this->cooldownSeconds - ($now - $userLimits['last_message'])
            ];
        }

        $messagesLastMinute = count(array_filter($userLimits['messages'], function($timestamp) use ($now) {
            return ($now - $timestamp) < 60;
        }));

        if ($messagesLastMinute >= $this->maxMessagesPerMinute) {
            return [
                'allowed' => false,
                'reason' => 'Limite de mensagens por minuto excedido',
                'retry_after' => 60
            ];
        }

        if (count($userLimits['messages']) >= $this->maxMessagesPerHour) {
            return [
                'allowed' => false,
                'reason' => 'Limite de mensagens por hora excedido',
                'retry_after' => 3600
            ];
        }

        $userLimits['messages'][] = $now;
        $userLimits['last_message'] = $now;

        return ['allowed' => true];
    }

    public function resetUser($userId)
    {
        unset($this->limits[$userId]);
    }
}
