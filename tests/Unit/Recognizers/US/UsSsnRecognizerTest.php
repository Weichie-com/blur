<?php

declare(strict_types=1);

namespace Weichie\Blur\Tests\Unit\Recognizers\US;

use PHPUnit\Framework\TestCase;
use Weichie\Blur\Analyzer\Recognizers\US\UsSsnRecognizer;

class UsSsnRecognizerTest extends TestCase
{
    private UsSsnRecognizer $recognizer;

    protected function setUp(): void
    {
        $this->recognizer = new UsSsnRecognizer();
    }

    public function testValidSsnWithDashes(): void
    {
        $text = "My social security number is 123-45-6789.";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertNotEmpty($results, "Should detect SSN with dashes");
        $this->assertEquals('US_SSN', $results[0]->entityType);
    }

    public function testValidSsnWithDots(): void
    {
        $text = "SSN: 123.45.6789";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertNotEmpty($results, "Should detect SSN with dots");
    }

    public function testValidSsnWithSpaces(): void
    {
        $text = "SSN: 123 45 6789";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertNotEmpty($results, "Should detect SSN with spaces");
    }

    public function testValidSsnNoSeparators(): void
    {
        $text = "SSN: 123456789";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertNotEmpty($results, "Should detect SSN without separators");
    }

    public function testInvalidAreaZero(): void
    {
        $text = "SSN: 000-45-6789";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertEmpty($results, "Area 000 should be invalid");
    }

    public function testInvalidArea666(): void
    {
        $text = "SSN: 666-45-6789";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertEmpty($results, "Area 666 should be invalid");
    }

    public function testInvalidArea900Plus(): void
    {
        $text = "SSN: 900-45-6789";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertEmpty($results, "Area 900+ should be invalid");
    }

    public function testInvalidGroupZero(): void
    {
        $text = "SSN: 123-00-6789";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertEmpty($results, "Group 00 should be invalid");
    }

    public function testInvalidSerialZero(): void
    {
        $text = "SSN: 123-45-0000";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertEmpty($results, "Serial 0000 should be invalid");
    }

    public function testInvalidSameDigits(): void
    {
        $sameDigits = ['111-11-1111', '222-22-2222', '999-99-9999'];

        foreach ($sameDigits as $ssn) {
            $text = "SSN: {$ssn}";
            $results = $this->recognizer->analyze($text, [], 'en');

            $this->assertEmpty($results, "Same-digit SSN should be invalid: {$ssn}");
        }
    }

    public function testInvalidMixedDelimiters(): void
    {
        $mixed = ['123-45.6789', '123.45 6789', '123-45 6789'];

        foreach ($mixed as $ssn) {
            $text = "SSN: {$ssn}";
            $results = $this->recognizer->analyze($text, [], 'en');

            $this->assertEmpty($results, "Mixed delimiter SSN should be invalid: {$ssn}");
        }
    }

    public function testContextWords(): void
    {
        $contexts = ['social', 'security', 'ssn', 'ssns', 'ssid'];

        foreach ($contexts as $context) {
            $this->assertContains($context, $this->recognizer->getContext());
        }
    }

    public function testSupportedEntities(): void
    {
        $entities = $this->recognizer->getSupportedEntities();

        $this->assertCount(1, $entities);
        $this->assertContains('US_SSN', $entities);
    }

    public function testSupportedLanguages(): void
    {
        $this->assertContains('en', $this->recognizer->getSupportedLanguages());
    }

    public function testUnsupportedLanguage(): void
    {
        $text = "SSN: 123-45-6789";
        $results = $this->recognizer->analyze($text, [], 'nl');

        $this->assertEmpty($results);
    }

    public function testEntityFiltering(): void
    {
        $text = "SSN: 123-45-6789";

        $results = $this->recognizer->analyze($text, ['US_SSN'], 'en');
        $this->assertNotEmpty($results);

        $results = $this->recognizer->analyze($text, ['EMAIL_ADDRESS'], 'en');
        $this->assertEmpty($results);
    }

    public function testMultipleSsnsInText(): void
    {
        $text = "SSN 123-45-6789 and SSN 234-56-7890 are both valid.";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertGreaterThanOrEqual(2, count($results));
    }
}
