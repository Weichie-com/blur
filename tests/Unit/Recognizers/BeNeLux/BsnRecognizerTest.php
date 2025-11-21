<?php

declare(strict_types=1);

namespace Weichie\Blur\Tests\Unit\Recognizers\BeNeLux;

use PHPUnit\Framework\TestCase;
use Weichie\Blur\Analyzer\Recognizers\BeNeLux\BsnRecognizer;

class BsnRecognizerTest extends TestCase
{
    private BsnRecognizer $recognizer;

    protected function setUp(): void
    {
        $this->recognizer = new BsnRecognizer();
    }

    /**
     * Valid BSN numbers that pass 11-proof checksum.
     */
    public function testValidBsnNumbers(): void
    {
        $validBSNs = [
            '111222333',    // Valid test BSN
            '123456782',    // Valid BSN
            '111.222.333',  // With dots
            '111 222 333',  // With spaces
        ];

        foreach ($validBSNs as $bsn) {
            $text = "Het BSN is {$bsn} voor deze klant.";
            $results = $this->recognizer->analyze($text, [], 'nl');

            $this->assertNotEmpty($results, "Should detect valid BSN: {$bsn}");
            $this->assertEquals('NL_BSN', $results[0]->entityType);
            $this->assertEquals(1.0, $results[0]->score, "Valid BSN should have score 1.0");
        }
    }

    /**
     * Invalid BSN numbers that fail 11-proof checksum.
     */
    public function testInvalidBsnChecksum(): void
    {
        $invalidBSNs = [
            '111222334',    // Wrong checksum
            '123456789',    // Wrong checksum
            '987654321',    // Wrong checksum
        ];

        foreach ($invalidBSNs as $bsn) {
            $text = "Het BSN is {$bsn} voor deze klant.";
            $results = $this->recognizer->analyze($text, [], 'nl');

            $this->assertEmpty($results, "Should NOT detect invalid BSN: {$bsn}");
        }
    }

    /**
     * BSN numbers with all same digits should be invalidated.
     */
    public function testInvalidSameDigits(): void
    {
        $invalidBSNs = [
            '111111111',
            '222222222',
            '999999999',
        ];

        foreach ($invalidBSNs as $bsn) {
            $text = "Het BSN is {$bsn} voor deze klant.";
            $results = $this->recognizer->analyze($text, [], 'nl');

            $this->assertEmpty($results, "Should NOT detect same-digit BSN: {$bsn}");
        }
    }

    /**
     * All zeros should be invalid.
     */
    public function testInvalidAllZeros(): void
    {
        $text = "Het BSN is 000000000 voor deze klant.";
        $results = $this->recognizer->analyze($text, [], 'nl');

        $this->assertEmpty($results);
    }

    /**
     * BSN with wrong length should not be detected.
     */
    public function testInvalidLength(): void
    {
        $invalidBSNs = [
            '12345678',     // Too short
            '1234567890',   // Too long
            '12345',        // Way too short
        ];

        foreach ($invalidBSNs as $bsn) {
            $text = "Het BSN is {$bsn} voor deze klant.";
            $results = $this->recognizer->analyze($text, [], 'nl');

            $this->assertEmpty($results, "Should NOT detect wrong-length BSN: {$bsn}");
        }
    }

    /**
     * BSN with letters should not be detected.
     */
    public function testInvalidNonNumeric(): void
    {
        $text = "Het BSN is 11122233A voor deze klant.";
        $results = $this->recognizer->analyze($text, [], 'nl');

        $this->assertEmpty($results);
    }

    /**
     * Test context words boost confidence.
     */
    public function testContextWords(): void
    {
        $contexts = [
            'bsn',
            'burgerservicenummer',
            'sofi',
            'sofinummer',
        ];

        foreach ($contexts as $context) {
            $this->assertContains($context, $this->recognizer->getContext());
        }
    }

    /**
     * Test supported entities.
     */
    public function testSupportedEntities(): void
    {
        $entities = $this->recognizer->getSupportedEntities();

        $this->assertCount(1, $entities);
        $this->assertContains('NL_BSN', $entities);
    }

    /**
     * Test supported languages.
     */
    public function testSupportedLanguages(): void
    {
        $languages = $this->recognizer->getSupportedLanguages();

        $this->assertContains('nl', $languages);
        $this->assertContains('en', $languages);
    }

    /**
     * Test multiple BSNs in same text.
     */
    public function testMultipleBSNsInText(): void
    {
        $text = "BSN 111222333 en BSN 123456782 zijn beide geldig.";
        $results = $this->recognizer->analyze($text, [], 'nl');

        $this->assertCount(2, $results);
        $this->assertEquals('NL_BSN', $results[0]->entityType);
        $this->assertEquals('NL_BSN', $results[1]->entityType);
    }

    /**
     * Test BSN not detected in unsupported language.
     */
    public function testUnsupportedLanguage(): void
    {
        $text = "Het BSN is 111222333 voor deze klant.";
        $results = $this->recognizer->analyze($text, [], 'fr'); // French not supported

        $this->assertEmpty($results);
    }

    /**
     * Test entity filtering.
     */
    public function testEntityFiltering(): void
    {
        $text = "Het BSN is 111222333 voor deze klant.";

        // Should detect when NL_BSN is in filter
        $results = $this->recognizer->analyze($text, ['NL_BSN'], 'nl');
        $this->assertNotEmpty($results);

        // Should NOT detect when different entity in filter
        $results = $this->recognizer->analyze($text, ['EMAIL_ADDRESS'], 'nl');
        $this->assertEmpty($results);
    }

    /**
     * Test UTF-8 handling.
     */
    public function testUTF8Handling(): void
    {
        $text = "Het BSN nummer is 111222333 voor café eigenaar.";
        $results = $this->recognizer->analyze($text, [], 'nl');

        $this->assertNotEmpty($results);
        $this->assertEquals('NL_BSN', $results[0]->entityType);
    }
}
