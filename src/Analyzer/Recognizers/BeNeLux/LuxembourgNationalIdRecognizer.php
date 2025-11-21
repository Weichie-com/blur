<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Recognizers\BeNeLux;

use Weichie\Blur\Analyzer\Models\Pattern;
use Weichie\Blur\Analyzer\PatternRecognizer;

/**
 * Recognizes Luxembourg National Identification Number.
 *
 * Format: YYYYMMDDXXXXX (13 digits)
 * - YYYY: Year of birth
 * - MM: Month of birth (01-12)
 * - DD: Day of birth (01-31)
 * - XXXXX: Sequential number
 */
class LuxembourgNationalIdRecognizer extends PatternRecognizer
{
    public function __construct()
    {
        $patterns = [
            // Format: YYYYMMDDXXXXX (13 digits)
            new Pattern(
                name: 'Luxembourg National ID',
                regex: '/\b(19|20)[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])[0-9]{5}\b/',
                score: 0.4
            ),
            // Format with spaces: YYYY MM DD XXXXX
            new Pattern(
                name: 'Luxembourg National ID (spaced)',
                regex: '/\b(19|20)[0-9]{2}[\s](0[1-9]|1[0-2])[\s](0[1-9]|[12][0-9]|3[01])[\s][0-9]{5}\b/',
                score: 0.4
            ),
        ];

        parent::__construct(
            patterns: $patterns,
            supportedEntities: ['LU_NATIONAL_ID'],
            supportedLanguages: ['fr', 'de', 'lb', 'en'],
            context: [
                'matricule', 'national', 'identification', 'identité',
                'luxembourg', 'numéro', 'nationalidentifikationsnummer'
            ]
        );
    }

    protected function validateResult(string $text): ?bool
    {
        // Remove spaces
        $cleaned = preg_replace('/[\s]/', '', $text);

        // Must be exactly 13 digits
        if (!ctype_digit($cleaned) || strlen($cleaned) !== 13) {
            return false;
        }

        // Extract date components
        $year = (int)substr($cleaned, 0, 4);
        $month = (int)substr($cleaned, 4, 2);
        $day = (int)substr($cleaned, 6, 2);

        // Validate year (reasonable range)
        if ($year < 1900 || $year > 2100) {
            return false;
        }

        // Validate month
        if ($month < 1 || $month > 12) {
            return false;
        }

        // Validate day
        if ($day < 1 || $day > 31) {
            return false;
        }

        // Check if the date is valid
        if (!checkdate($month, $day, $year)) {
            return false;
        }

        return true;
    }

    protected function invalidateResult(string $text): bool
    {
        // Remove spaces
        $cleaned = preg_replace('/[\s]/', '', $text);

        // Invalid if all digits (except date part) are the same
        $sequential = substr($cleaned, 8, 5);
        if (preg_match('/^(\d)\1+$/', $sequential)) {
            return true;
        }

        return false;
    }
}
