<?php

declare(strict_types=1);

namespace Weichie\Blur\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Weichie\Blur\Analyzer\AnalyzerEngine;
use Weichie\Blur\Analyzer\RecognizerRegistry;
use Weichie\Blur\Analyzer\Recognizers\Generic\EmailRecognizer;
use Weichie\Blur\Analyzer\Recognizers\Generic\CreditCardRecognizer;
use Weichie\Blur\Analyzer\Recognizers\Generic\IbanRecognizer;
use Weichie\Blur\Analyzer\Recognizers\BeNeLux\BsnRecognizer;
use Weichie\Blur\Analyzer\Recognizers\BeNeLux\BelgianNationalNumberRecognizer;
use Weichie\Blur\Anonymizer\AnonymizerEngine;
use Weichie\Blur\Anonymizer\Models\OperatorConfig;
use Weichie\Blur\Anonymizer\Operators\ReplaceOperator;
use Weichie\Blur\Anonymizer\Operators\MaskOperator;
use Weichie\Blur\Anonymizer\Operators\HashOperator;
use Weichie\Blur\Anonymizer\Operators\RedactOperator;
use Weichie\Blur\Anonymizer\Operators\EncryptOperator;
use Weichie\Blur\Anonymizer\Operators\DecryptOperator;

class FullPipelineTest extends TestCase
{
    private AnalyzerEngine $analyzer;
    private AnonymizerEngine $anonymizer;

    protected function setUp(): void
    {
        // Setup analyzer
        $registry = new RecognizerRegistry();
        $registry->addRecognizer(new EmailRecognizer());
        $registry->addRecognizer(new CreditCardRecognizer());
        $registry->addRecognizer(new IbanRecognizer());
        $registry->addRecognizer(new BsnRecognizer());
        $registry->addRecognizer(new BelgianNationalNumberRecognizer());

        $this->analyzer = new AnalyzerEngine($registry);

        // Setup anonymizer
        $this->anonymizer = new AnonymizerEngine();
        $this->anonymizer->addOperator(new ReplaceOperator());
        $this->anonymizer->addOperator(new MaskOperator());
        $this->anonymizer->addOperator(new HashOperator());
        $this->anonymizer->addOperator(new RedactOperator());
        $this->anonymizer->addOperator(new EncryptOperator());
        $this->anonymizer->addOperator(new DecryptOperator());
    }

    /**
     * Test complete pipeline: detect and anonymize.
     */
    public function testFullPipelineDetectAndAnonymize(): void
    {
        $text = "Contact Jan at jan@example.com, BSN: 111222333, Card: 4532015112830366";

        // Step 1: Analyze
        $results = $this->analyzer->analyze($text, 'nl');

        $this->assertCount(3, $results);

        // Step 2: Anonymize
        $operators = [
            'EMAIL_ADDRESS' => OperatorConfig::replace('[EMAIL]'),
            'NL_BSN' => OperatorConfig::mask('*', 6),
            'CREDIT_CARD' => OperatorConfig::redact(),
        ];

        $anonymized = $this->anonymizer->anonymize($text, $results, $operators);

        $this->assertStringContainsString('[EMAIL]', $anonymized->getText());
        $this->assertStringContainsString('******333', $anonymized->getText());
        $this->assertStringNotContainsString('4532015112830366', $anonymized->getText());
    }

    /**
     * Test overlapping entities are handled correctly.
     */
    public function testOverlappingEntities(): void
    {
        // Create a scenario where entities might overlap
        $text = "Email: test@example.com and another@example.com";

        $results = $this->analyzer->analyze($text, 'nl');

        $this->assertGreaterThanOrEqual(2, count($results));

        // Anonymize
        $operators = ['EMAIL_ADDRESS' => OperatorConfig::replace('[EMAIL]')];
        $anonymized = $this->anonymizer->anonymize($text, $results, $operators);

        // Both emails should be replaced
        $this->assertStringNotContainsString('test@example.com', $anonymized->getText());
        $this->assertStringNotContainsString('another@example.com', $anonymized->getText());
    }

