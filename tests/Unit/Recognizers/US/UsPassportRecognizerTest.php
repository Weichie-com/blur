<?php

declare(strict_types=1);

namespace Weichie\Blur\Tests\Unit\Recognizers\US;

use PHPUnit\Framework\TestCase;
use Weichie\Blur\Analyzer\Recognizers\US\UsPassportRecognizer;

class UsPassportRecognizerTest extends TestCase
{
    private UsPassportRecognizer $recognizer;

    protected function setUp(): void
    {
        $this->recognizer = new UsPassportRecognizer();
    }

    public function testTraditional9DigitPassport(): void
    {
        $text = "Passport number: 123456789";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertNotEmpty($results, "Should detect 9-digit passport number");
        $this->assertEquals('US_PASSPORT', $results[0]->entityType);
    }

    public function testNextGenPassport(): void
    {
        $text = "Passport number: A12345678";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertNotEmpty($results, "Should detect next-gen passport (letter + 8 digits)");
        $this->assertEquals('US_PASSPORT', $results[0]->entityType);
    }

    public function testNextGenPassportLowercaseNotDetected(): void
    {
        $text = "Passport number: a12345678";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertEmpty($results, "Lowercase letter should not match next-gen pattern");
    }

    public function testContextWords(): void
    {
        $contexts = ['us', 'united', 'states', 'passport', 'passport#', 'travel', 'document'];

        foreach ($contexts as $context) {
            $this->assertContains($context, $this->recognizer->getContext());
        }
    }

    public function testSupportedEntities(): void
    {
        $entities = $this->recognizer->getSupportedEntities();

        $this->assertCount(1, $entities);
        $this->assertContains('US_PASSPORT', $entities);
    }

    public function testSupportedLanguages(): void
    {
        $this->assertContains('en', $this->recognizer->getSupportedLanguages());
    }

    public function testUnsupportedLanguage(): void
    {
        $text = "Passport number: 123456789";
        $results = $this->recognizer->analyze($text, [], 'nl');

        $this->assertEmpty($results);
    }

    public function testEntityFiltering(): void
    {
        $text = "Passport number: 123456789";

        $results = $this->recognizer->analyze($text, ['US_PASSPORT'], 'en');
        $this->assertNotEmpty($results);

        $results = $this->recognizer->analyze($text, ['US_SSN'], 'en');
        $this->assertEmpty($results);
    }
}
