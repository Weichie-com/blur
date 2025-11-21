<?php

declare(strict_types=1);

namespace Weichie\Blur\Tests\Unit\Recognizers\Generic;

use PHPUnit\Framework\TestCase;
use Weichie\Blur\Analyzer\Recognizers\Generic\IbanRecognizer;

class IbanRecognizerTest extends TestCase
{
    private IbanRecognizer $recognizer;

    protected function setUp(): void
    {
        $this->recognizer = new IbanRecognizer();
    }

    /**
     * Valid BeNeLux IBANs with correct mod-97 checksum.
     */
    public function testValidBeNeLuxIBANs(): void
    {
        $validIBANs = [
            // Netherlands
            'NL91ABNA0417164300',
            'NL91 ABNA 0417 1643 00',  // With spaces
            'NL02ABNA0123456789',

            // Belgium
            'BE68539007547034',
            'BE68 5390 0754 7034',     // With spaces
            'BE71096123456769',

            // Luxembourg
            'LU280019400644750000',
            'LU28 0019 4006 4475 0000', // With spaces
        ];

        foreach ($validIBANs as $iban) {
            $text = "IBAN: {$iban}";
            $results = $this->recognizer->analyze($text);

            $this->assertNotEmpty($results, "Should detect valid IBAN: {$iban}");
            $this->assertEquals('IBAN_CODE', $results[0]->entityType);
            $this->assertEquals(1.0, $results[0]->score, "Valid IBAN should have score 1.0");
        }
    }

    /**
     * Invalid IBANs with wrong checksum.
     */
    public function testInvalidChecksumIBANs(): void
    {
        $invalidIBANs = [
            'NL00ABNA0417164300',  // Wrong checksum (00)
            'BE00539007547034',     // Wrong checksum
            'LU00001940064475 0000', // Wrong checksum
        ];

        foreach ($invalidIBANs as $iban) {
            $text = "IBAN: {$iban}";
            $results = $this->recognizer->analyze($text);

            $this->assertEmpty($results, "Should NOT detect invalid IBAN: {$iban}");
        }
    }

    /**
     * Invalid IBAN with wrong length.
     */
    public function testInvalidLengthIBANs(): void
    {
        $invalidIBANs = [
            'NL91ABNA041716430',      // Too short (17 instead of 18)
            'NL91ABNA04171643000',    // Too long (19 instead of 18)
            'BE6853900754703',        // Too short (15 instead of 16)
            'BE685390075470344',      // Too long (17 instead of 16)
        ];

        foreach ($invalidIBANs as $iban) {
            $text = "IBAN: {$iban}";
            $results = $this->recognizer->analyze($text);

            $this->assertEmpty($results, "Should NOT detect wrong-length IBAN: {$iban}");
        }
    }

    /**
     * IBAN with invalid format should not be detected.
     */
    public function testInvalidFormatIBANs(): void
    {
        $invalidIBANs = [
            '91NLABNA0417164300',      // Country code in wrong position
            'NLAABNA0417164300',       // Missing check digits
            'NL91123A0417164300',      // Invalid characters in NL IBAN (should be 4 letters)
        ];

        foreach ($invalidIBANs as $iban) {
            $text = "IBAN: {$iban}";
            $results = $this->recognizer->analyze($text);

            $this->assertEmpty($results, "Should NOT detect invalid format: {$iban}");
        }
    }

    /**
     * Test supported entities.
     */
    public function testSupportedEntities(): void
    {
        $entities = $this->recognizer->getSupportedEntities();

        $this->assertCount(1, $entities);
        $this->assertContains('IBAN_CODE', $entities);
    }

    /**
     * Test context words in multiple languages.
     */
    public function testContextWords(): void
    {
        $context = $this->recognizer->getContext();

        $this->assertContains('iban', $context);
        $this->assertContains('bank', $context);
        $this->assertContains('account', $context);
        $this->assertContains('rekening', $context);      // Dutch
        $this->assertContains('compte', $context);        // French
        $this->assertContains('bankrekening', $context);  // Dutch
    }

    /**
     * Test multiple IBANs in same text.
     */
    public function testMultipleIBANsInText(): void
    {
        $text = "Transfer from NL91ABNA0417164300 to BE68539007547034";
        $results = $this->recognizer->analyze($text);

        $this->assertCount(2, $results);
        $this->assertEquals('IBAN_CODE', $results[0]->entityType);
        $this->assertEquals('IBAN_CODE', $results[1]->entityType);
    }

    /**
     * Test IBAN with and without spaces.
     */
    public function testIBANWithAndWithoutSpaces(): void
    {
        $withSpaces = "IBAN: NL91 ABNA 0417 1643 00";
        $withoutSpaces = "IBAN: NL91ABNA0417164300";

        $results1 = $this->recognizer->analyze($withSpaces);
        $results2 = $this->recognizer->analyze($withoutSpaces);

        $this->assertNotEmpty($results1);
        $this->assertNotEmpty($results2);
        $this->assertEquals(1.0, $results1[0]->score);
        $this->assertEquals(1.0, $results2[0]->score);
    }

    /**
     * Test mod-97 algorithm with edge cases.
     */
    public function testMod97EdgeCases(): void
    {
        // Test IBAN with checksum 01
        $text = "IBAN: BE62510007547061";
        $results = $this->recognizer->analyze($text);

        // This should be validated correctly
        $this->assertNotEmpty($results);
    }

    /**
     * Test UTF-8 handling.
     */
    public function testUTF8Handling(): void
    {
        $text = "Le compte bancaire IBAN NL91ABNA0417164300 pour François.";
        $results = $this->recognizer->analyze($text);

        $this->assertNotEmpty($results);
        $this->assertEquals('IBAN_CODE', $results[0]->entityType);
    }

    /**
     * Test non-BeNeLux IBANs are not detected.
     */
    public function testNonBeNeLuxIBANsNotDetected(): void
    {
        $nonBeNeLuxIBANs = [
            'DE89370400440532013000',  // Germany
            'FR1420041010050500013M02606', // France
            'GB29NWBK60161331926819',  // UK
        ];

        foreach ($nonBeNeLuxIBANs as $iban) {
            $text = "IBAN: {$iban}";
            $results = $this->recognizer->analyze($text);

            $this->assertEmpty($results, "Should NOT detect non-BeNeLux IBAN: {$iban}");
        }
    }
}
