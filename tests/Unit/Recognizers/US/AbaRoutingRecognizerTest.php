<?php

declare(strict_types=1);

namespace Weichie\Blur\Tests\Unit\Recognizers\US;

use PHPUnit\Framework\TestCase;
use Weichie\Blur\Analyzer\Recognizers\US\AbaRoutingRecognizer;

class AbaRoutingRecognizerTest extends TestCase
{
    private AbaRoutingRecognizer $recognizer;

    protected function setUp(): void
    {
        $this->recognizer = new AbaRoutingRecognizer();
    }

    public function testValidRoutingNumber(): void
    {
        // 021000021 is a real JPMorgan Chase routing number (valid checksum)
        $text = "Routing number: 021000021";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertNotEmpty($results, "Should detect valid ABA routing number");
        $this->assertEquals('US_ABA_ROUTING', $results[0]->entityType);
        $this->assertEquals(1.0, $results[0]->score, "Valid checksum should yield score 1.0");
    }

    public function testValidRoutingNumber2(): void
    {
        // 011401533 is a valid routing number
        $text = "Routing: 011401533";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertNotEmpty($results, "Should detect valid ABA routing number");
        $this->assertEquals(1.0, $results[0]->score);
    }

    public function testInvalidChecksum(): void
    {
        $text = "Routing: 123456780";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertEmpty($results, "Invalid checksum should be rejected");
    }

    public function testInvalidSameDigits(): void
    {
        $sameDigits = ['111111111', '222222222', '999999999'];

        foreach ($sameDigits as $number) {
            $text = "Routing: {$number}";
            $results = $this->recognizer->analyze($text, [], 'en');

            $this->assertEmpty($results, "Same-digit routing number should be invalid: {$number}");
        }
    }

    public function testContextWords(): void
    {
        $contexts = ['routing', 'aba', 'transit', 'rtn', 'routing#', 'routing number'];

        foreach ($contexts as $context) {
            $this->assertContains($context, $this->recognizer->getContext());
        }
    }

    public function testSupportedEntities(): void
    {
        $entities = $this->recognizer->getSupportedEntities();

        $this->assertCount(1, $entities);
        $this->assertContains('US_ABA_ROUTING', $entities);
    }

    public function testSupportedLanguages(): void
    {
        $this->assertContains('en', $this->recognizer->getSupportedLanguages());
    }

    public function testUnsupportedLanguage(): void
    {
        $text = "Routing: 021000021";
        $results = $this->recognizer->analyze($text, [], 'nl');

        $this->assertEmpty($results);
    }

    public function testEntityFiltering(): void
    {
        $text = "Routing: 021000021";

        $results = $this->recognizer->analyze($text, ['US_ABA_ROUTING'], 'en');
        $this->assertNotEmpty($results);

        $results = $this->recognizer->analyze($text, ['US_SSN'], 'en');
        $this->assertEmpty($results);
    }
}
