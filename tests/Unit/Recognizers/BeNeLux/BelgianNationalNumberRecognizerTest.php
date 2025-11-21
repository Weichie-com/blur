<?php

declare(strict_types=1);

namespace Weichie\Blur\Tests\Unit\Recognizers\BeNeLux;

use PHPUnit\Framework\TestCase;
use Weichie\Blur\Analyzer\Recognizers\BeNeLux\BelgianNationalNumberRecognizer;

class BelgianNationalNumberRecognizerTest extends TestCase
{
    private BelgianNationalNumberRecognizer $recognizer;

    protected function setUp(): void
    {
        $this->recognizer = new BelgianNationalNumberRecognizer();
    }

    /**
     * Valid Belgian National Numbers.
     */
    public function testValidBelgianNationalNumbers(): void
    {
        $validNumbers = [
            '85.07.30-033.28',  // Valid formatted (checksum: 97 - (850730033 % 97) = 28)
            '85073003328',      // Valid unformatted
            '00.01.01-001.05',  // Valid for people born in 2000 (prepend "2": 97 - (2000101001 % 97) = 05)
        ];

        foreach ($validNumbers as $number) {
            $text = "Le numéro national est {$number} pour ce client.";
            $results = $this->recognizer->analyze($text, [], 'fr');

            $this->assertNotEmpty($results, "Should detect valid number: {$number}");
            $this->assertEquals('BE_NATIONAL_NUMBER', $results[0]->entityType);
            $this->assertEquals(1.0, $results[0]->score);
        }
    }

    /**
     * Invalid Belgian National Numbers (wrong checksum).
     */
    public function testInvalidChecksum(): void
    {
        $invalidNumbers = [
            '85.07.30-033.61',  // Wrong checksum (should be 28)
            '85073003361',      // Wrong checksum
        ];

        foreach ($invalidNumbers as $number) {
            $text = "Le numéro national est {$number} pour ce client.";
            $results = $this->recognizer->analyze($text, [], 'fr');

            $this->assertEmpty($results, "Should NOT detect invalid number: {$number}");
        }
    }

    /**
     * Invalid date components.
     */
    public function testInvalidDateComponents(): void
    {
        $invalidNumbers = [
            '85.13.30-033.61',  // Invalid month (13)
            '85.07.32-033.61',  // Invalid day (32)
            '85.00.15-033.61',  // Invalid month (00)
        ];

        foreach ($invalidNumbers as $number) {
            $text = "Le numéro national est {$number} pour ce client.";
            $results = $this->recognizer->analyze($text, [], 'fr');

            $this->assertEmpty($results, "Should NOT detect invalid date: {$number}");
        }
    }

    /**
     * All same digits should be invalid.
     */
    public function testInvalidSameDigits(): void
    {
        $text = "Le numéro national est 11111111111 pour ce client.";
        $results = $this->recognizer->analyze($text, [], 'fr');

        $this->assertEmpty($results);
    }

    /**
     * All zeros should be invalid.
     */
    public function testInvalidAllZeros(): void
    {
        $text = "Le numéro national est 00000000000 pour ce client.";
        $results = $this->recognizer->analyze($text, [], 'fr');

        $this->assertEmpty($results);
    }

    /**
     * Test supported entities.
     */
    public function testSupportedEntities(): void
    {
        $entities = $this->recognizer->getSupportedEntities();

        $this->assertCount(1, $entities);
        $this->assertContains('BE_NATIONAL_NUMBER', $entities);
    }

    /**
     * Test supported languages (Dutch, French, German, English).
     */
    public function testSupportedLanguages(): void
    {
        $languages = $this->recognizer->getSupportedLanguages();

        $this->assertContains('nl', $languages);
        $this->assertContains('fr', $languages);
        $this->assertContains('de', $languages);
        $this->assertContains('en', $languages);
    }

    /**
     * Test context words in multiple languages.
     */
    public function testContextWords(): void
    {
        $context = $this->recognizer->getContext();

        // Dutch
        $this->assertContains('rijksregisternummer', $context);

        // French
        $this->assertContains('registre national', $context);

        // English
        $this->assertContains('national number', $context);
    }

    /**
     * Test formatted vs unformatted numbers.
     */
    public function testFormattedAndUnformatted(): void
    {
        $text = "Numbers: 85.07.30-033.28 and 85073003328 are the same.";
        $results = $this->recognizer->analyze($text, [], 'nl');

        $this->assertCount(2, $results);
        $this->assertEquals('BE_NATIONAL_NUMBER', $results[0]->entityType);
        $this->assertEquals('BE_NATIONAL_NUMBER', $results[1]->entityType);
    }

    /**
     * Test people born after 2000 (special handling).
     */
    public function testPeopleBornAfter2000(): void
    {
        // For people born after 2000, the algorithm adds "2" before the 9 digits
        $text = "Le numéro national est 00.01.01-001.05 pour ce client.";
        $results = $this->recognizer->analyze($text, [], 'fr');

        $this->assertNotEmpty($results);
        $this->assertEquals(1.0, $results[0]->score);
    }

    /**
     * Test UTF-8 handling with special characters.
     */
    public function testUTF8Handling(): void
    {
        $text = "Le numéro de sécurité sociale est 85.07.30-033.28 pour Françoise.";
        $results = $this->recognizer->analyze($text, [], 'fr');

        $this->assertNotEmpty($results);
        $this->assertEquals('BE_NATIONAL_NUMBER', $results[0]->entityType);
    }
}
