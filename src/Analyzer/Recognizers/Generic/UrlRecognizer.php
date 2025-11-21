<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Recognizers\Generic;

use Weichie\Blur\Analyzer\Models\Pattern;
use Weichie\Blur\Analyzer\PatternRecognizer;

/**
 * Recognizes URLs (HTTP/HTTPS).
 */
class UrlRecognizer extends PatternRecognizer
{
    public function __construct()
    {
        $patterns = [
            // HTTP/HTTPS URLs
            new Pattern(
                name: 'URL (HTTP/HTTPS)',
                regex: '/\bhttps?:\/\/(?:www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&\/=]*)/',
                score: 0.5
            ),
            // www. URLs without protocol
            new Pattern(
                name: 'URL (www)',
                regex: '/\bwww\.[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&\/=]*)/',
                score: 0.4
            ),
        ];

        parent::__construct(
            patterns: $patterns,
            supportedEntities: ['URL'],
            supportedLanguages: ['en', 'nl', 'fr', 'de', 'es', 'it'],
            context: ['url', 'website', 'site', 'link', 'lien']
        );
    }

    protected function validateResult(string $text): ?bool
    {
        // Add protocol if missing for validation
        $url = $text;
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'http://' . $url;
        }

        // Parse URL components
        $parts = parse_url($url);

        // Must have at least a host
        if (!isset($parts['host'])) {
            return false;
        }

        // Check if host has a valid TLD
        $host = $parts['host'];
        if (!preg_match('/\.([a-z]{2,})$/i', $host)) {
            return false;
        }

        return true;
    }
}
