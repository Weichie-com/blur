<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer;

use Weichie\Blur\Analyzer\Models\Pattern;
use Weichie\Blur\Analyzer\Models\RecognizerResult;

/**
 * Base class for pattern-based entity recognizers using regex.
 */
abstract class PatternRecognizer implements EntityRecognizer
{
    /**
     * @param Pattern[] $patterns Regex patterns to match
     * @param string[] $supportedEntities Entity types this recognizer can detect
     * @param string[] $supportedLanguages Language codes supported
     * @param string[] $context Context words that boost confidence
     */
    public function __construct(
        protected array $patterns,
        protected array $supportedEntities,
        protected array $supportedLanguages = ['en'],
        protected array $context = []
    ) {
    }

    public function getSupportedEntities(): array
    {
        return $this->supportedEntities;
    }

    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function analyze(string $text, array $entities = [], string $language = 'en'): array
    {
        // Check if language is supported
        if (!in_array($language, $this->supportedLanguages, true)) {
            return [];
        }

        // Filter entities if specified
        if (!empty($entities)) {
            $matchingEntities = array_intersect($this->supportedEntities, $entities);
            if (empty($matchingEntities)) {
                return [];
            }
        }

        $results = [];

        foreach ($this->patterns as $pattern) {
            $patternResults = $this->analyzePattern($text, $pattern);
            $results = array_merge($results, $patternResults);
        }

        // Remove duplicates
        $results = $this->removeDuplicates($results);

        return $results;
    }

    /**
     * Analyze text using a single pattern.
     *
     * @return RecognizerResult[]
     */
    protected function analyzePattern(string $text, Pattern $pattern): array
    {
        $results = [];

        // Use PREG_OFFSET_CAPTURE to get byte offsets
        $matches = [];
        if (preg_match_all($pattern->regex, $text, $matches, PREG_OFFSET_CAPTURE) === false) {
            return [];
        }

        if (empty($matches[0])) {
            return [];
        }

        foreach ($matches[0] as $match) {
            [$matchedText, $byteOffset] = $match;

            // Convert byte offset to character offset for UTF-8
            $start = mb_strlen(substr($text, 0, $byteOffset), 'UTF-8');
            $end = $start + mb_strlen($matchedText, 'UTF-8');

            // Check invalidation conditions FIRST
            $invalidationResult = $this->invalidateResult($matchedText);
            if ($invalidationResult) {
                continue; // Skip this match
            }

            // Validate the matched text
            $validationResult = $this->validateResult($matchedText);

            // Calculate final score
            $score = $pattern->score;
            if ($validationResult === true) {
                $score = 1.0;
            } elseif ($validationResult === false) {
                continue; // Skip this match
            }

            if ($score > 0.0) {
                $results[] = new RecognizerResult(
                    entityType: $this->supportedEntities[0] ?? 'UNKNOWN',
                    start: $start,
                    end: $end,
                    score: $score,
                    recognitionMetadata: [
                        'pattern_name' => $pattern->name,
                        'recognizer' => static::class
                    ]
                );
            }
        }

        return $results;
    }

    /**
     * Validate a matched result.
     * Override this method to implement custom validation logic (e.g., checksum).
     *
     * @param string $text The matched text
     * @return bool|null true = valid (set score to 1.0), false = invalid (skip), null = no validation
     */
    protected function validateResult(string $text): ?bool
    {
        return null;
    }

    /**
     * Invalidate a matched result based on custom logic.
     * Override this method to implement invalidation rules (e.g., all same digits).
     *
     * @param string $text The matched text
     * @return bool true = invalid (skip this match), false = valid
     */
    protected function invalidateResult(string $text): bool
    {
        return false;
    }

    /**
     * Remove duplicate results based on overlap and score.
     *
     * @param RecognizerResult[] $results
     * @return RecognizerResult[]
     */
    protected function removeDuplicates(array $results): array
    {
        if (empty($results)) {
            return [];
        }

        $uniqueResults = [];
        $otherElements = $results;

        foreach ($results as $result) {
            // Remove current result from comparison pool
            $key = array_search($result, $otherElements, true);
            if ($key !== false) {
                unset($otherElements[$key]);
            }

            // Check if this result conflicts with any other element
            $isConflicted = false;
            foreach ($otherElements as $other) {
                if ($result->hasConflict($other)) {
                    $isConflicted = true;
                    break;
                }
            }

            if (!$isConflicted) {
                $uniqueResults[] = $result;
                $otherElements[] = $result; // Add back for future comparisons
            }
        }

        return $uniqueResults;
    }
}
