<?php

declare(strict_types=1);

namespace Weichie\Blur\Anonymizer;

/**
 * Base interface for anonymization operators.
 */
interface Operator
{
    /**
     * Apply the operator to transform the text.
     *
     * @param string $text The original text to transform
     * @param array $params Operator-specific parameters
     * @return string The transformed text
     */
    public function operate(string $text, array $params = []): string;

    /**
     * Get the operator name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Validate operator parameters.
     *
     * @param array $params Parameters to validate
     * @throws \InvalidArgumentException if parameters are invalid
     */
    public function validateParams(array $params): void;
}
