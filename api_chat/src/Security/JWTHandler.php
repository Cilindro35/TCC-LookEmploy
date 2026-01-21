<?php

namespace Api\Security;

use Exception;

class JWTHandler
{
    private $secretKey;
    private $algorithm = 'HS256';
    private $expirationTime = 86400;

    public function __construct()
    {
        $this->secretKey = $_ENV['JWT_SECRET'] ?? $_SERVER['JWT_SECRET'] ?? getenv('JWT_SECRET');
        if (!$this->secretKey) {
            throw new Exception("JWT_SECRET não encontrado nas variáveis de ambiente.");
        }
    }

    public function generateToken($userId, $userName, $userType)
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->expirationTime;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'user_id' => $userId,
            'user_name' => $userName,
            'user_type' => $userType,
            'jti' => bin2hex(random_bytes(16))
        ];

        return $this->encode($payload);
    }

    public function validateToken($token)
    {
        try {
            $payload = $this->decode($token);

            if (isset($payload['exp']) && $payload['exp'] < time()) {
                error_log("Token expirado");
                return false;
            }

            if (!isset($payload['user_id']) || !isset($payload['user_name']) || !isset($payload['user_type'])) {
                error_log("Token sem campos obrigatórios");
                return false;
            }

            return $payload;

        } catch (Exception $e) {
            error_log("Erro ao validar token: " . $e->getMessage());
            return false;
        }
    }

    private function encode($payload)
    {
        $header = [
            'typ' => 'JWT',
            'alg' => $this->algorithm
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            $headerEncoded . "." . $payloadEncoded,
            $this->secretKey,
            true
        );

        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
    }

    private function decode($token)
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new Exception("Token inválido");
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        $signature = hash_hmac(
            'sha256',
            $headerEncoded . "." . $payloadEncoded,
            $this->secretKey,
            true
        );

        $signatureCheck = $this->base64UrlEncode($signature);

        if ($signatureEncoded !== $signatureCheck) {
            throw new Exception("Assinatura inválida");
        }

        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);

        if (!$payload) {
            throw new Exception("Payload inválido");
        }

        return $payload;
    }

    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public function getUserIdFromToken($token)
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) return null;
            
            $payload = json_decode($this->base64UrlDecode($parts[1]), true);
            return $payload['user_id'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }
}
