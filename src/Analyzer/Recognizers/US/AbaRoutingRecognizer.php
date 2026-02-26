<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Recognizers\US;

use Weichie\Blur\Analyzer\Models\Pattern;
use Weichie\Blur\Analyzer\PatternRecognizer;

/**
 * Recognizes US ABA Routing Numbers.
 * Ported from Microsoft Presidio's AbaRoutingRecognizer.
 */
class AbaRoutingRecognizer extends PatternRecognizer
{
    public function __construct()
    {
        $patterns = [
            new Pattern(
                name: 'ABA Routing (very weak)',
                regex: '/\b[0123678]\d{8}\b/',
                score: 0.05
            ),
            new Pattern(
                name: 'ABA Routing (weak, formatted)',
                regex: '/\b[0123678]\d{3}-\d{4}-\d\b/',
                score: 0.3
            ),
        ];

        parent::__construct(
            patterns: $patterns,
            supportedEntities: ['US_ABA_ROUTING'],
            supportedLanguages: ['en'],
            context: ['routing', 'aba', 'transit', 'rtn', 'routing#', 'routing number']
        );
    }

    protected function validateResult(string $text): ?bool
    {
        if (!ctype_digit($text) || strlen($text) !== 9) {
            return false;
        }

        $weights = [3, 7, 1, 3, 7, 1, 3, 7, 1];
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $text[$i] * $weights[$i];
        }

        return $sum % 10 === 0;
    }

    protected function invalidateResult(string $text): bool
    {
        if (preg_match('/^(\d)\1+$/', $text)) {
            return true;
        }

        return false;
    }
}
