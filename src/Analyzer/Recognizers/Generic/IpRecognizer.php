<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Recognizers\Generic;

use Weichie\Blur\Analyzer\Models\Pattern;
use Weichie\Blur\Analyzer\PatternRecognizer;

/**
 * Recognizes IPv4 and IPv6 addresses.
 */
class IpRecognizer extends PatternRecognizer
{
    public function __construct()
    {
        $patterns = [
            // IPv4: xxx.xxx.xxx.xxx
            new Pattern(
                name: 'IPv4',
                regex: '/\b(?:[0-9]{1,3}\.){3}[0-9]{1,3}\b/',
                score: 0.5
            ),
            // IPv6: Full format
            new Pattern(
                name: 'IPv6',
                regex: '/\b(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}\b/',
                score: 0.5
            ),
            // IPv6: Compressed format with ::
            new Pattern(
                name: 'IPv6 Compressed',
                regex: '/\b(?:[0-9a-fA-F]{1,4}:){0,7}:(?:[0-9a-fA-F]{1,4}:){0,7}[0-9a-fA-F]{1,4}\b/',
                score: 0.5
            ),
        ];

        parent::__construct(
            patterns: $patterns,
            supportedEntities: ['IP_ADDRESS'],
            supportedLanguages: ['en', 'nl', 'fr', 'de', 'es', 'it'],
            context: ['ip', 'address', 'adres', 'adresse']
        );
    }

    protected function validateResult(string $text): ?bool
    {
        // Try IPv4 validation
        if (filter_var($text, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return true;
        }

        // Try IPv6 validation
        if (filter_var($text, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return true;
        }

        return false;
    }

    protected function invalidateResult(string $text): bool
    {
        // Check for common false positives (e.g., version numbers like 1.2.3.4)
        // We'll be lenient here and let validation handle it
        return false;
    }
}
