<?php

declare(strict_types=1);

namespace Weichie\Blur\Anonymizer\Operators;

use Weichie\Blur\Anonymizer\Operator;

/**
 * Replace operator - replaces text with a custom value.
 */
class ReplaceOperator implements Operator
{
    public function operate(string $text, array $params = []): string
    {
        return $params['new_value'] ?? '';
    }

    public function getName(): string
    {
        return 'replace';
    }

    public function validateParams(array $params): void
    {
        if (!isset($params['new_value'])) {
            throw new \InvalidArgumentException('ReplaceOperator requires "new_value" parameter');
        }
    }
}
