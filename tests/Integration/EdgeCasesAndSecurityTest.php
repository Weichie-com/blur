<?php

declare(strict_types=1);

namespace Weichie\Blur\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Weichie\Blur\Analyzer\AnalyzerEngine;
use Weichie\Blur\Analyzer\RecognizerRegistry;
use Weichie\Blur\Analyzer\Recognizers\Generic\EmailRecognizer;
use Weichie\Blur\Analyzer\Recognizers\BeNeLux\BsnRecognizer;
use Weichie\Blur\Anonymizer\AnonymizerEngine;
use Weichie\Blur\Anonymizer\Models\OperatorConfig;
use Weichie\Blur\Anonymizer\Operators\ReplaceOperator;
use Weichie\Blur\Anonymizer\Operators\MaskOperator;
use Weichie\Blur\Anonymizer\Operators\HashOperator;

class EdgeCasesAndSecurityTest extends TestCase
{
    private AnalyzerEngine $analyzer;
    private AnonymizerEngine $anonymizer;

    protected function setUp(): void
    {
        $registry = new RecognizerRegistry();
        $registry->addRecognizer(new EmailRecognizer());
        $registry->addRecognizer(new BsnRecognizer());

        $this->analyzer = new AnalyzerEngine($registry);

        $this->anonymizer = new AnonymizerEngine();
        $this->anonymizer->addOperator(new ReplaceOperator());
        $this->anonymizer->addOperator(new MaskOperator());
        $this->anonymizer->addOperator(new HashOperator());
    }

    // ============ UTF-8 Edge Cases ============

    /**
     * Test various UTF-8 characters don't break detection.
     */
    public function testUTF8EdgeCases(): void
    {
        $texts = [
            "Café owner email: test@example.com",
            "Naïve user BSN: 111222333",
            "Chinese text 中文 BSN: 111222333",
            "Emoji 😀 BSN: 111222333",
            "Arabic text العربية BSN: 111222333",
            "Cyrillic текст BSN: 111222333",
        ];

        foreach ($texts as $text) {
            $results = $this->analyzer->analyze($text, 'nl');

            $this->assertNotEmpty($results, "Should detect PII in: {$text}");

            // Anonymize and check UTF-8 preserved
            $operators = [
                'EMAIL_ADDRESS' => OperatorConfig::replace('[EMAIL]'),
                'NL_BSN' => OperatorConfig::replace('[BSN]'),
            ];
            $anonymized = $this->anonymizer->anonymize($text, $results, $operators);

            // Original UTF-8 characters should be preserved
            if (mb_strpos($text, 'Café') !== false) {
                $this->assertStringContainsString('Café', $anonymized->getText());
            }
            if (mb_strpos($text, '中文') !== false) {
                $this->assertStringContainsString('中文', $anonymized->getText());
            }
        }
    }

    /**
     * Test very long UTF-8 strings.
     */
    public function testVeryLongUTF8String(): void
    {
        $longText = str_repeat("café ", 1000) . "BSN: 111222333 " . str_repeat("naïve ", 1000);

        $results = $this->analyzer->analyze($longText, 'nl');

        $this->assertNotEmpty($results);
        $this->assertEquals('NL_BSN', $results[0]->entityType);
    }

    // ============ Overlapping and Conflict Detection ============

    /**
     * Test overlapping entities are resolved correctly.
     */
    public function testOverlappingEntitiesResolution(): void
    {
        // Create text where patterns might overlap
        $text = "test@111222333.com";  // Might match both email and BSN pattern

        $results = $this->analyzer->analyze($text, 'nl');

        // Should detect email (higher confidence/longer match)
        $this->assertNotEmpty($results);

        // Check no duplicates or conflicts
        $positions = [];
        foreach ($results as $result) {
            $key = "{$result->start}-{$result->end}";
            $this->assertArrayNotHasKey($key, $positions, "Duplicate position detected");
            $positions[$key] = true;
        }
    }