    /**
     * Test context enhancement improves detection.
     */
    public function testContextEnhancement(): void
    {
        $text = "Het BSN nummer is 111222333 voor deze klant.";

        // Without context
        $resultsWithoutContext = $this->analyzer->analyze($text, 'nl');

        // With context
        $resultsWithContext = $this->analyzer->analyze(
            text: $text,
            language: 'nl',
            context: ['bsn', 'nummer', 'klant']
        );

        $this->assertNotEmpty($resultsWithContext);

        // Context should boost confidence (though in this case BSN validation already gives 1.0)
        $this->assertGreaterThanOrEqual($resultsWithoutContext[0]->score, $resultsWithContext[0]->score);
    }

    /**
     * Test allow list filters out whitelisted entities.
     */
    public function testAllowList(): void
    {
        $text = "Email test@example.com and admin@example.com";

        // Without allow list
        $results = $this->analyzer->analyze($text, 'nl');
        $this->assertCount(2, $results);

        // With allow list
        $resultsWithAllowList = $this->analyzer->analyze(
            text: $text,
            language: 'nl',
            allowList: ['admin@example.com']
        );

        $this->assertCount(1, $resultsWithAllowList);

        // The remaining one should be test@example.com
        $detectedText = mb_substr($text, $resultsWithAllowList[0]->start, $resultsWithAllowList[0]->length(), 'UTF-8');
        $this->assertEquals('test@example.com', $detectedText);
    }

    /**
     * Test score threshold filtering.
     */
    public function testScoreThreshold(): void
    {
        $text = "Contact at test@example.com";

        // Low threshold (accept everything)
        $resultsLow = $this->analyzer->analyze($text, 'nl', [], 0.1);
        $this->assertNotEmpty($resultsLow);

        // High threshold (very strict)
        $resultsHigh = $this->analyzer->analyze($text, 'nl', [], 0.99);

        // Email validation gives 1.0, so should still be detected
        $this->assertNotEmpty($resultsHigh);
    }

    /**
     * Test entity filtering.
     */
    public function testEntityFiltering(): void
    {
        $text = "BSN: 111222333, Email: test@example.com";

        // Only look for BSN
        $results = $this->analyzer->analyze($text, 'nl', ['NL_BSN']);

        $this->assertCount(1, $results);
        $this->assertEquals('NL_BSN', $results[0]->entityType);
    }

    /**
     * Test multiple BeNeLux identifiers in one text.
     */
    public function testMultipleBeNeLuxIdentifiers(): void
    {
        $text = "Dutch BSN: 111222333, Belgian Number: 85.07.30-033.61, IBAN: NL91ABNA0417164300";

        $results = $this->analyzer->analyze($text, 'nl');

        // Should detect: BSN, Belgian National Number (as phone due to format), IBAN
        $this->assertGreaterThanOrEqual(2, count($results));

        $entityTypes = array_map(fn($r) => $r->entityType, $results);

        $this->assertContains('NL_BSN', $entityTypes);
        $this->assertContains('IBAN_CODE', $entityTypes);
    }

    /**
     * Test UTF-8 text handling throughout pipeline.
     */
    public function testUTF8Pipeline(): void
    {
        $text = "L'email de François est test@example.com et son BSN est 111222333.";

        $results = $this->analyzer->analyze($text, 'nl');

        $this->assertGreaterThanOrEqual(2, count($results));

        $operators = [
            'EMAIL_ADDRESS' => OperatorConfig::replace('[EMAIL]'),
            'NL_BSN' => OperatorConfig::mask('*', 6),
        ];

        $anonymized = $this->anonymizer->anonymize($text, $results, $operators);

        // Check UTF-8 characters are preserved
        $this->assertStringContainsString('François', $anonymized->getText());
        $this->assertStringContainsString('[EMAIL]', $anonymized->getText());
        $this->assertStringContainsString('******333', $anonymized->getText());
    }

    /**
     * Test encryption is reversible through full pipeline.
     */
    public function testReversibleEncryption(): void
    {
        $text = "BSN: 111222333";
        $key = 'test-key-2024';

        // Detect
        $results = $this->analyzer->analyze($text, 'nl');
        $this->assertCount(1, $results);

        // Encrypt
        $operators = ['NL_BSN' => OperatorConfig::encrypt($key)];
        $encrypted = $this->anonymizer->anonymize($text, $results, $operators);

        $encryptedText = $encrypted->getText();
        $this->assertStringNotContainsString('111222333', $encryptedText);

        // Extract encrypted BSN
        $encryptedBSN = $encrypted->getItems()[0]->text;

        // Manually decrypt to verify
        $decryptOp = new DecryptOperator();
        $decrypted = $decryptOp->operate($encryptedBSN, ['key' => $key]);

        $this->assertEquals('111222333', $decrypted);
    }

