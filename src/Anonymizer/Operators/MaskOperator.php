<?php

declare(strict_types=1);

namespace Weichie\Blur\Anonymizer\Operators;

use Weichie\Blur\Anonymizer\Operator;

/**
 * Mask operator - replaces N characters with a masking character.
 */
class MaskOperator implements Operator
{
    public function operate(string $text, array $params = []): string
    {
        $maskingChar = $params['masking_char'] ?? '*';
        $charsToMask = $params['chars_to_mask'] ?? -1; // -1 means all
        $fromEnd = $params['from_end'] ?? false;

        $length = mb_strlen($text, 'UTF-8');

        // Mask all characters
        if ($charsToMask === -1 || $charsToMask >= $length) {
            return str_repeat($maskingChar, $length);
        }

        // Mask from end
        if ($fromEnd) {
            $unmasked = mb_substr($text, 0, $length - $charsToMask, 'UTF-8');
            $masked = str_repeat($maskingChar, $charsToMask);
            return $unmasked . $masked;
        }

        // Mask from start
        $masked = str_repeat($maskingChar, $charsToMask);
        $unmasked = mb_substr($text, $charsToMask, null, 'UTF-8');
        return $masked . $unmasked;
    }

    public function getName(): string
    {
        return 'mask';
    }

    public function validateParams(array $params): void
    {
        if (isset($params['masking_char'])) {
            if (!is_string($params['masking_char']) || mb_strlen($params['masking_char'], 'UTF-8') !== 1) {
                throw new \InvalidArgumentException('masking_char must be a single character');
            }
        }

        if (isset($params['chars_to_mask'])) {
            if (!is_int($params['chars_to_mask']) || ($params['chars_to_mask'] < -1)) {
                throw new \InvalidArgumentException('chars_to_mask must be an integer >= -1');
            }
        }
    }
}
