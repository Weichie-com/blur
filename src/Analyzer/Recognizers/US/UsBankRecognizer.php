<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Recognizers\US;

use Weichie\Blur\Analyzer\Models\Pattern;
use Weichie\Blur\Analyzer\PatternRecognizer;

/**
 * Recognizes US Bank Account Numbers.
 * Ported from Microsoft Presidio's UsBankRecognizer.
 */
class UsBankRecognizer extends PatternRecognizer
{
    public function __construct()
    {
        $patterns = [
            new Pattern(
                name: 'Bank Account (very weak)',
                regex: '/\b[0-9]{8,17}\b/',
                score: 0.05
            ),
        ];

        parent::__construct(
            patterns: $patterns,
            supportedEntities: ['US_BANK_NUMBER'],
            supportedLanguages: ['en'],
            context: ['check', 'account', 'account#', 'acct', 'bank', 'save', 'debit']
        );
    }
}
