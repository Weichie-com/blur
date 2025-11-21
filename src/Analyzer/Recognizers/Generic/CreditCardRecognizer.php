<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Recognizers\Generic;

use Weichie\Blur\Analyzer\Models\Pattern;
use Weichie\Blur\Analyzer\PatternRecognizer;

/**
 * Recognizes credit card numbers with Luhn checksum validation.
 */
class CreditCardRecognizer extends PatternRecognizer
{
    public function __construct()
    {
        $patterns = [
            // Visa: 4xxx-xxxx-xxxx-xxxx (13-19 digits)
            new Pattern(
                name: 'Visa',
                regex: '/\b4[0-9]{12}(?:[0-9]{3})?(?:[0-9]{3})?\b/',
                score: 0.3
            ),
            // Mastercard: 5[1-5]xx-xxxx-xxxx-xxxx or 2[2-7]xx-xxxx-xxxx-xxxx
            new Pattern(
                name: 'Mastercard',
                regex: '/\b(?:5[1-5][0-9]{14}|2(?:2[2-9][0-9]|2[3-9][0-9]|[3-6][0-9]{2}|7[01][0-9]|720)[0-9]{12})\b/',
                score: 0.3
            ),
            // American Express: 3[47]xx-xxxxxx-xxxxx (15 digits)
            new Pattern(
                name: 'American Express',
                regex: '/\b3[47][0-9]{13}\b/',
                score: 0.3
            ),
            // Discover: 6011-xxxx-xxxx-xxxx or 65xx-xxxx-xxxx-xxxx
            new Pattern(
                name: 'Discover',
                regex: '/\b(?:6011|65[0-9]{2})[0-9]{12}\b/',
                score: 0.3
            ),
            // Generic pattern with spaces or dashes
            new Pattern(
                name: 'Credit Card (with separators)',
                regex: '/\b[0-9]{4}[\s\-][0-9]{4}[\s\-][0-9]{4}[\s\-][0-9]{4}\b/',
                score: 0.3
            ),
        ];

        parent::__construct(
            patterns: $patterns,
            supportedEntities: ['CREDIT_CARD'],
            supportedLanguages: ['en', 'nl', 'fr', 'de', 'es', 'it'],
            context: [
                'credit', 'card', 'visa', 'mastercard', 'amex', 'american express',
                'discover', 'creditcard', 'cc', 'carte', 'krediet'
            ]
        );
    }

    protected function validateResult(string $text): ?bool
    {
        // Remove spaces and dashes for validation
        $cleaned = preg_replace('/[\s\-]/', '', $text);

        // Must be numeric
        if (!ctype_digit($cleaned)) {
            return false;
        }

        // Apply Luhn checksum algorithm
        return $this->luhnCheck($cleaned);
    }

    protected function invalidateResult(string $text): bool
    {
        // Remove spaces and dashes
        $cleaned = preg_replace('/[\s\-]/', '', $text);

        // Invalid if all digits are the same
        if (preg_match('/^(\d)\1+$/', $cleaned)) {
            return true;
        }

        // Invalid if sequential (e.g., 123456789...)
        $isSequential = true;
        for ($i = 1; $i < strlen($cleaned); $i++) {
            if ((int)$cleaned[$i] !== ((int)$cleaned[$i - 1] + 1) % 10) {
                $isSequential = false;
                break;
            }
        }

        return $isSequential;
    }

    /**
     * Validate using Luhn checksum algorithm.
     */
    private function luhnCheck(string $number): bool
    {
        $sum = 0;
        $length = strlen($number);
        $parity = $length % 2;

        for ($i = 0; $i < $length; $i++) {
            $digit = (int)$number[$i];

            // Double every other digit starting from the right
            if ($i % 2 === $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}
