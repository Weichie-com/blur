<?php

declare(strict_types=1);

namespace Weichie\Blur\Tests\Unit\Recognizers\US;

use PHPUnit\Framework\TestCase;
use Weichie\Blur\Analyzer\Recognizers\US\UsBankRecognizer;

class UsBankRecognizerTest extends TestCase
{
    private UsBankRecognizer $recognizer;

    protected function setUp(): void
    {
        $this->recognizer = new UsBankRecognizer();
    }

    public function testValid8DigitAccount(): void
    {
        $text = "Bank account: 12345678";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertNotEmpty($results, "Should detect 8-digit bank account");
        $this->assertEquals('US_BANK_NUMBER', $results[0]->entityType);
    }

    public function testValid17DigitAccount(): void
    {
        $text = "Account number: 12345678901234567";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertNotEmpty($results, "Should detect 17-digit bank account");
    }

    public function testTooShort(): void
    {
        $text = "Account: 1234567";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertEmpty($results, "7 digits should be too short");
    }

    public function testTooLong(): void
    {
        $text = "Account: 123456789012345678";
        $results = $this->recognizer->analyze($text, [], 'en');

        $this->assertEmpty($results, "18 digits should be too long");
    }

    public function testContextWords(): void
    {
        $contexts = ['check', 'account', 'account#', 'acct', 'bank', 'save', 'debit'];

        foreach ($contexts as $context) {
            $this->assertContains($context, $this->recognizer->getContext());
        }
    }

    public function testSupportedEntities(): void
    {
        $entities = $this->recognizer->getSupportedEntities();

        $this->assertCount(1, $entities);
        $this->assertContains('US_BANK_NUMBER', $entities);
    }

    public function testSupportedLanguages(): void
    {
        $this->assertContains('en', $this->recognizer->getSupportedLanguages());
    }

    public function testUnsupportedLanguage(): void
    {
        $text = "Bank account: 12345678";
        $results = $this->recognizer->analyze($text, [], 'nl');

        $this->assertEmpty($results);
    }

    public function testEntityFiltering(): void
    {
        $text = "Bank account: 12345678";

        $results = $this->recognizer->analyze($text, ['US_BANK_NUMBER'], 'en');
        $this->assertNotEmpty($results);

        $results = $this->recognizer->analyze($text, ['US_SSN'], 'en');
        $this->assertEmpty($results);
    }
}
