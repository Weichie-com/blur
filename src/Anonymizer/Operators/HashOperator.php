<?php

declare(strict_types=1);

namespace Weichie\Blur\Anonymizer\Operators;

use Weichie\Blur\Anonymizer\Operator;

/**
 * Hash operator - hashes text using SHA-256 or SHA-512.
 */
class HashOperator implements Operator
{
    public function operate(string $text, array $params = []): string
    {
        $algorithm = $params['algorithm'] ?? 'sha256';

        if (!in_array($algorithm, ['sha256', 'sha512'], true)) {
            throw new \InvalidArgumentException("Unsupported hash algorithm: {$algorithm}");
        }

        return hash($algorithm, $text);
    }

    public function getName(): string
    {
        return 'hash';
    }

    public function validateParams(array $params): void
    {
        if (isset($params['algorithm'])) {
            if (!in_array($params['algorithm'], ['sha256', 'sha512'], true)) {
                throw new \InvalidArgumentException('algorithm must be "sha256" or "sha512"');
            }
        }
    }
}
