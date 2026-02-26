<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Recognizers\US;

use Weichie\Blur\Analyzer\Models\Pattern;
use Weichie\Blur\Analyzer\PatternRecognizer;

/**
 * Recognizes US Social Security Numbers (SSN).
 * Ported from Microsoft Presidio's UsSsnRecognizer.
 */
class UsSsnRecognizer extends PatternRecognizer
{
    private const SAMPLE_SSNS = [
        '123456789',
    ];

    public function __construct()
    {
        $patterns = [
            new Pattern(
                name: 'SSN (very weak, #####-####)',
                regex: '/\b[0-9]{5}[- ][0-9]{4}\b/',
                score: 0.05
            ),
            new Pattern(
                name: 'SSN (very weak, ###-######)',
                regex: '/\b[0-9]{3}[- ][0-9]{6}\b/',
                score: 0.05
            ),
            new Pattern(
                name: 'SSN (very weak, ###-##-####)',
                regex: '/\b[0-9]{3}[- ][0-9]{2}[- ][0-9]{4}\b/',
                score: 0.05
            ),
            new Pattern(
                name: 'SSN (very weak, 9 digits)',
                regex: '/\b[0-9]{9}\b/',
                score: 0.05
            ),
            new Pattern(
                name: 'SSN (medium)',
                regex: '/\b[0-9]{3}[- .][0-9]{2}[- .][0-9]{4}\b/',
                score: 0.5
            ),
        ];

        parent::__construct(
            patterns: $patterns,
            supportedEntities: ['US_SSN'],
            supportedLanguages: ['en'],
            context: ['social', 'security', 'ssn', 'ssns', 'ssid']
        );
    }

    protected function invalidateResult(string $text): bool
    {
        $cleaned = preg_replace('/[\s\-\.]/', '', $text);

        if (!ctype_digit($cleaned) || strlen($cleaned) !== 9) {
            return true;
        }

        $hasDash = str_contains($text, '-');
        $hasDot = str_contains($text, '.');
        $hasSpace = str_contains($text, ' ');
        if (($hasDash && $hasDot) || ($hasDash && $hasSpace) || ($hasDot && $hasSpace)) {
            return true;
        }

        if (preg_match('/^(\d)\1+$/', $cleaned)) {
            return true;
        }

        $area = (int) substr($cleaned, 0, 3);
        if ($area === 0 || $area === 666 || $area >= 900) {
            return true;
        }

        if ((int) substr($cleaned, 3, 2) === 0) {
            return true;
        }

        if ((int) substr($cleaned, 5, 4) === 0) {
            return true;
        }

        // Reject well-known sample/fake SSNs
        if (in_array($cleaned, self::SAMPLE_SSNS, true)) {
            return true;
        }

        return false;
    }
}
