<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Recognizers\Generic;

use Weichie\Blur\Analyzer\Models\Pattern;
use Weichie\Blur\Analyzer\PatternRecognizer;

/**
 * Recognizes IBAN codes with mod-97 checksum validation.
 * Focused on BeNeLux countries: Belgium (BE), Netherlands (NL), Luxembourg (LU).
 */
class IbanRecognizer extends PatternRecognizer
{
    public function __construct()
    {
        $patterns = [
            // Belgium (BE): BE + 2 check digits + 12 numeric (16 chars total)
            new Pattern(
                name: 'IBAN (Belgium)',
                regex: '/\bBE[0-9]{2}[\s]?[0-9]{4}[\s]?[0-9]{4}[\s]?[0-9]{4}\b/',
                score: 0.6
            ),
            // Netherlands (NL): NL + 2 check digits + 4 letters + 10 numeric (18 chars total)
            new Pattern(
                name: 'IBAN (Netherlands)',
                regex: '/\bNL[0-9]{2}[\s]?[A-Z]{4}[\s]?[0-9]{4}[\s]?[0-9]{4}[\s]?[0-9]{2}\b/',
                score: 0.6
            ),
            // Luxembourg (LU): LU + 2 check digits + 3 numeric + 13 alphanumeric (20 chars total)
            new Pattern(
                name: 'IBAN (Luxembourg)',
                regex: '/\bLU[0-9]{2}[\s]?[0-9]{3}[A-Z0-9][\s]?[A-Z0-9]{4}[\s]?[A-Z0-9]{4}[\s]?[A-Z0-9]{4}\b/',
                score: 0.6
            ),
        ];

        parent::__construct(
            patterns: $patterns,
            supportedEntities: ['IBAN_CODE'],
            supportedLanguages: ['en', 'nl', 'fr', 'de'],
            context: [
                'iban', 'bank', 'account', 'rekening', 'compte',
                'bancaire', 'bankrekening', 'rekeningnummer'
            ]
        );
    }

    protected function validateResult(string $text): ?bool
    {
        // Remove spaces and convert to uppercase
        $iban = strtoupper(preg_replace('/[\s\-]/', '', $text));

        // Must start with 2 letters followed by 2 digits
        if (!preg_match('/^[A-Z]{2}[0-9]{2}/', $iban)) {
            return false;
        }

        // Check length for BeNeLux countries
        $countryCode = substr($iban, 0, 2);
        $expectedLengths = [
            'BE' => 16,
            'NL' => 18,
            'LU' => 20,
        ];

        if (isset($expectedLengths[$countryCode])) {
            if (strlen($iban) !== $expectedLengths[$countryCode]) {
                return false;
            }
        }

        // Perform mod-97 checksum validation
        return $this->mod97Check($iban);
    }

    /**
     * Validate IBAN using mod-97 checksum algorithm.
     */
    private function mod97Check(string $iban): bool
    {
        // Move first 4 characters to the end
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);

        // Replace letters with numbers (A=10, B=11, ..., Z=35)
        $numeric = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numeric .= (string)(ord($char) - ord('A') + 10);
            } else {
                $numeric .= $char;
            }
        }

        // Calculate mod 97 (for large numbers, we need to do it in chunks)
        return $this->modulo97($numeric) === 1;
    }

    /**
     * Calculate modulo 97 for very large numbers (as strings).
     */
    private function modulo97(string $number): int
    {
        $remainder = 0;
        $length = strlen($number);

        for ($i = 0; $i < $length; $i++) {
            $remainder = ($remainder * 10 + (int)$number[$i]) % 97;
        }

        return $remainder;
    }
}
