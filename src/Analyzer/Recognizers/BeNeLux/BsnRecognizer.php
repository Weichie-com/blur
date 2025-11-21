<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Recognizers\BeNeLux;

use Weichie\Blur\Analyzer\Models\Pattern;
use Weichie\Blur\Analyzer\PatternRecognizer;

/**
 * Recognizes Dutch BSN (Burgerservicenummer) with 11-proof checksum validation.
 * BSN is a 9-digit social security number used in the Netherlands.
 */
class BsnRecognizer extends PatternRecognizer
{
    public function __construct()
    {
        $patterns = [
            // 9 digits, optionally with spaces or dots
            new Pattern(
                name: 'BSN',
                regex: '/\b[0-9]{3}[\s\.]?[0-9]{3}[\s\.]?[0-9]{3}\b/',
                score: 0.4
            ),
        ];

        parent::__construct(
            patterns: $patterns,
            supportedEntities: ['NL_BSN'],
            supportedLanguages: ['nl', 'en'],
            context: [
                'bsn', 'burgerservicenummer', 'burger service nummer',
                'sofi', 'sofinummer', 'sociaal', 'fiscaal'
            ]
        );
    }

    protected function validateResult(string $text): ?bool
    {
        // Remove spaces and dots
        $cleaned = preg_replace('/[\s\.]/', '', $text);

        // Must be exactly 9 digits
        if (!ctype_digit($cleaned) || strlen($cleaned) !== 9) {
            return false;
        }

        // Apply 11-proof checksum algorithm
        return $this->elevenProofCheck($cleaned);
    }

    protected function invalidateResult(string $text): bool
    {
        // Remove spaces and dots
        $cleaned = preg_replace('/[\s\.]/', '', $text);

        // Invalid if all digits are the same
        if (preg_match('/^(\d)\1+$/', $cleaned)) {
            return true;
        }

        // Invalid if all zeros
        if ($cleaned === '000000000') {
            return true;
        }

        return false;
    }

    /**
     * Validate BSN using 11-proof checksum algorithm (elfproef).
     *
     * Algorithm:
     * - Multiply digits by weights: 9, 8, 7, 6, 5, 4, 3, 2, -1
     * - Sum all products
     * - Valid if sum is divisible by 11
     */
    private function elevenProofCheck(string $bsn): bool
    {
        $weights = [9, 8, 7, 6, 5, 4, 3, 2, -1];
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$bsn[$i] * $weights[$i];
        }

        return $sum % 11 === 0;
    }
}
