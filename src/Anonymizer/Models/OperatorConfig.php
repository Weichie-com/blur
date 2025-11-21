<?php

declare(strict_types=1);

namespace Weichie\Blur\Anonymizer\Models;

/**
 * Configuration for an anonymization operator.
 */
class OperatorConfig
{
    public function __construct(
        public readonly string $operatorName,
        public readonly array $params = []
    ) {
    }

    /**
     * Create a replace operator configuration.
     */
    public static function replace(string $newValue): self
    {
        return new self('replace', ['new_value' => $newValue]);
    }

    /**
     * Create a redact operator configuration.
     */
    public static function redact(): self
    {
        return new self('redact');
    }

    /**
     * Create a mask operator configuration.
     */
    public static function mask(
        string $maskingChar = '*',
        int $charsToMask = -1,
        bool $fromEnd = false
    ): self {
        return new self('mask', [
            'masking_char' => $maskingChar,
            'chars_to_mask' => $charsToMask,
            'from_end' => $fromEnd
        ]);
    }

    /**
     * Create a hash operator configuration.
     */
    public static function hash(string $algorithm = 'sha256'): self
    {
        return new self('hash', ['algorithm' => $algorithm]);
    }

    /**
     * Create an encrypt operator configuration.
     */
    public static function encrypt(string $key): self
    {
        return new self('encrypt', ['key' => $key]);
    }

    /**
     * Create a decrypt operator configuration.
     */
    public static function decrypt(string $key): self
    {
        return new self('decrypt', ['key' => $key]);
    }

    /**
     * Create a keep operator configuration (no-op).
     */
    public static function keep(): self
    {
        return new self('keep');
    }
}
