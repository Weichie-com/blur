<?php

declare(strict_types=1);

namespace Weichie\Blur\Anonymizer\Models;

/**
 * Final result of the anonymization process.
 */
class EngineResult
{
    private string $text = '';

    /** @var OperatorResult[] */
    private array $items = [];

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function addItem(OperatorResult $item): void
    {
        $this->items[] = $item;
    }

    /**
     * Get all operator results.
     *
     * @return OperatorResult[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Normalize item indexes after text replacement.
     * Since replacements are done from end to start, we need to recalculate
     * the actual positions in the final text.
     */
    public function normalizeItemIndexes(): void
    {
        // Sort items by original start position
        usort($this->items, fn($a, $b) => $a->start <=> $b->start);

        $offset = 0;
        foreach ($this->items as $item) {
            $originalLength = $item->end - $item->start;
            $newLength = mb_strlen($item->text, 'UTF-8');
            $lengthDiff = $newLength - $originalLength;

            // Update positions with accumulated offset
            $item->start += $offset;
            $item->end = $item->start + $newLength;

            // Update offset for next items
            $offset += $lengthDiff;
        }
    }
}
