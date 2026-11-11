<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Recognizers\Generic;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Weichie\Blur\Analyzer\EntityRecognizer;
use Weichie\Blur\Analyzer\Models\RecognizerResult;

/**
 * Recognizes phone numbers using libphonenumber.
 * Supports BeNeLux countries: Belgium (+32), Netherlands (+31), Luxembourg (+352),
 * and US (+1).
 */
class PhoneRecognizer implements EntityRecognizer
{
    private PhoneNumberUtil $phoneUtil;
    private array $supportedRegions;

    public function __construct(array $supportedRegions = ['BE', 'NL', 'LU', 'US'])
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
        $this->supportedRegions = $supportedRegions;
    }

    public function analyze(string $text, array $entities = [], string $language = 'en'): array
    {
        // Filter entities if specified
        if (!empty($entities) && !in_array('PHONE_NUMBER', $entities, true)) {
            return [];
        }

        $results = [];

        // Try to find phone numbers for each supported region
        foreach ($this->supportedRegions as $region) {
            $matches = $this->phoneUtil->findNumbers($text, $region);

            foreach ($matches as $match) {
                $phoneNumber = $match->number();
                $start = $match->start();
                $rawString = $match->rawString();

                // Skip short digit-only matches (e.g. years like "2024")
                $digitCount = strlen(preg_replace('/\D/', '', $rawString));
                if ($digitCount < 6) {
                    continue;
                }

                // Validate the phone number
                if ($this->phoneUtil->isValidNumber($phoneNumber)) {
                    // libphonenumber (giggsey) already returns a UTF-8 character
                    // offset, not a byte offset. Use it as is, and verify that
                    // the span really holds the raw match before trusting it.
                    $charLength = mb_strlen($rawString, 'UTF-8');
                    $charStart = $this->resolveCharStart($text, $rawString, $start, $charLength);
                    if ($charStart === null) {
                        continue;
                    }

                    $results[] = new RecognizerResult(
                        entityType: 'PHONE_NUMBER',
                        start: $charStart,
                        end: $charStart + $charLength,
                        score: 0.75,
                        recognitionMetadata: [
                            'recognizer' => static::class,
                            'region' => $region,
                            'country_code' => $phoneNumber->getCountryCode()
                        ]
                    );
                }
            }
        }

        // Remove duplicates (same position)
        return $this->removeDuplicates($results);
    }

    public function getSupportedEntities(): array
    {
        return ['PHONE_NUMBER'];
    }

    public function getSupportedLanguages(): array
    {
        return ['en', 'nl', 'fr', 'de', 'lb'];
    }

    public function getContext(): array
    {
        return [
            'phone', 'telefoon', 'téléphone', 'tel', 'mobile',
            'mobiel', 'gsm', 'cell', 'number', 'nummer', 'numéro'
        ];
    }

    /**
     * Return the character offset where $rawString starts in $text.
     * Trusts the libphonenumber offset when it points at the raw match;
     * otherwise re-locates the match by value (multibyte safe), picking the
     * occurrence closest to the reported offset.
     */
    private function resolveCharStart(string $text, string $rawString, int $start, int $charLength): ?int
    {
        if (mb_substr($text, $start, $charLength, 'UTF-8') === $rawString) {
            return $start;
        }

        $best = null;
        $offset = 0;
        while (($pos = mb_strpos($text, $rawString, $offset, 'UTF-8')) !== false) {
            if ($best === null || abs($pos - $start) < abs($best - $start)) {
                $best = $pos;
            }
            $offset = $pos + 1;
        }

        return $best;
    }

    private function removeDuplicates(array $results): array
    {
        if (empty($results)) {
            return [];
        }

        $uniqueResults = [];
        $positions = [];

        foreach ($results as $result) {
            $key = "{$result->start}-{$result->end}";

            // Keep the one with higher score if same position
            if (!isset($positions[$key]) || $result->score > $positions[$key]->score) {
                $positions[$key] = $result;
            }
        }

        return array_values($positions);
    }
}
