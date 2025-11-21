<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Recognizers\Generic;

use Weichie\Blur\Analyzer\Models\Pattern;
use Weichie\Blur\Analyzer\PatternRecognizer;

/**
 * Recognizes email addresses with RFC validation.
 */
class EmailRecognizer extends PatternRecognizer
{
    public function __construct()
    {
        $patterns = [
            new Pattern(
                name: 'Email (Medium)',
                regex: '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/',
                score: 0.5
            ),
        ];

        parent::__construct(
            patterns: $patterns,
            supportedEntities: ['EMAIL_ADDRESS'],
            supportedLanguages: ['en', 'nl', 'fr', 'de', 'es', 'it'],
            context: ['email', 'e-mail', 'mail', 'courriel']
        );
    }

    protected function validateResult(string $text): ?bool
    {
        // Use PHP's built-in email validation
        return filter_var($text, FILTER_VALIDATE_EMAIL) !== false;
    }
}