    /**
     * Test nested entities.
     */
    public function testNestedEntities(): void
    {
        $text = "Contact: test@example.com (email)";

        $results = $this->analyzer->analyze($text, 'nl');

        // Should detect email
        $this->assertNotEmpty($results);

        // No entity should contain another with lower score
        foreach ($results as $i => $result1) {
            foreach ($results as $j => $result2) {
                if ($i === $j) continue;

                if ($result1->contains($result2)) {
                    $this->assertGreaterThan($result2->score, $result1->score,
                        "Containing entity should have higher score");
                }
            }
        }
    }

    // ============ Input Validation and Security ============

    /**
     * Test extremely long input doesn't cause issues.
     */
    public function testVeryLongInput(): void
    {
        $veryLongText = str_repeat("This is normal text. ", 10000) . "BSN: 111222333";

        $results = $this->analyzer->analyze($veryLongText, 'nl');

        $this->assertNotEmpty($results);
        $this->assertEquals('NL_BSN', $results[0]->entityType);
    }

    /**
     * Test special regex characters in text don't break detection.
     */
    public function testSpecialRegexCharacters(): void
    {
        $texts = [
            "BSN: 111222333 (valid)",
            "BSN: 111222333 [test]",
            "BSN: 111222333 {data}",
            "BSN: 111222333 $100",
            "BSN: 111222333 ^top",
            "BSN: 111222333 *note",
            "BSN: 111222333 +add",
            "BSN: 111222333 ?query",
            "BSN: 111222333 |or",
            "BSN: 111222333 \\backslash",
        ];

        foreach ($texts as $text) {
            $results = $this->analyzer->analyze($text, 'nl');

            $this->assertNotEmpty($results, "Should detect BSN in: {$text}");
            $this->assertEquals('NL_BSN', $results[0]->entityType);
        }
    }

    /**
     * Test malicious regex patterns don't cause ReDoS.
     */
    public function testReDoSPrevention(): void
    {
        // Patterns that could cause catastrophic backtracking
        $maliciousTexts = [
            str_repeat("a", 1000) . "!",
            "(" . str_repeat("a", 500) . ")*b",
            str_repeat("test@", 100) . "example.com",
        ];

        foreach ($maliciousTexts as $text) {
            $startTime = microtime(true);

            $results = $this->analyzer->analyze($text, 'nl');

            $duration = microtime(true) - $startTime;

            // Should complete in reasonable time (< 1 second)
            $this->assertLessThan(1.0, $duration, "Analysis took too long: {$duration}s");
        }
    }

    /**
     * Test SQL injection-like patterns don't affect detection.
     */
    public function testSQLInjectionPatterns(): void
    {
        $texts = [
            "BSN: 111222333'; DROP TABLE users;--",
            "BSN: 111222333' OR '1'='1",
            "BSN: 111222333 UNION SELECT * FROM users",
        ];

        foreach ($texts as $text) {
            $results = $this->analyzer->analyze($text, 'nl');

            $this->assertNotEmpty($results);
            $this->assertEquals('NL_BSN', $results[0]->entityType);
        }
    }

    /**
     * Test XSS-like patterns don't affect detection.
     */
    public function testXSSPatterns(): void
    {
        $texts = [
            "BSN: 111222333<script>alert('xss')</script>",
            "BSN: 111222333<img src=x onerror=alert('xss')>",
            "BSN: 111222333<iframe src='evil.com'>",
        ];

        foreach ($texts as $text) {
            $results = $this->analyzer->analyze($text, 'nl');

            $this->assertNotEmpty($results);
            $this->assertEquals('NL_BSN', $results[0]->entityType);

            // Anonymize and check output is safe
            $operators = ['NL_BSN' => OperatorConfig::replace('[BSN]')];
            $anonymized = $this->anonymizer->anonymize($text, $results, $operators);

            // XSS patterns should still be in output (not filtered, just PII removed)
            // This is correct - we only handle PII, not XSS
            $this->assertStringContainsString('[BSN]', $anonymized->getText());
        }
    }

    // ============ Boundary Cases ============

    /**
     * Test entities at the very beginning of text.
     */
    public function testEntityAtStart(): void
    {
        $text = "111222333 is a BSN";

        $results = $this->analyzer->analyze($text, 'nl');

        $this->assertNotEmpty($results);
        $this->assertEquals(0, $results[0]->start);
    }

