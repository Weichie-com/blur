<?php

declare(strict_types=1);

namespace Weichie\Blur\Tests\Unit\Recognizers\Generic;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Weichie\Blur\Analyzer\AnalyzerEngine;
use Weichie\Blur\Analyzer\RecognizerRegistry;
use Weichie\Blur\Analyzer\Recognizers\Generic\PhoneRecognizer;
use Weichie\Blur\Anonymizer\AnonymizerEngine;
use Weichie\Blur\Anonymizer\Models\OperatorConfig;
use Weichie\Blur\Anonymizer\Operators\MaskOperator;

/**
 * Regression tests for character offsets on multibyte input.
 * libphonenumber returns character offsets; treating them as byte offsets
 * shifted the span left and left (part of) the number unmasked.
 */
class PhoneRecognizerTest extends TestCase
{
    private const NUMBER = '0470 12 34 56';

    public static function multibyteTexts(): array
    {
        return [
            'ascii' => ['mijn gsm is 0470 12 34 56'],
            'emoji before' => ['Hallo 🙂 mijn gsm is 0470 12 34 56'],
            'accents before' => ['Café één: bel 0470 12 34 56'],
            '30 emoji before' => [str_repeat('🙂', 30) . ' 0470 12 34 56'],
            'mixed dutch text' => ['Geachte, ik heb een vraag over de réünie van België en de ruïne in Zeeë. Mijn gsm: 0470 12 34 56, groetjes! 😊'],
            'multibyte after too' => ['Bel 0470 12 34 56 voor het café ’s avonds ✨'],
        ];
    }

    #[DataProvider('multibyteTexts')]
    public function testSpanCoversExactlyTheNumber(string $text): void
    {
        $results = (new PhoneRecognizer(['BE']))->analyze($text);

        $this->assertCount(1, $results);
        $span = mb_substr($text, $results[0]->start, $results[0]->end - $results[0]->start, 'UTF-8');
        $this->assertSame(self::NUMBER, $span);
    }

    #[DataProvider('multibyteTexts')]
    public function testMaskLeavesNoReadableNumber(string $text): void
    {
        $registry = new RecognizerRegistry();
        $registry->addRecognizer(new PhoneRecognizer(['BE']));
        $analyzer = new AnalyzerEngine($registry);
        $anonymizer = new AnonymizerEngine();
        $anonymizer->addOperator(new MaskOperator());

        $results = $analyzer->analyze($text, 'nl');
        $masked = $anonymizer->anonymize($text, $results, [
            'PHONE_NUMBER' => OperatorConfig::mask('*', 100, true),
        ])->getText();

        $this->assertStringNotContainsString('0470', $masked);
        $this->assertStringNotContainsString('34 56', $masked);
        // Text around the number must survive untouched.
        $this->assertSame(
            str_replace(self::NUMBER, str_repeat('*', mb_strlen(self::NUMBER)), $text),
            $masked
        );
    }

    public function testRepeatedNumberGetsBothSpans(): void
    {
        $text = 'Één: 0470 12 34 56 — of twéé: 0470 12 34 56';
        $results = (new PhoneRecognizer(['BE']))->analyze($text);

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertSame(self::NUMBER, mb_substr($text, $result->start, $result->end - $result->start, 'UTF-8'));
        }
        $this->assertNotSame($results[0]->start, $results[1]->start);
    }

    public function testInternationalFormatAfterEmoji(): void
    {
        $text = '📞📞 +32 470 12 34 56';
        $results = (new PhoneRecognizer(['BE']))->analyze($text);

        $this->assertCount(1, $results);
        $this->assertSame('+32 470 12 34 56', mb_substr($text, $results[0]->start, $results[0]->end - $results[0]->start, 'UTF-8'));
    }
}
