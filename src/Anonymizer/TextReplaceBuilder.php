<?php

declare(strict_types=1);

namespace Weichie\Blur\Anonymizer;

/**
 * Builder for text replacement operations.
 * Supports replacing text segments while maintaining proper UTF-8 encoding.
 */
class TextReplaceBuilder
{
    private string $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    /**
     * Replace a segment of text.
     *
     * @param int $start Start position (character offset, not byte offset)
     * @param int $end End position (character offset, not byte offset)
     * @param string $newText Replacement text
     */
    public function replace(int $start, int $end, string $newText): void
    {
        // Extract parts using multibyte functions
        $before = mb_substr($this->text, 0, $start, 'UTF-8');
        $after = mb_substr($this->text, $end, null, 'UTF-8');

        // Build new text
        $this->text = $before . $newText . $after;
    }

    /**
     * Get the resulting text.
     */
    public function getText(): string
    {
        return $this->text;
    }
}