    /**
     * Test entities at the very end of text.
     */
    public function testEntityAtEnd(): void
    {
        $text = "The BSN is 111222333";

        $results = $this->analyzer->analyze($text, 'nl');

        $this->assertNotEmpty($results);
        $this->assertEquals(mb_strlen($text, 'UTF-8'), $results[0]->end);
    }

    /**
     * Test single entity as entire text.
     */
    public function testSingleEntityAsWholeText(): void
    {
        $text = "111222333";

        $results = $this->analyzer->analyze($text, 'nl');

        $this->assertNotEmpty($results);
        $this->assertEquals(0, $results[0]->start);
        $this->assertEquals(9, $results[0]->end);
    }

    /**
     * Test entities separated by single character.
     */
    public function testEntitiesSeparatedBySingleChar(): void
    {
        $text = "111222333,111222333";

        $results = $this->analyzer->analyze($text, 'nl');

        $this->assertCount(2, $results);
    }

    /**
     * Test adjacent entities of same type.
     */
    public function testAdjacentSameTypeEntities(): void
    {
        $text = "111222333 111222333";  // BSNs separated by space

        $results = $this->analyzer->analyze($text, 'nl');

        $this->assertCount(2, $results);
    }

    // ============ Hash Security ============

    /**
     * Test hash output doesn't reveal input.
     */
    public function testHashDoesNotRevealInput(): void
    {
        $text = "BSN: 111222333";

        $results = $this->analyzer->analyze($text, 'nl');

        $operators = ['NL_BSN' => OperatorConfig::hash('sha256')];
        $anonymized = $this->anonymizer->anonymize($text, $results, $operators);

        $hash = $anonymized->getItems()[0]->text;

        // Hash should not contain original value
        $this->assertStringNotContainsString('111222333', $hash);
        $this->assertStringNotContainsString('111', $hash);
        $this->assertStringNotContainsString('222', $hash);
        $this->assertStringNotContainsString('333', $hash);
    }

    /**
     * Test different inputs produce different hashes.
     */
    public function testDifferentInputsDifferentHashes(): void
    {
        $text1 = "BSN: 111222333";
        $text2 = "BSN: 123456782";

        $results1 = $this->analyzer->analyze($text1, 'nl');
        $results2 = $this->analyzer->analyze($text2, 'nl');

        $operators = ['NL_BSN' => OperatorConfig::hash('sha256')];

        $anonymized1 = $this->anonymizer->anonymize($text1, $results1, $operators);
        $anonymized2 = $this->anonymizer->anonymize($text2, $results2, $operators);

        $hash1 = $anonymized1->getItems()[0]->text;
        $hash2 = $anonymized2->getItems()[0]->text;

        $this->assertNotEquals($hash1, $hash2);
    }

    // ============ Performance ============

    /**
     * Test performance with many entities.
     */
    public function testPerformanceWithManyEntities(): void
    {
        // Create text with many BSNs
        $bsns = array_fill(0, 100, '111222333');
        $text = implode(' ', $bsns);

        $startTime = microtime(true);

        $results = $this->analyzer->analyze($text, 'nl');

        $duration = microtime(true) - $startTime;

        $this->assertCount(100, $results);
        $this->assertLessThan(1.0, $duration, "Analysis of 100 entities took too long");

        // Test anonymization performance
        $operators = ['NL_BSN' => OperatorConfig::replace('[BSN]')];

        $startTime = microtime(true);

        $anonymized = $this->anonymizer->anonymize($text, $results, $operators);

        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration, "Anonymization of 100 entities took too long");
    }

    /**
     * Test memory usage stays reasonable.
     */
    public function testMemoryUsage(): void
    {
        $startMemory = memory_get_usage();

        // Process large text
        $largeText = str_repeat("BSN: 111222333 ", 1000);
        $results = $this->analyzer->analyze($largeText, 'nl');

        $operators = ['NL_BSN' => OperatorConfig::replace('[BSN]')];
        $anonymized = $this->anonymizer->anonymize($largeText, $results, $operators);

        $endMemory = memory_get_usage();
        $memoryUsed = $endMemory - $startMemory;

        // Should use less than 10MB for this test
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed, "Memory usage too high");
    }
}
