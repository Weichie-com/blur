<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Recognizers\BeNeLux;

use Weichie\Blur\Analyzer\Models\Pattern;
use Weichie\Blur\Analyzer\PatternRecognizer;

/**
 * Recognizes Belgian National Number (Rijksregisternummer / Numéro de registre national)
 * with mod-97 checksum validation.
 *
 * Format: YY.MM.DD-XXX.CD (11 digits)
 * - YY: Year of birth
 * - MM: Month of birth (01-12)
 * - DD: Day of birth (01-31)
 * - XXX: Sequential number (odd for male, even for female)
 * - CD: Check digits (97 - (YYMMDDXXX mod 97))
 */
class BelgianNationalNumberRecognizer extends PatternRecognizer
{
    public function __construct()
    {
        $patterns = [
            // Format: YY.MM.DD-XXX.CD
            new Pattern(
                name: 'Belgian National Number (formatted)',
                regex: '/\b[0-9]{2}\.[0-9]{2}\.[0-9]{2}[\-][0-9]{3}\.[0-9]{2}\b/',
                score: 0.4
            ),
            // Format without separators: YYMMDDXXXCD
            new Pattern(
                name: 'Belgian National Number (unformatted)',
                regex: '/\b[0-9]{11}\b/',
                score: 0.3
            ),
        ];

        parent::__construct(
            patterns: $patterns,
            supportedEntities: ['BE_NATIONAL_NUMBER'],
            supportedLanguages: ['nl', 'fr', 'de', 'en'],
            context: [
                'rijksregisternummer', 'registre national', 'national number',
                'rr', 'nrn', 'nn', 'identiteitsnummer', 'identiteit',
                'numéro national', 'nationalregisternummer'
            ]
        );
    }

    protected function validateResult(string $text): ?bool
    {
        // Remove separators (dots and dashes)
        $cleaned = preg_replace('/[\.\-]/', '', $text);

        // Must be exactly 11 digits
        if (!ctype_digit($cleaned) || strlen($cleaned) !== 11) {
            return false;
        }

        // Extract components
        $birthDate = substr($cleaned, 0, 6); // YYMMDD
        $sequential = substr($cleaned, 6, 3); // XXX
        $checkDigits = substr($cleaned, 9, 2); // CD

        // Validate date components
        $year = (int)substr($birthDate, 0, 2);
        $month = (int)substr($birthDate, 2, 2);
        $day = (int)substr($birthDate, 4, 2);

        if ($month < 1 || $month > 12) {
            return false;
        }

        if ($day < 1 || $day > 31) {
            return false;
        }

        // Apply mod-97 checksum validation
        return $this->mod97Check($cleaned);
    }

    protected function invalidateResult(string $text): bool
    {
        // Remove separators
        $cleaned = preg_replace('/[\.\-]/', '', $text);

        // Invalid if all digits are the same
        if (preg_match('/^(\d)\1+$/', $cleaned)) {
            return true;
        }

        // Invalid if all zeros
        if ($cleaned === '00000000000') {
            return true;
        }

        return false;
    }

    /**
     * Validate Belgian National Number using mod-97 checksum.
     *
     * Algorithm:
     * - Take first 9 digits (YYMMDDXXX)
     * - Calculate: 97 - (number mod 97)
     * - Result must equal the last 2 digits (CD)
     *
     * Note: For people born in 2000 or later, prepend "2" to the 9-digit number.
     */
    private function mod97Check(string $number): bool
    {
        $firstNine = substr($number, 0, 9);
        $checkDigits = (int)substr($number, 9, 2);

        // Try standard calculation (for people born before 2000)
        $calculated = 97 - ((int)$firstNine % 97);
        if ($calculated === $checkDigits) {
            return true;
        }

        // Try with "2" prepended (for people born in 2000 or later)
        $firstNineWith2 = '2' . $firstNine;
        $calculatedWith2 = 97 - ((int)$firstNineWith2 % 97);
        if ($calculatedWith2 === $checkDigits) {
            return true;
        }

        return false;
    }
}
