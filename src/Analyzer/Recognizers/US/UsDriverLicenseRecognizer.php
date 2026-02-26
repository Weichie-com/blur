<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Recognizers\US;

use Weichie\Blur\Analyzer\Models\Pattern;
use Weichie\Blur\Analyzer\PatternRecognizer;

/**
 * Recognizes US Driver License Numbers.
 * Ported from Microsoft Presidio's UsDriverLicenseRecognizer.
 */
class UsDriverLicenseRecognizer extends PatternRecognizer
{
    public function __construct()
    {
        $patterns = [
            new Pattern(
                name: 'Driver License - Alphanumeric (weak)',
                regex: '/\b(?:'
                    . '[A-Z][0-9]{3,6}'
                    . '|[A-Z][0-9]{5,9}'
                    . '|[A-Z][0-9]{6,8}'
                    . '|[A-Z][0-9]{4,8}'
                    . '|[A-Z][0-9]{9,11}'
                    . '|[A-Z]{1,2}[0-9]{5,6}'
                    . '|H[0-9]{8}'
                    . '|V[0-9]{6}'
                    . '|X[0-9]{8}'
                    . '|[A-Z]{2}[0-9]{2,5}'
                    . '|[A-Z]{2}[0-9]{3,7}'
                    . '|[0-9]{2}[A-Z]{3}[0-9]{5,6}'
                    . '|[A-Z][0-9]{13,14}'
                    . '|[A-Z][0-9]{18}'
                    . '|[A-Z][0-9]{6}R'
                    . '|[A-Z][0-9]{9}'
                    . '|[A-Z][0-9]{1,12}'
                    . '|[0-9]{9}[A-Z]'
                    . '|[A-Z]{2}[0-9]{6}[A-Z]'
                    . '|[0-9]{8}[A-Z]{2}'
                    . '|[0-9]{3}[A-Z]{2}[0-9]{4}'
                    . '|[A-Z][0-9][A-Z][0-9][A-Z]'
                    . '|[0-9]{7,8}[A-Z]'
                    . ')\b/',
                score: 0.3
            ),
            new Pattern(
                name: 'Driver License - Digits (very weak)',
                regex: '/\b(?:[0-9]{6,14}|[0-9]{16})\b/',
                score: 0.01
            ),
        ];

        parent::__construct(
            patterns: $patterns,
            supportedEntities: ['US_DRIVER_LICENSE'],
            supportedLanguages: ['en'],
            context: ['driver', 'license', 'permit', 'lic', 'identification', 'dls', 'cdls', 'lic#', 'driving']
        );
    }
}
