<?php

namespace Api\Security;

use Exception;

class MessageEncryption
{
    private $encryptionKey;
    private $cipher = 'aes-256-gcm';

    public function __construct()
    {
        $key = $_ENV['AES_KEY'] ?? $_SERVER['AES_KEY'] ?? getenv('AES_KEY');
        if (!$key) {
            $key = getenv('ENCRYPTION_KEY') ?: 'sua_chave_de_criptografia_aqui_min32chars!!';
        }
        $this->encryptionKey = substr(hash('sha256', $key, true), 0, 32);
    }

    public function encrypt($plaintext)
    {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->cipher,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new Exception("Erro ao criptografar mensagem");
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt($encrypted)
    {
        $data = base64_decode($encrypted);
        
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $tag = substr($data, $ivLength, 16);
        $ciphertext = substr($data, $ivLength + 16);

        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new Exception("Erro ao descriptografar mensagem");
        }

        return $plaintext;
    }
}
