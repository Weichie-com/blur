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
                    . '[A-Z]\d{3,8}'
                    . '|[A-Z]{2}\d{2,7}'
                    . '|\d{2}[A-Z]{3}\d{5}'
                    . '|[A-Z]\d{2}[- ]\d{2}[- ]\d{4}'
                    . '|[A-Z]\d{2}[- ]\d{3}[- ]\d{3}'
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
