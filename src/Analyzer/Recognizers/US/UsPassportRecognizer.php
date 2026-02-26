<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Recognizers\US;

use Weichie\Blur\Analyzer\Models\Pattern;
use Weichie\Blur\Analyzer\PatternRecognizer;

/**
 * Recognizes US Passport Numbers.
 * Ported from Microsoft Presidio's UsPassportRecognizer.
 */
class UsPassportRecognizer extends PatternRecognizer
{
    public function __construct()
    {
        $patterns = [
            new Pattern(
                name: 'Passport (very weak, 9 digits)',
                regex: '/\b[0-9]{9}\b/',
                score: 0.05
            ),
            new Pattern(
                name: 'Passport Next Gen (very weak, letter + 8 digits)',
                regex: '/\b[A-Z][0-9]{8}\b/',
                score: 0.1
            ),
        ];

        parent::__construct(
            patterns: $patterns,
            supportedEntities: ['US_PASSPORT'],
            supportedLanguages: ['en'],
            context: ['us', 'united', 'states', 'passport', 'passport#', 'travel', 'document']
        );
    }
}
