<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer;

use Weichie\Blur\Analyzer\Models\RecognizerResult;

/**
 * Main engine for PII detection in text.
 */
class AnalyzerEngine
{
    public function __construct(
        private RecognizerRegistry $registry,
        private float $defaultScoreThreshold = 0.0
    ) {
    }

    /**
     * Analyze text to detect PII entities.
     *
     * @param string $text The text to analyze
     * @param string $language Language code (e.g., 'en', 'nl', 'fr')
     * @param string[] $entities Filter for specific entity types (empty = all)
     * @param float|null $scoreThreshold Minimum confidence score (overrides default)
     * @param string[] $context Context words to boost confidence
     * @param string[] $allowList Whitelist terms to ignore
     * @return RecognizerResult[]
     */
    public function analyze(
        string $text,
        string $language = 'en',
        array $entities = [],
        ?float $scoreThreshold = null,
        array $context = [],
        array $allowList = []
    ): array {
        $threshold = $scoreThreshold ?? $this->defaultScoreThreshold;

        // Get applicable recognizers
        $recognizers = $this->registry->getRecognizers($language, $entities);

        if (empty($recognizers)) {
            return [];
        }

        // Run each recognizer
        $results = [];
        foreach ($recognizers as $recognizer) {
            $recognizerResults = $recognizer->analyze($text, $entities, $language);
            $results = array_merge($results, $recognizerResults);
        }

        // Enhance using context (always run — recognizers provide their own context words)
        $results = $this->enhanceUsingContext($text, $results, $context, $recognizers);

        // Remove duplicates and conflicts
        $results = $this->removeConflicts($results);

        // Filter by score threshold
        $results = array_filter($results, fn($r) => $r->score >= $threshold);

        // Apply allow list
        if (!empty($allowList)) {
            $results = $this->removeAllowList($text, $results, $allowList);
        }

        // Sort by start position
        usort($results, fn($a, $b) => $a->start <=> $b->start);

        return array_values($results);
    }

    /**
     * Get all supported entity types.
     *
     * @param string|null $language Filter by language
     * @return string[]
     */
    public function getSupportedEntities(?string $language = null): array
    {
        return $this->registry->getSupportedEntities($language);
    }

    /**
     * Get all supported languages.
     *
     * @return string[]
     */
    public function getSupportedLanguages(): array
    {
        return $this->registry->getSupportedLanguages();
    }

    /**
     * Enhance results using context words.
     *
     * @param string $text Original text
     * @param RecognizerResult[] $results Current results
     * @param string[] $context Context words to search for
     * @param EntityRecognizer[] $recognizers Recognizers to get context from
     * @return RecognizerResult[]
     */
    private function enhanceUsingContext(
        string $text,
        array $results,
        array $context,
        array $recognizers
    ): array {
        // Build a map of entity type to context words
        $contextMap = [];
        foreach ($recognizers as $recognizer) {
            foreach ($recognizer->getSupportedEntities() as $entityType) {
                $recognizerContext = $recognizer->getContext();
                if (!isset($contextMap[$entityType])) {
                    $contextMap[$entityType] = [];
                }
                $contextMap[$entityType] = array_merge(
                    $contextMap[$entityType],
                    $recognizerContext
                );
            }
        }

        // Add user-provided context to all entity types
        foreach ($contextMap as $entityType => $words) {
            $contextMap[$entityType] = array_merge($words, $context);
        }

        // Enhance each result
        foreach ($results as $result) {
            $entityContext = $contextMap[$result->entityType] ?? $context;
            if (empty($entityContext)) {
                continue;
            }

            // Get surrounding text (100 characters before and after)
            $surroundStart = max(0, $result->start - 100);
            $surroundEnd = min(mb_strlen($text, 'UTF-8'), $result->end + 100);
            $surroundingText = mb_strtolower(
                mb_substr($text, $surroundStart, $surroundEnd - $surroundStart, 'UTF-8'),
                'UTF-8'
            );

            // Check if any context word appears
            foreach ($entityContext as $word) {
                if (mb_strpos($surroundingText, mb_strtolower($word, 'UTF-8')) !== false) {
                    // Boost score by 0.35 (matching Presidio), cap at 1.0, floor at 0.4
                    $result->score = min($result->score + 0.35, 1.0);
                    $result->score = max($result->score, 0.4);
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * Remove overlapping and conflicting results.
     *
     * @param RecognizerResult[] $results
     * @return RecognizerResult[]
     */
    private function removeConflicts(array $results): array
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

    /**
     * Remove results that match allow-listed terms.
     *
     * @param string $text Original text
     * @param RecognizerResult[] $results Current results
     * @param string[] $allowList Terms to whitelist
     * @return RecognizerResult[]
     */
    private function removeAllowList(string $text, array $results, array $allowList): array
    {
        $filtered = [];

        foreach ($results as $result) {
            $matchedText = mb_substr($text, $result->start, $result->length(), 'UTF-8');

            // Check if matched text is in allow list (case-insensitive)
            $isAllowed = false;
            foreach ($allowList as $allowedTerm) {
                if (mb_strtolower($matchedText, 'UTF-8') === mb_strtolower($allowedTerm, 'UTF-8')) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                $filtered[] = $result;
            }
        }

        return $filtered;
    }
}
