<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Weichie\Blur\Analyzer\AnalyzerEngine;
use Weichie\Blur\Analyzer\RecognizerRegistry;
use Weichie\Blur\Analyzer\Recognizers\Generic\EmailRecognizer;
use Weichie\Blur\Analyzer\Recognizers\Generic\CreditCardRecognizer;
use Weichie\Blur\Analyzer\Recognizers\Generic\IpRecognizer;
use Weichie\Blur\Analyzer\Recognizers\Generic\UrlRecognizer;
use Weichie\Blur\Analyzer\Recognizers\Generic\IbanRecognizer;
use Weichie\Blur\Analyzer\Recognizers\Generic\PhoneRecognizer;
use Weichie\Blur\Analyzer\Recognizers\BeNeLux\BsnRecognizer;
use Weichie\Blur\Analyzer\Recognizers\BeNeLux\BelgianNationalNumberRecognizer;
use Weichie\Blur\Analyzer\Recognizers\BeNeLux\LuxembourgNationalIdRecognizer;
use Weichie\Blur\Anonymizer\AnonymizerEngine;
use Weichie\Blur\Anonymizer\Models\OperatorConfig;
use Weichie\Blur\Anonymizer\Operators\ReplaceOperator;
use Weichie\Blur\Anonymizer\Operators\RedactOperator;
use Weichie\Blur\Anonymizer\Operators\MaskOperator;
use Weichie\Blur\Anonymizer\Operators\HashOperator;
use Weichie\Blur\Anonymizer\Operators\EncryptOperator;

echo "=== Blur BeNeLux Example ===\n\n";

// 1. Setup Analyzer Engine
echo "1. Setting up Analyzer Engine...\n";

$registry = new RecognizerRegistry();

// Add generic recognizers
$registry->addRecognizer(new EmailRecognizer());
$registry->addRecognizer(new CreditCardRecognizer());
$registry->addRecognizer(new IpRecognizer());
$registry->addRecognizer(new UrlRecognizer());
$registry->addRecognizer(new IbanRecognizer());
$registry->addRecognizer(new PhoneRecognizer(['BE', 'NL', 'LU']));

// Add BeNeLux-specific recognizers
$registry->addRecognizer(new BsnRecognizer());
$registry->addRecognizer(new BelgianNationalNumberRecognizer());
$registry->addRecognizer(new LuxembourgNationalIdRecognizer());

$analyzer = new AnalyzerEngine($registry);

echo "Supported entities: " . implode(', ', $analyzer->getSupportedEntities()) . "\n\n";

// 2. Sample BeNeLux text with PII
$text = <<<'TEXT'
Customer Information (BeNeLux Region):

Netherlands:
Name: Jan de Vries
BSN: 111222333
Email: jan.devries@example.nl
Phone: +31 6 12345678
IBAN: NL91ABNA0417164300

Belgium:
Name: Marie Dubois
National Number: 85.07.30-033.28
Email: marie.dubois@example.be
Phone: +32 2 123 45 67
IBAN: BE68539007547034

Luxembourg:
Name: Jean Weber
National ID: 1990030112345
Email: jean.weber@example.lu
Phone: +352 621 123 456
IBAN: LU280019400644750000

Payment Information:
Credit Card: 4532-1234-5678-9010
IP Address: 192.168.1.100
Website: https://www.example.com
TEXT;

echo "2. Original Text:\n";
echo str_repeat('-', 80) . "\n";
echo $text . "\n";
echo str_repeat('-', 80) . "\n\n";

// 3. Analyze the text
echo "3. Analyzing text for PII...\n";

$results = $analyzer->analyze(
    text: $text,
    language: 'nl',
    scoreThreshold: 0.3
);

echo "Found " . count($results) . " PII entities:\n\n";

foreach ($results as $result) {
    $detectedText = mb_substr($text, $result->start, $result->end - $result->start, 'UTF-8');
    printf(
        "  - %s: \"%s\" (score: %.2f, pos: %d-%d)\n",
        $result->entityType,
        $detectedText,
        $result->score,
        $result->start,
        $result->end
    );
}
echo "\n";

// 4. Setup Anonymizer Engine
echo "4. Setting up Anonymizer Engine...\n";

$anonymizer = new AnonymizerEngine();
$anonymizer->addOperator(new ReplaceOperator());
$anonymizer->addOperator(new RedactOperator());
$anonymizer->addOperator(new MaskOperator());
$anonymizer->addOperator(new HashOperator());
$anonymizer->addOperator(new EncryptOperator());

echo "\n";

// 5. Anonymize with different strategies
echo "5. Anonymizing text...\n\n";

// Strategy 1: Replace with entity type labels
echo "Strategy 1: Replace with entity type labels\n";
echo str_repeat('-', 80) . "\n";

