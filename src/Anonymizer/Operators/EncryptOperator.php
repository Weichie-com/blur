<?php

declare(strict_types=1);

namespace Weichie\Blur\Anonymizer\Operators;

use Weichie\Blur\Anonymizer\Operator;

/**
 * Encrypt operator - encrypts text using AES-256-CBC.
 */
class EncryptOperator implements Operator
{
    public function operate(string $text, array $params = []): string
    {
        $key = $params['key'] ?? throw new \InvalidArgumentException('Encryption key is required');

        // Generate a random IV
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivLength);

        // Hash the key to ensure it's 32 bytes for AES-256
        $keyHash = hash('sha256', $key, true);

        // Encrypt
        $encrypted = openssl_encrypt($text, 'aes-256-cbc', $keyHash, OPENSSL_RAW_DATA, $iv);

        // Return base64-encoded IV + encrypted data
        return base64_encode($iv . $encrypted);
    }

    public function getName(): string
    {
        return 'encrypt';
    }

    public function validateParams(array $params): void
    {
        if (!isset($params['key']) || !is_string($params['key']) || strlen($params['key']) === 0) {
            throw new \InvalidArgumentException('EncryptOperator requires non-empty "key" parameter');
        }
    }
}
