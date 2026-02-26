# Blur

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-113%20passed-brightgreen.svg)](phpunit.xml)
[![Coverage](https://img.shields.io/badge/coverage-354%20assertions-blue.svg)](tests/)

A data protection and de-identification SDK for **BeNeLux** (Belgium, Netherlands, Luxembourg) and **US** identifiers, inspired by [Microsoft Presidio](https://github.com/microsoft/presidio).

---

## Features

- **Pattern-based PII Detection**: Fast and accurate entity recognition using regex patterns
- **Full Validation**: Checksum validation (Luhn, mod-97, 11-proof) for high accuracy
- **BeNeLux-Specific Recognizers**:
  - 🇳🇱 Dutch BSN (Burgerservicenummer) with 11-proof validation
  - 🇧🇪 Belgian National Number with mod-97 validation
  - 🇱🇺 Luxembourg National ID
  - BeNeLux IBAN codes with mod-97 checksum
  - Phone numbers for BE/NL/LU (using libphonenumber)
- **US-Specific Recognizers**:
  - 🇺🇸 Social Security Number (SSN) with area/group/serial validation
  - 🇺🇸 Individual Taxpayer ID (ITIN)
  - 🇺🇸 Passport Number (traditional + next-gen)
  - 🇺🇸 Driver License (multi-state formats)
  - 🇺🇸 Bank Account Number
  - 🇺🇸 ABA Routing Number with checksum validation
- **Generic Recognizers**: Email, Credit Card (Luhn), IP Address, URL
- **Multiple Anonymization Strategies**:
  - Replace with custom values
  - Redact (remove completely)
  - Mask (partial or full)
  - Hash (SHA-256/SHA-512)
  - Encrypt/Decrypt (AES-256-CBC)
- **Context Enhancement**: Boost detection confidence with contextual keywords
- **UTF-8 Support**: Full multibyte string handling
- **Type-Safe**: Built with PHP 8.1+ strict types

## Installation

Install via Composer:

```bash
composer require weichie-com/blur
```

Or add to your `composer.json`:

```json
{
    "require": {
        "weichie-com/blur": "^1.0"
    }
}
```

### Requirements

- **PHP 8.1+** (for strict types and named parameters)
- **ext-mbstring**: Multibyte string support (UTF-8)
- **ext-openssl**: AES encryption support
- **giggsey/libphonenumber-for-php**: Phone number validation (auto-installed)

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use Weichie\Blur\Analyzer\AnalyzerEngine;
use Weichie\Blur\Analyzer\RecognizerRegistry;
use Weichie\Blur\Analyzer\Recognizers\BeNeLux\BsnRecognizer;
use Weichie\Blur\Anonymizer\AnonymizerEngine;
use Weichie\Blur\Anonymizer\Models\OperatorConfig;
use Weichie\Blur\Anonymizer\Operators\MaskOperator;

// 1. Setup Analyzer
$registry = new RecognizerRegistry();
$registry->addRecognizer(new BsnRecognizer());

$analyzer = new AnalyzerEngine($registry);

// 2. Analyze text
$text = "Het BSN nummer is 111222333 voor deze klant.";
$results = $analyzer->analyze($text, language: 'nl');

// 3. Setup Anonymizer
$anonymizer = new AnonymizerEngine();
$anonymizer->addOperator(new MaskOperator());

// 4. Anonymize
$operators = [
    'NL_BSN' => OperatorConfig::mask('*', 6)
];

$anonymized = $anonymizer->anonymize($text, $results, $operators);
echo $anonymized->getText();
// Output: "Het BSN nummer is ******333 voor deze klant."
```

## Usage Examples

### 1. Detecting BeNeLux National IDs

```php
use Weichie\Blur\Analyzer\Recognizers\BeNeLux\BsnRecognizer;
use Weichie\Blur\Analyzer\Recognizers\BeNeLux\BelgianNationalNumberRecognizer;
use Weichie\Blur\Analyzer\Recognizers\BeNeLux\LuxembourgNationalIdRecognizer;

$registry = new RecognizerRegistry();
$registry->addRecognizer(new BsnRecognizer());                      // Dutch BSN
$registry->addRecognizer(new BelgianNationalNumberRecognizer());    // Belgian National Number
$registry->addRecognizer(new LuxembourgNationalIdRecognizer());     // Luxembourg National ID

$analyzer = new AnalyzerEngine($registry);

$text = "BSN: 111222333, BE National: 85.07.30-033.61, LU ID: 1990030112345";
$results = $analyzer->analyze($text, language: 'nl');

foreach ($results as $result) {
    echo "{$result->entityType}: score {$result->score}\n";
}
```

### 2. Detecting IBAN Codes

```php
use Weichie\Blur\Analyzer\Recognizers\Generic\IbanRecognizer;

$registry = new RecognizerRegistry();
$registry->addRecognizer(new IbanRecognizer());

$analyzer = new AnalyzerEngine($registry);

$text = "IBAN: NL91ABNA0417164300 (Netherlands), BE68539007547034 (Belgium)";
$results = $analyzer->analyze($text);
```

### 3. Multiple Anonymization Strategies

```php
// Strategy 1: Replace with labels
$operators = [
    'NL_BSN' => OperatorConfig::replace('[BSN-REDACTED]'),
    'EMAIL_ADDRESS' => OperatorConfig::replace('[EMAIL]'),
];

// Strategy 2: Partial masking
$operators = [
    'NL_BSN' => OperatorConfig::mask('*', 6, false),          // Mask first 6 chars
    'CREDIT_CARD' => OperatorConfig::mask('*', 12, false),    // Mask first 12 chars
];

// Strategy 3: Complete redaction
$operators = [
    'DEFAULT' => OperatorConfig::redact(),  // Remove all detected entities
];

// Strategy 4: Hashing for consistency
$operators = [
    'NL_BSN' => OperatorConfig::hash('sha256'),
    'IBAN_CODE' => OperatorConfig::hash('sha256'),
];

// Strategy 5: Encryption (reversible)
$key = 'your-secret-key';
$operators = [
    'NL_BSN' => OperatorConfig::encrypt($key),
    'BE_NATIONAL_NUMBER' => OperatorConfig::encrypt($key),
];
```

### 4. Context Enhancement

Boost detection confidence when context keywords appear near entities:

```php
$text = "Het BSN nummer is 111222333 voor deze klant.";

$results = $analyzer->analyze(
    text: $text,
    language: 'nl',
    context: ['bsn', 'nummer', 'klant'],  // Boost score when these words are nearby
    scoreThreshold: 0.3
);

// The BSN will have a higher confidence score due to context words
```

### 5. Entity Filtering

Detect only specific entity types:

```php
$results = $analyzer->analyze(
    text: $text,
    language: 'nl',
    entities: ['NL_BSN', 'EMAIL_ADDRESS']  // Only detect these types
);
```

### 6. Allow List

Whitelist specific values to ignore:

```php
$results = $analyzer->analyze(
    text: $text,
    language: 'nl',
    allowList: ['test@example.com', '111222333']  // Ignore these values
);
```

## Supported Recognizers

### BeNeLux-Specific

| Entity Type | Description | Validation | Country |
|-------------|-------------|------------|---------|
| `NL_BSN` | Dutch Burgerservicenummer | 11-proof checksum | 🇳🇱 NL |
| `BE_NATIONAL_NUMBER` | Belgian National Number | mod-97 checksum | 🇧🇪 BE |
| `LU_NATIONAL_ID` | Luxembourg National ID | Date validation | 🇱🇺 LU |
| `IBAN_CODE` | IBAN (BE/NL/LU) | mod-97 checksum | 🇧🇪🇳🇱🇱🇺 |
| `PHONE_NUMBER` | Phone numbers | libphonenumber | 🇧🇪🇳🇱🇱🇺 |

### US-Specific

| Entity Type | Description | Validation | Country |
|-------------|-------------|------------|---------|
| `US_SSN` | Social Security Number | Area/group/serial rules | 🇺🇸 US |
| `US_ITIN` | Individual Taxpayer ID | Format + digit ranges | 🇺🇸 US |
| `US_PASSPORT` | Passport Number | Pattern (context-boosted) | 🇺🇸 US |
| `US_DRIVER_LICENSE` | Driver License | Multi-state patterns | 🇺🇸 US |
| `US_BANK_NUMBER` | Bank Account Number | Pattern (context-boosted) | 🇺🇸 US |
| `US_ABA_ROUTING` | ABA Routing Number | Weighted checksum (mod 10) | 🇺🇸 US |

### Generic

| Entity Type | Description | Validation |
|-------------|-------------|------------|
| `EMAIL_ADDRESS` | Email addresses | RFC validation |
| `CREDIT_CARD` | Credit card numbers | Luhn checksum |
| `IP_ADDRESS` | IPv4/IPv6 addresses | IP validation |
| `URL` | URLs | URL validation |

## Supported Operators

| Operator | Description | Parameters |
|----------|-------------|------------|
| `replace` | Replace with custom value | `new_value` |
| `redact` | Remove completely | None |
| `mask` | Partial/full masking | `masking_char`, `chars_to_mask`, `from_end` |
| `hash` | SHA-256/SHA-512 hashing | `algorithm` (default: sha256) |
| `encrypt` | AES-256-CBC encryption | `key` |
| `decrypt` | AES-256-CBC decryption | `key` |

## Validation Algorithms

### Luhn Checksum (Credit Cards)

Used to validate credit card numbers. Prevents false positives from random digit sequences.

### Mod-97 Checksum (IBAN, Belgian National Number)

ISO 7064 mod-97 algorithm for IBAN codes and Belgian National Numbers.

### 11-Proof Checksum (Dutch BSN)

Dutch "elfproef" (11-check) algorithm for validating BSN numbers.

### ABA Routing Checksum (US ABA Routing)

Weighted sum mod-10 algorithm (weights: 3, 7, 1) for validating US ABA routing numbers.

## Architecture

```
Weichie\Blur\
├── Analyzer/
│   ├── AnalyzerEngine.php           # Main detection orchestrator
│   ├── EntityRecognizer.php         # Base recognizer interface
│   ├── PatternRecognizer.php        # Pattern-based recognition
│   ├── RecognizerRegistry.php       # Recognizer management
│   ├── Recognizers/
│   │   ├── Generic/                 # Universal recognizers
│   │   │   ├── EmailRecognizer.php
│   │   │   ├── CreditCardRecognizer.php
│   │   │   ├── IpRecognizer.php
│   │   │   ├── UrlRecognizer.php
│   │   │   ├── IbanRecognizer.php
│   │   │   └── PhoneRecognizer.php
│   │   ├── BeNeLux/                 # BeNeLux-specific
│   │   │   ├── BsnRecognizer.php
│   │   │   ├── BelgianNationalNumberRecognizer.php
│   │   │   └── LuxembourgNationalIdRecognizer.php
│   │   └── US/                      # US-specific
│   │       ├── UsSsnRecognizer.php
│   │       ├── UsItinRecognizer.php
│   │       ├── UsPassportRecognizer.php
│   │       ├── UsDriverLicenseRecognizer.php
│   │       ├── UsBankRecognizer.php
│   │       └── AbaRoutingRecognizer.php
│   └── Models/
│       ├── RecognizerResult.php
│       └── Pattern.php
└── Anonymizer/
    ├── AnonymizerEngine.php         # Main anonymization orchestrator
    ├── Operator.php                 # Base operator interface
    ├── TextReplaceBuilder.php       # Text manipulation
    ├── Operators/
    │   ├── ReplaceOperator.php
    │   ├── RedactOperator.php
    │   ├── MaskOperator.php
    │   ├── HashOperator.php
    │   ├── EncryptOperator.php
    │   └── DecryptOperator.php
    └── Models/
        ├── OperatorConfig.php
        ├── OperatorResult.php
        └── EngineResult.php
```

## Design Principles

1. **Simple but Complete**: Focus on core functionality without ML/NLP complexity
2. **Pattern-Based**: Fast regex matching with validation for accuracy
3. **Type-Safe**: PHP 8.1+ with strict types throughout
4. **UTF-8 First**: Proper multibyte string handling everywhere
5. **Extensible**: Easy to add custom recognizers and operators
6. **Immutable Results**: Thread-safe result objects

## Performance

- **Fast Pattern Matching**: No ML model overhead
- **Efficient Validation**: Checksum algorithms run in O(n) time
- **UTF-8 Optimized**: Uses `mb_*` functions for correct character offsets
- **Minimal Dependencies**: Only essential libraries (libphonenumber)

## Examples

See `examples/benelux_example.php` for a comprehensive demonstration including:
- All BeNeLux recognizers in action
- Multiple anonymization strategies
- Context enhancement
- Different operator configurations

Run it:
```bash
php examples/benelux_example.php
```

## Contributing

Contributions are welcome! To add support for additional countries:

1. Create a recognizer in `src/Analyzer/Recognizers/CountryName/`
2. Extend `PatternRecognizer` or implement `EntityRecognizer`
3. Add validation logic (checksum, format, etc.)
4. Include context words in local language(s)
5. Add tests and examples

## License

MIT License - See LICENSE file for details

## Credits

This project is inspired by [Microsoft Presidio](https://github.com/microsoft/presidio). Special thanks to the Presidio team for their excellent work on PII detection and de-identification.

## Roadmap

- [x] US-specific recognizers (SSN, ITIN, Passport, Driver License, Bank Account, ABA Routing)
- [ ] Additional country-specific recognizers (Germany, France, Spain, etc.)
- [ ] Custom recognizer builder API
- [ ] Batch processing support
- [ ] Performance benchmarks
- [ ] Integration with popular PHP frameworks (Laravel, Symfony)

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/weichie-com/blur).
