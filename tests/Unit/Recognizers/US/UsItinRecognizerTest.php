<?php

declare(strict_types=1);

namespace Weichie\Blur\Tests\Unit\Recognizers\US;

use PHPUnit\Framework\TestCase;
use Weichie\Blur\Analyzer\Recognizers\US\UsItinRecognizer;

class UsItinRecognizerTest extends TestCase
{
    private UsItinRecognizer $recognizer;

    protected function setUp(): void
    {
        $this->recognizer = new UsItinRecognizer();
    }

    public function testValidItinWithDashes(): void
    {
        $text = "ITIN: 912-70-1234";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertNotEmpty($results, "Should detect ITIN with dashes");
        $this->assertEquals('US_ITIN', $results[0]->entityType);
    }

    public function testValidItinWithSpaces(): void
    {
        $text = "ITIN: 912 70 1234";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertNotEmpty($results, "Should detect ITIN with spaces");
    }

    public function testValidItinNoSeparators(): void
    {
        $text = "ITIN: 912701234";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertNotEmpty($results, "Should detect ITIN without separators");
    }

    public function testItinMustStartWith9(): void
    {
        $text = "Not an ITIN: 812-70-1234";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertEmpty($results, "ITIN must start with 9");
    }

    public function testContextWords(): void
    {
        $contexts = ['individual', 'taxpayer', 'itin', 'tax', 'payer', 'taxid', 'tin'];

        foreach ($contexts as $context) {
            $this->assertContains($context, $this->recognizer->getContext());
        }
    }

    public function testSupportedEntities(): void
    {
        $entities = $this->recognizer->getSupportedEntities();

        $this->assertCount(1, $entities);
        $this->assertContains('US_ITIN', $entities);
    }

    public function testSupportedLanguages(): void
    {
        $this->assertContains('en', $this->recognizer->getSupportedLanguages());
    }

    public function testUnsupportedLanguage(): void
    {
        $text = "ITIN: 912-70-1234";
        $results = $this->recognizer->analyze($text, [], 'nl');

        $this->assertEmpty($results);
    }

    public function testEntityFiltering(): void
    {
        $text = "ITIN: 912-70-1234";

        $results = $this->recognizer->analyze($text, ['US_ITIN'], 'en');
        $this->assertNotEmpty($results);

        $results = $this->recognizer->analyze($text, ['US_SSN'], 'en');
        $this->assertEmpty($results);
    }
}
