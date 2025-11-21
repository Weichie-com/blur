<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer;

/**
 * Registry for managing entity recognizers.
 */
class RecognizerRegistry
{
    /** @var EntityRecognizer[] */
    private array $recognizers = [];

    /**
     * Add a recognizer to the registry.
     */
    public function addRecognizer(EntityRecognizer $recognizer): void
    {
        $this->recognizers[] = $recognizer;
    }

    /**
     * Get all recognizers that support the given language and entities.
     *
     * @param string $language Language code
     * @param string[] $entities Filter for specific entity types (empty = all)
     * @return EntityRecognizer[]
     */
    public function getRecognizers(string $language = 'en', array $entities = []): array
    {
        $matchingRecognizers = [];

        foreach ($this->recognizers as $recognizer) {
            // Check if recognizer supports the language
            if (!in_array($language, $recognizer->getSupportedLanguages(), true)) {
                continue;
            }

            // If entities filter is provided, check if recognizer supports any of them
            if (!empty($entities)) {
                $supportedEntities = $recognizer->getSupportedEntities();
                $intersection = array_intersect($entities, $supportedEntities);
                if (empty($intersection)) {
                    continue;
                }
            }

            $matchingRecognizers[] = $recognizer;
        }

        return $matchingRecognizers;
    }

    /**
     * Get all recognizers.
     *
     * @return EntityRecognizer[]
     */
    public function getAllRecognizers(): array
    {
        return $this->recognizers;
    }

    /**
     * Get all supported entity types across all recognizers.
     *
     * @param string|null $language Filter by language (null = all languages)
     * @return string[]
     */
    public function getSupportedEntities(?string $language = null): array
    {
        $entities = [];

        foreach ($this->recognizers as $recognizer) {
            if ($language !== null && !in_array($language, $recognizer->getSupportedLanguages(), true)) {
                continue;
            }

            $entities = array_merge($entities, $recognizer->getSupportedEntities());
        }

        return array_values(array_unique($entities));
    }

    /**
     * Get all supported languages across all recognizers.
     *
     * @return string[]
     */
    public function getSupportedLanguages(): array
    {
        $languages = [];

        foreach ($this->recognizers as $recognizer) {
            $languages = array_merge($languages, $recognizer->getSupportedLanguages());
        }

        return array_values(array_unique($languages));
    }

    /**
     * Remove all recognizers from the registry.
     */
    public function clear(): void
    {
        $this->recognizers = [];
    }
}
