<?php

declare(strict_types=1);

namespace Weichie\Blur\Anonymizer\Models;

/**
 * Represents the result of applying an operator to a single entity.
 */
class OperatorResult
{
    public function __construct(
        public int $start,
        public int $end,
        public readonly string $entityType,
        public readonly string $text,
        public readonly string $operator
    ) {
    }
}
