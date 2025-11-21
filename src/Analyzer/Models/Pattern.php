<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Models;

/**
 * Represents a regex pattern for entity recognition.
 */
class Pattern
{
    public function __construct(
        public readonly string $name,
        public readonly string $regex,
        public readonly float $score
    ) {
        if ($this->score < 0.0 || $this->score > 1.0) {
            throw new \InvalidArgumentException('Score must be between 0.0 and 1.0');
        }

        // Validate regex pattern
        if (@preg_match($this->regex, '') === false) {
            throw new \InvalidArgumentException("Invalid regex pattern: {$this->regex}");
        }
    }
}
