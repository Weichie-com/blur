# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-11-21

### Added
- Initial release of Blur
- Pattern-based PII detection engine (AnalyzerEngine)
- Full anonymization engine with 6 operators (AnonymizerEngine)
- BeNeLux-specific recognizers:
  - Dutch BSN (Burgerservicenummer) with 11-proof validation
  - Belgian National Number with mod-97 validation
  - Luxembourg National ID with date validation
  - IBAN codes (BE/NL/LU) with mod-97 checksum
  - Phone numbers (BE/NL/LU) using libphonenumber
- Generic recognizers:
  - Email addresses with RFC validation
  - Credit cards with Luhn checksum
  - IP addresses (IPv4/IPv6)
  - URLs with TLD validation
- Anonymization operators:
  - Replace - custom value substitution
  - Redact - complete removal
  - Mask - partial/full character masking
  - Hash - SHA-256/SHA-512 hashing
  - Encrypt - AES-256-CBC encryption
  - Decrypt - AES decryption for deanonymization
- Context enhancement for confidence boosting
- Allow-list support for whitelisting
- Score threshold filtering
- Entity type filtering
- Conflict resolution for overlapping entities
- Full UTF-8 multibyte string support
- Comprehensive test suite (113 tests, 354 assertions)
- PHPUnit integration
- Complete documentation and examples

### Security
- ReDoS prevention in regex patterns
- SQL injection pattern handling
- XSS pattern handling
- Input validation and sanitization
- Secure encryption with random IV

## [Unreleased]

### Planned
- Additional country-specific recognizers (DE, FR, ES)
- Custom recognizer builder API
- Batch processing support
- Laravel integration package
- Symfony bundle
- Performance benchmarks

---

[1.0.0]: https://github.com/weichie-com/blur/releases/tag/v1.0.0