$operators1 = [
    'NL_BSN' => OperatorConfig::replace('[BSN-REDACTED]'),
    'BE_NATIONAL_NUMBER' => OperatorConfig::replace('[BE-NATIONAL-NUMBER]'),
    'LU_NATIONAL_ID' => OperatorConfig::replace('[LU-NATIONAL-ID]'),
    'EMAIL_ADDRESS' => OperatorConfig::replace('[EMAIL]'),
    'PHONE_NUMBER' => OperatorConfig::replace('[PHONE]'),
    'IBAN_CODE' => OperatorConfig::replace('[IBAN]'),
    'CREDIT_CARD' => OperatorConfig::replace('[CREDIT-CARD]'),
    'IP_ADDRESS' => OperatorConfig::replace('[IP]'),
    'URL' => OperatorConfig::replace('[URL]'),
];

$result1 = $anonymizer->anonymize($text, $results, $operators1);
echo $result1->getText() . "\n";
echo str_repeat('-', 80) . "\n\n";

// Strategy 2: Mask sensitive data
echo "Strategy 2: Mask sensitive data (partial masking)\n";
echo str_repeat('-', 80) . "\n";

$operators2 = [
    'NL_BSN' => OperatorConfig::mask('*', 6, false),
    'BE_NATIONAL_NUMBER' => OperatorConfig::mask('*', 8, false),
    'LU_NATIONAL_ID' => OperatorConfig::mask('*', 9, false),
    'EMAIL_ADDRESS' => OperatorConfig::mask('*', 3, false),
    'PHONE_NUMBER' => OperatorConfig::mask('*', 6, true),
    'IBAN_CODE' => OperatorConfig::mask('*', 12, false),
    'CREDIT_CARD' => OperatorConfig::mask('*', 12, false),
    'DEFAULT' => OperatorConfig::mask('*', -1, false),
];

$result2 = $anonymizer->anonymize($text, $results, $operators2);
echo $result2->getText() . "\n";
echo str_repeat('-', 80) . "\n\n";

// Strategy 3: Hash sensitive IDs, redact other PII
echo "Strategy 3: Hash IDs, redact other PII\n";
echo str_repeat('-', 80) . "\n";

$operators3 = [
    'NL_BSN' => OperatorConfig::hash('sha256'),
    'BE_NATIONAL_NUMBER' => OperatorConfig::hash('sha256'),
    'LU_NATIONAL_ID' => OperatorConfig::hash('sha256'),
    'IBAN_CODE' => OperatorConfig::hash('sha256'),
    'DEFAULT' => OperatorConfig::redact(),
];

$result3 = $anonymizer->anonymize($text, $results, $operators3);
echo $result3->getText() . "\n";
echo str_repeat('-', 80) . "\n\n";

// Strategy 4: Encrypt for reversible anonymization
echo "Strategy 4: Encrypt sensitive data (reversible)\n";
echo str_repeat('-', 80) . "\n";

$encryptionKey = 'my-secret-encryption-key-2024';

$operators4 = [
    'NL_BSN' => OperatorConfig::encrypt($encryptionKey),
    'BE_NATIONAL_NUMBER' => OperatorConfig::encrypt($encryptionKey),
    'LU_NATIONAL_ID' => OperatorConfig::encrypt($encryptionKey),
    'IBAN_CODE' => OperatorConfig::encrypt($encryptionKey),
    'DEFAULT' => OperatorConfig::replace('[REDACTED]'),
];

$result4 = $anonymizer->anonymize($text, $results, $operators4);
echo $result4->getText() . "\n";
echo str_repeat('-', 80) . "\n\n";

// 6. Demonstrate context enhancement
echo "6. Context Enhancement Example:\n";
echo str_repeat('-', 80) . "\n";

$textWithContext = "Het BSN nummer is 111222333 voor deze klant.";

$resultsWithContext = $analyzer->analyze(
    text: $textWithContext,
    language: 'nl',
    context: ['bsn', 'nummer', 'klant'], // Context words boost confidence
    scoreThreshold: 0.3
);

echo "Text: $textWithContext\n";
echo "Detected entities with context boost:\n";
foreach ($resultsWithContext as $result) {
    $detectedText = mb_substr($textWithContext, $result->start, $result->end - $result->start, 'UTF-8');
    printf(
        "  - %s: \"%s\" (score: %.2f)\n",
        $result->entityType,
        $detectedText,
        $result->score
    );
}
echo str_repeat('-', 80) . "\n\n";

// 7. Summary
echo "7. Summary:\n";
echo "  - Total PII entities detected: " . count($results) . "\n";
echo "  - Anonymization strategies: 4 different approaches demonstrated\n";
echo "  - BeNeLux coverage: Netherlands (BSN), Belgium (National Number), Luxembourg (National ID)\n";
echo "  - Generic recognizers: Email, Phone, IBAN, Credit Card, IP, URL\n";
echo "\nBlur is ready for production use!\n";
