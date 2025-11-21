<?php

declare(strict_types=1);

namespace Weichie\Blur\Anonymizer\Operators;

use Weichie\Blur\Anonymizer\Operator;

/**
 * Decrypt operator - decrypts text that was encrypted with EncryptOperator.
 */
class DecryptOperator implements Operator
{
    public function operate(string $text, array $params = []): string
    {
        $key = $params['key'] ?? throw new \InvalidArgumentException('Decryption key is required');

        // Decode base64
        $data = base64_decode($text, true);
        if ($data === false) {
            throw new \RuntimeException('Failed to decode encrypted data');
        }

        // Extract IV and encrypted data
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        // Hash the key to ensure it's 32 bytes for AES-256
        $keyHash = hash('sha256', $key, true);

        // Decrypt
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $keyHash, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed - wrong key or corrupted data');
        }

        return $decrypted;
    }

    public function getName(): string
    {
        return 'decrypt';
    }

    public function validateParams(array $params): void
    {
        if (!isset($params['key']) || !is_string($params['key']) || strlen($params['key']) === 0) {
            throw new \InvalidArgumentException('DecryptOperator requires non-empty "key" parameter');
        }
    }
}
