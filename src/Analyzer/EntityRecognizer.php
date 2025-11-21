<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer;

use Weichie\Blur\Analyzer\Models\RecognizerResult;

/**
 * Base interface for all entity recognizers.
 */
interface EntityRecognizer
{
    /**
     * Analyze text to detect entities.
     *
     * @param string $text The text to analyze
     * @param string[] $entities Filter for specific entity types (empty = all)
     * @param string $language Language code (e.g., 'en', 'nl', 'fr')
     * @return RecognizerResult[]
     */
    public function analyze(string $text, array $entities = [], string $language = 'en'): array;

    /**
     * Get the list of entities this recognizer can detect.
     *
     * @return string[]
     */
    public function getSupportedEntities(): array;

    /**
     * Get the list of languages this recognizer supports.
     *
     * @return string[]
     */
    public function getSupportedLanguages(): array;

    /**
     * Get context words that can boost confidence when found near an entity.
     *
     * @return string[]
     */
    public function getContext(): array;
}
