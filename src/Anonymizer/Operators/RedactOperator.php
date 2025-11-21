<?php

declare(strict_types=1);

namespace Weichie\Blur\Anonymizer\Operators;

use Weichie\Blur\Anonymizer\Operator;

/**
 * Redact operator - removes text completely (empty string).
 */
class RedactOperator implements Operator
{
    public function operate(string $text, array $params = []): string
    {
        return '';
    }

    public function getName(): string
    {
        return 'redact';
    }

    public function validateParams(array $params): void
    {
        // No parameters required
    }
}
