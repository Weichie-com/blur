<?php

declare(strict_types=1);

namespace Weichie\Blur\Tests\Unit\Recognizers\US;

use PHPUnit\Framework\TestCase;
use Weichie\Blur\Analyzer\Recognizers\US\UsDriverLicenseRecognizer;

class UsDriverLicenseRecognizerTest extends TestCase
{
    private UsDriverLicenseRecognizer $recognizer;

    protected function setUp(): void
    {
        $this->recognizer = new UsDriverLicenseRecognizer();
    }

    public function testAlphanumericOneLetterDigits(): void
    {
        // 1 letter + 3-8 digits (many states)
        $validNumbers = ['A1234', 'B12345678'];

        foreach ($validNumbers as $number) {
            $text = "Driver license: {$number}";
            $results = $this->recognizer->analyze($text, [], 'en');

            $this->assertNotEmpty($results, "Should detect alphanumeric DL: {$number}");
            $this->assertEquals('US_DRIVER_LICENSE', $results[0]->entityType);
        }
    }

    public function testAlphanumericTwoLettersDigits(): void
    {
        // 2 letters + 2-7 digits
        $text = "Driver license: AB1234567";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertNotEmpty($results, "Should detect 2-letter alphanumeric DL");
    }

    public function testDigitsOnlyFormat(): void
    {
        // 6-14 digits
        $text = "License number: 12345678";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertNotEmpty($results, "Should detect numeric-only DL");
    }

    public function testFormattedWithDashes(): void
    {
        // X00-00-0000 format
        $text = "License: A12-34-5678";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertNotEmpty($results, "Should detect formatted DL with dashes");
    }

    public function testContextWords(): void
    {
        $contexts = ['driver', 'license', 'permit', 'lic', 'identification', 'dls', 'cdls', 'lic#', 'driving'];

        foreach ($contexts as $context) {
            $this->assertContains($context, $this->recognizer->getContext());
        }
    }

    public function testSupportedEntities(): void
    {
        $entities = $this->recognizer->getSupportedEntities();

        $this->assertCount(1, $entities);
        $this->assertContains('US_DRIVER_LICENSE', $entities);
    }

    public function testSupportedLanguages(): void
    {
        $this->assertContains('en', $this->recognizer->getSupportedLanguages());
    }

    public function testUnsupportedLanguage(): void
    {
        $text = "Driver license: A1234567";
        $results = $this->recognizer->analyze($text, [], 'nl');

        $this->assertEmpty($results);
    }

    public function testEntityFiltering(): void
    {
        $text = "Driver license: A1234567";

        $results = $this->recognizer->analyze($text, ['US_DRIVER_LICENSE'], 'en');
        $this->assertNotEmpty($results);

        $results = $this->recognizer->analyze($text, ['US_SSN'], 'en');
        $this->assertEmpty($results);
    }
}
