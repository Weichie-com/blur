<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Models;

/**
 * Represents the result of a PII entity recognition.
 */
class RecognizerResult
{
    public function __construct(
        public readonly string $entityType,
        public readonly int $start,
        public readonly int $end,
        public float $score,
        public readonly array $recognitionMetadata = []
    ) {
        if ($this->start < 0 || $this->end < 0) {
            throw new \InvalidArgumentException('Start and end positions must be non-negative');
        }
        if ($this->start >= $this->end) {
            throw new \InvalidArgumentException('Start position must be less than end position');
        }
        if ($this->score < 0.0 || $this->score > 1.0) {
            throw new \InvalidArgumentException('Score must be between 0.0 and 1.0');
        }
    }

    /**
     * Check if this result has the same indices as another result.
     */
    public function equalIndices(self $other): bool
    {
        return $this->start === $other->start && $this->end === $other->end;
    }

    /**
     * Check if this result is contained within another result.
     */
    public function containedIn(self $other): bool
    {
        return $this->start >= $other->start && $this->end <= $other->end;
    }

    /**
     * Check if this result contains another result.
     */
    public function contains(self $other): bool
    {
        return $other->containedIn($this);
    }

    /**
     * Check if this result overlaps with another result.
     */
    public function overlaps(self $other): bool
    {
        return !($this->end <= $other->start || $this->start >= $other->end);
    }

    /**
     * Check if this result has a conflict with another result.
     * A conflict exists if:
     * - Same indices but this has lower or equal score
     * - This is contained in the other
     */
    public function hasConflict(self $other): bool
    {
        if ($this->equalIndices($other)) {
            return $this->score <= $other->score;
        }
        return $this->containedIn($other);
    }

    /**
     * Get the length of the detected entity.
     */
    public function length(): int
    {
        return $this->end - $this->start;
    }
}
