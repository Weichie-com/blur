<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Recognizers\US;

use Weichie\Blur\Analyzer\Models\Pattern;
use Weichie\Blur\Analyzer\PatternRecognizer;

/**
 * Recognizes US Individual Taxpayer Identification Numbers (ITIN).
 * Ported from Microsoft Presidio's UsItinRecognizer.
 */
class UsItinRecognizer extends PatternRecognizer
{
    public function __construct()
    {
        $patterns = [
            new Pattern(
                name: 'ITIN (very weak)',
                regex: '/\b9\d{2}[- ](5\d|6[0-5]|7\d|8[0-8]|9[0-2]|9[4-9])\d{4}\b|\b9\d{2}(5\d|6[0-5]|7\d|8[0-8]|9[0-2]|9[4-9])[- ]\d{4}\b/',
                score: 0.05
            ),
            new Pattern(
                name: 'ITIN (weak)',
                regex: '/\b9\d{2}(5\d|6[0-5]|7\d|8[0-8]|9[0-2]|9[4-9])\d{4}\b/',
                score: 0.3
            ),
            new Pattern(
                name: 'ITIN (medium)',
                regex: '/\b9\d{2}[- ](5\d|6[0-5]|7\d|8[0-8]|9[0-2]|9[4-9])[- ]\d{4}\b/',
                score: 0.5
            ),
        ];

        parent::__construct(
            patterns: $patterns,
            supportedEntities: ['US_ITIN'],
            supportedLanguages: ['en'],
            context: ['individual', 'taxpayer', 'itin', 'tax', 'payer', 'taxid', 'tin']
        );
    }
}
