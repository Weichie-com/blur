<?php

declare(strict_types=1);

namespace Weichie\Blur\Analyzer\Recognizers\Generic;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Weichie\Blur\Analyzer\EntityRecognizer;
use Weichie\Blur\Analyzer\Models\RecognizerResult;

/**
 * Recognizes phone numbers using libphonenumber.
 * Focused on BeNeLux countries: Belgium (+32), Netherlands (+31), Luxembourg (+352).
 */
class PhoneRecognizer implements EntityRecognizer
{
    private PhoneNumberUtil $phoneUtil;
    private array $supportedRegions;

    public function __construct(array $supportedRegions = ['BE', 'NL', 'LU'])
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

                // Validate the phone number
                if ($this->phoneUtil->isValidNumber($phoneNumber)) {
                    // Convert byte offset to character offset for UTF-8
                    $charStart = mb_strlen(substr($text, 0, $start), 'UTF-8');
                    $charLength = mb_strlen($rawString, 'UTF-8');

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