    /**
     * Test hash consistency across multiple runs.
     */
    public function testHashConsistency(): void
    {
        $text = "BSN: 111222333";

        $results = $this->analyzer->analyze($text, 'nl');

        $operators = ['NL_BSN' => OperatorConfig::hash('sha256')];

        // First run
        $anonymized1 = $this->anonymizer->anonymize($text, $results, $operators);
        $hash1 = $anonymized1->getItems()[0]->text;

        // Second run
        $anonymized2 = $this->anonymizer->anonymize($text, $results, $operators);
        $hash2 = $anonymized2->getItems()[0]->text;

        // Hashes should be identical
        $this->assertEquals($hash1, $hash2);

        // Should be valid SHA-256 (64 hex characters)
        $this->assertEquals(64, strlen($hash1));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash1);
    }

    /**
     * Test default operator is applied when entity-specific operator not provided.
     */
    public function testDefaultOperator(): void
    {
        $text = "BSN: 111222333, Email: test@example.com";

        $results = $this->analyzer->analyze($text, 'nl');

        // Only provide default operator
        $operators = [
            'DEFAULT' => OperatorConfig::replace('[REDACTED]'),
        ];

        $anonymized = $this->anonymizer->anonymize($text, $results, $operators);

        // All entities should be replaced with [REDACTED]
        $this->assertStringNotContainsString('111222333', $anonymized->getText());
        $this->assertStringNotContainsString('test@example.com', $anonymized->getText());
        $this->assertStringContainsString('[REDACTED]', $anonymized->getText());
    }

    /**
     * Test empty text handling.
     */
    public function testEmptyText(): void
    {
        $text = "";

        $results = $this->analyzer->analyze($text, 'nl');
        $this->assertEmpty($results);

        $anonymized = $this->anonymizer->anonymize($text, $results, []);
        $this->assertEquals('', $anonymized->getText());
    }

    /**
     * Test text with no PII.
     */
    public function testTextWithNoPII(): void
    {
        $text = "This is a normal sentence with no personal information.";

        $results = $this->analyzer->analyze($text, 'nl');
        $this->assertEmpty($results);

        $anonymized = $this->anonymizer->anonymize($text, $results, []);
        $this->assertEquals($text, $anonymized->getText());
    }

    /**
     * Test adjacent entities are merged correctly.
     */
    public function testAdjacentEntitiesMerged(): void
    {
        // Two phone numbers separated only by space (same entity type)
        $text = "Phones: +31612345678 +31698765432";

        $registry = new RecognizerRegistry();
        $registry->addRecognizer(new \Weichie\Blur\Analyzer\Recognizers\Generic\PhoneRecognizer(['NL']));

        $analyzer = new AnalyzerEngine($registry);
        $results = $analyzer->analyze($text, 'nl');

        // Should detect at least 1 phone number (libphonenumber may merge adjacent numbers)
        $this->assertGreaterThanOrEqual(1, count($results));

        $operators = ['PHONE_NUMBER' => OperatorConfig::replace('[PHONE]')];
        $anonymized = $this->anonymizer->anonymize($text, $results, $operators);

        // Check anonymization worked
        $this->assertStringContainsString('[PHONE]', $anonymized->getText());
    }

    /**
     * Test operator result metadata.
     */
    public function testOperatorResultMetadata(): void
    {
        $text = "BSN: 111222333";

        $results = $this->analyzer->analyze($text, 'nl');

        $operators = ['NL_BSN' => OperatorConfig::replace('[BSN]')];
        $anonymized = $this->anonymizer->anonymize($text, $results, $operators);

        $items = $anonymized->getItems();

        $this->assertCount(1, $items);
        $this->assertEquals('NL_BSN', $items[0]->entityType);
        $this->assertEquals('[BSN]', $items[0]->text);
        $this->assertEquals('replace', $items[0]->operator);
    }
}
