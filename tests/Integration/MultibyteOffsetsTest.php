<?php

declare(strict_types=1);

namespace Weichie\Blur\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Weichie\Blur\Analyzer\EntityRecognizer;
use Weichie\Blur\Analyzer\Recognizers\BeNeLux\BelgianNationalNumberRecognizer;
use Weichie\Blur\Analyzer\Recognizers\BeNeLux\BsnRecognizer;
use Weichie\Blur\Analyzer\Recognizers\Generic\CreditCardRecognizer;
use Weichie\Blur\Analyzer\Recognizers\Generic\EmailRecognizer;
use Weichie\Blur\Analyzer\Recognizers\Generic\IbanRecognizer;
use Weichie\Blur\Analyzer\Recognizers\Generic\IpRecognizer;
use Weichie\Blur\Analyzer\Recognizers\Generic\PhoneRecognizer;
use Weichie\Blur\Analyzer\Recognizers\Generic\UrlRecognizer;

/**
 * Audit: every recognizer must return character offsets, also when
 * multibyte characters (accents, emoji) precede the entity.
 */
class MultibyteOffsetsTest extends TestCase
{
    private const PREFIXES = [
        'ascii' => 'Hello, see: ',
        'accents' => 'Café één réünie België: ',
        'emoji' => '🙂🙂🙂 ',
        '30 emoji' => '🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂🙂 ',
    ];

    public static function cases(): iterable
    {
        $samples = [
            'phone' => [fn () => new PhoneRecognizer(['BE']), '0470 12 34 56'],
            'email' => [fn () => new EmailRecognizer(), 'jan.peeters@example.be'],
            'iban' => [fn () => new IbanRecognizer(), 'NL91ABNA0417164300'],
            'credit card' => [fn () => new CreditCardRecognizer(), '4532015112830366'],
            'bsn' => [fn () => new BsnRecognizer(), '111222333'],
            'be national number' => [fn () => new BelgianNationalNumberRecognizer(), '85.07.30-033.28'],
            'url' => [fn () => new UrlRecognizer(), 'https://www.example.be/contact'],
            'ip' => [fn () => new IpRecognizer(), '192.168.1.42'],
        ];

        foreach ($samples as $name => [$factory, $value]) {
            foreach (self::PREFIXES as $prefixName => $prefix) {
                yield "$name / $prefixName" => [$factory, $value, $prefix . $value . ' – dank je wel ✨'];
            }
        }
    }

    #[DataProvider('cases')]
    public function testSpanMatchesEntityValue(\Closure $factory, string $value, string $text): void
    {
        /** @var EntityRecognizer $recognizer */
        $recognizer = $factory();
        $language = $recognizer->getSupportedLanguages()[0];

        $spans = array_map(
            fn ($r) => mb_substr($text, $r->start, $r->end - $r->start, 'UTF-8'),
            $recognizer->analyze($text, [], $language)
        );

        $this->assertContains($value, $spans, 'Detected spans: ' . json_encode($spans, JSON_UNESCAPED_UNICODE));
    }
}
