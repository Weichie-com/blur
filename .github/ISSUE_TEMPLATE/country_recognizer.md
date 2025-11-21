---
name: Country-Specific Recognizer Request
about: Request support for a new country's PII identifiers
title: '[COUNTRY] Add support for [Country Name]'
labels: enhancement, country-support
assignees: ''
---

## Country Information
- **Country**: (e.g., Germany)
- **Country Code**: (e.g., DE)
- **Language(s)**: (e.g., German, English)

## PII Identifiers to Support
List the personal identifiers used in this country:

### 1. [Identifier Name] (e.g., Tax ID)
- **Local Name**: (e.g., Steuer-Identifikationsnummer)
- **Format**: (e.g., 11 digits)
- **Example**: (e.g., 12345678901 - use fake/test data only!)
- **Validation**: (e.g., mod-11 checksum)
- **Commonality**: How commonly is this used?

### 2. [Another Identifier]
- ...

## Context Words
What words/phrases commonly appear near these identifiers in text?
- Language 1: (e.g., "Steuernummer", "Tax ID")
- Language 2: ...

## Validation Algorithm
If there's a checksum or validation algorithm, please describe it or link to documentation:
- Algorithm: (e.g., mod-11, Luhn, custom)
- Reference: (link to official documentation if available)

## References
- Official documentation: (government website, Wikipedia, etc.)
- Example usage: (where these identifiers are commonly used)
- Legal/privacy considerations: (GDPR, local privacy laws)

## Contribution
- [ ] I can provide test cases
- [ ] I can implement this recognizer
- [ ] I have access to official documentation
- [ ] I can validate the checksum algorithm

## Additional Information
Any other relevant information about these identifiers.
