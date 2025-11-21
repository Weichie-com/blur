<?php

declare(strict_types=1);

namespace Weichie\Blur\Anonymizer;

use Weichie\Blur\Analyzer\Models\RecognizerResult;
use Weichie\Blur\Anonymizer\Models\EngineResult;
use Weichie\Blur\Anonymizer\Models\OperatorConfig;
use Weichie\Blur\Anonymizer\Models\OperatorResult;

/**
 * Main engine for anonymizing detected PII entities.
 */
class AnonymizerEngine
{
    /** @var array<string, Operator> */
    private array $operators = [];

    public function __construct()
    {
        // Operators will be registered separately
    }

    /**
     * Register an operator.
     */
    public function addOperator(Operator $operator): void
    {
        $this->operators[$operator->getName()] = $operator;
    }

    /**
     * Anonymize text based on analyzer results.
     *
     * @param string $text Original text
     * @param RecognizerResult[] $analyzerResults Detected entities
     * @param array<string, OperatorConfig> $operators Mapping of entity type to operator config
     * @return EngineResult
     */
    public function anonymize(
        string $text,
        array $analyzerResults,
        array $operators = []
    ): EngineResult {
        if (empty($analyzerResults)) {
            $result = new EngineResult();
            $result->setText($text);
            return $result;
        }

        // Sort results and handle conflicts
        $sortedResults = $this->sortAndRemoveConflicts($analyzerResults);

        // Merge adjacent entities of the same type with only whitespace between
        $mergedResults = $this->mergeAdjacentEntities($text, $sortedResults);

        // Process from END to START to preserve indices
        $reversedResults = array_reverse($mergedResults);

        $textBuilder = new TextReplaceBuilder($text);
        $engineResult = new EngineResult();

        foreach ($reversedResults as $entity) {
            // Get operator for this entity type (or DEFAULT)
            $operatorConfig = $operators[$entity->entityType] ?? $operators['DEFAULT'] ?? null;

            if ($operatorConfig === null) {
                // No operator specified, use replace with entity type
                $operatorConfig = OperatorConfig::replace("<{$entity->entityType}>");
            }

            // Get the operator
            $operator = $this->operators[$operatorConfig->operatorName] ?? null;

            if ($operator === null) {
                throw new \RuntimeException("Operator '{$operatorConfig->operatorName}' not found");
            }

            // Validate parameters
            $operator->validateParams($operatorConfig->params);

            // Get original text
            $originalText = mb_substr($text, $entity->start, $entity->end - $entity->start, 'UTF-8');

            // Apply operator
            $newText = $operator->operate($originalText, $operatorConfig->params);

            // Replace in text
            $textBuilder->replace($entity->start, $entity->end, $newText);

            // Record result
            $operatorResult = new OperatorResult(
                start: $entity->start,
                end: $entity->end,
                entityType: $entity->entityType,
                text: $newText,
                operator: $operatorConfig->operatorName
            );
            $engineResult->addItem($operatorResult);
        }

        // Set final text and normalize indices
        $engineResult->setText($textBuilder->getText());
        $engineResult->normalizeItemIndexes();

        return $engineResult;
    }

    /**
     * Get list of registered operators.
     *
     * @return string[]
     */
    public function getOperators(): array
    {
        return array_keys($this->operators);
    }

    /**
     * Sort results by start position and remove conflicts.
     *
     * @param RecognizerResult[] $results
     * @return RecognizerResult[]
     */
    private function sortAndRemoveConflicts(array $results): array
    {
        // Sort by start position, then by end position (descending)
        usort($results, function ($a, $b) {
            if ($a->start !== $b->start) {
                return $a->start <=> $b->start;
            }
            return $b->end <=> $a->end; // Longer spans first
        });

        // Remove conflicts
        $uniqueResults = [];
        foreach ($results as $result) {
            $hasConflict = false;
            foreach ($uniqueResults as $existing) {
                if ($result->hasConflict($existing)) {
                    $hasConflict = true;
                    break;
                }
            }
            if (!$hasConflict) {
                $uniqueResults[] = $result;
            }
        }

        return $uniqueResults;
    }

    /**
     * Merge adjacent entities of the same type with only whitespace between them.
     *
     * @param string $text Original text
     * @param RecognizerResult[] $results Sorted results
     * @return RecognizerResult[]
     */
    private function mergeAdjacentEntities(string $text, array $results): array
    {
        if (empty($results)) {
            return [];
        }

        $merged = [];
        $current = $results[0];

        for ($i = 1; $i < count($results); $i++) {
            $next = $results[$i];

            // Check if same entity type and only whitespace between
            if ($current->entityType === $next->entityType) {
                $between = mb_substr($text, $current->end, $next->start - $current->end, 'UTF-8');

                if (trim($between) === '') {
                    // Merge them
                    $current = new RecognizerResult(
                        entityType: $current->entityType,
                        start: $current->start,
                        end: $next->end,
                        score: max($current->score, $next->score)
                    );
                    continue;
                }
            }

            // Can't merge, add current and move to next
            $merged[] = $current;
            $current = $next;
        }

        // Add the last one
        $merged[] = $current;

        return $merged;
    }
}
