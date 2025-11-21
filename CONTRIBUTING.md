# Contributing to Blur

First off, thank you for considering contributing to Blur! It's people like you that make this project great.

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to the project maintainers.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

* **Use a clear and descriptive title**
* **Describe the exact steps to reproduce the problem**
* **Provide specific examples** - Include code samples
* **Describe the behavior you observed** and what behavior you expected to see
* **Include PHP version** and dependency versions

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

* **Use a clear and descriptive title**
* **Provide a detailed description** of the suggested enhancement
* **Provide specific examples** to demonstrate the steps
* **Describe the current behavior** and explain which behavior you expected to see instead
* **Explain why this enhancement would be useful**

### Adding New Country-Specific Recognizers

We welcome contributions of recognizers for additional countries! Here's how:

1. Create a new file in `src/Analyzer/Recognizers/CountryName/`
2. Extend `PatternRecognizer` or implement `EntityRecognizer`
3. Add validation logic (checksum algorithms, format validation)
4. Include context words in the local language(s)
5. Add comprehensive unit tests
6. Update documentation

Example structure:
```php
<?php

namespace Weichie\Blur\Analyzer\Recognizers\CountryName;

use Weichie\Blur\Analyzer\Models\Pattern;
use Weichie\Blur\Analyzer\PatternRecognizer;

class CountryIdRecognizer extends PatternRecognizer
{
    public function __construct()
    {
        $patterns = [
            new Pattern(
                name: 'Country ID',
                regex: '/\b[0-9]{10}\b/',
                score: 0.4
            ),
        ];

        parent::__construct(
            patterns: $patterns,
            supportedEntities: ['COUNTRY_ID'],
            supportedLanguages: ['en', 'local-code'],
            context: ['id', 'number', 'local-terms']
        );
    }

    protected function validateResult(string $text): ?bool
    {
        // Add checksum validation here
        return $this->checksumValid($text);
    }
}
```

### Pull Requests

1. **Fork the repository** and create your branch from `main`
2. **Follow PSR-12 coding standards**
3. **Add tests** for any new code
4. **Ensure all tests pass**: `vendor/bin/phpunit`
5. **Update documentation** if needed
6. **Write clear commit messages**

#### Pull Request Process

1. Update the README.md with details of changes if applicable
2. Update the CHANGELOG.md following [Keep a Changelog](https://keepachangelog.com/) format
3. Increase version numbers in composer.json following [Semantic Versioning](https://semver.org/)
4. Your PR will be merged once it has been reviewed and approved

## Development Setup

```bash
# Clone your fork
git clone https://github.com/weichie-com/blur.git
cd blur

# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Run tests with detailed output
vendor/bin/phpunit --testdox

# Run specific test
vendor/bin/phpunit tests/Unit/Recognizers/BeNeLux/BsnRecognizerTest.php
```

## Coding Standards

### PHP Standards

- **Follow PSR-12** coding style
- **Use strict types**: `declare(strict_types=1);` in all files
- **Type everything**: Parameters, return types, and properties
- **PHP 8.1+**: Use named parameters and constructor property promotion
- **PHPDoc**: Document complex logic and public APIs

### Testing Standards

- **Write tests first** (TDD when possible)
- **Test coverage**: Aim for high coverage of new code
- **Test structure**:
  - Unit tests in `tests/Unit/`
  - Integration tests in `tests/Integration/`
- **Test naming**: Use descriptive method names (`testValidBsnNumbers`)
- **Test data**: Use realistic test data with proper checksums

### Git Commit Messages

- Use the present tense ("Add feature" not "Added feature")
- Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
- Limit the first line to 72 characters or less
- Reference issues and pull requests liberally after the first line

Example:
```
Add German tax ID recognizer

- Implement pattern matching for German Steuer-ID
- Add mod-11 checksum validation
- Include comprehensive test suite
- Update documentation

Fixes #123
```

## Project Structure

```
blur/
├── src/
│   ├── Analyzer/           # PII detection engine
│   │   ├── Recognizers/
│   │   │   ├── Generic/    # Universal recognizers
│   │   │   └── BeNeLux/    # Country-specific
│   │   └── Models/
│   └── Anonymizer/         # Anonymization engine
│       ├── Operators/
│       └── Models/
├── tests/
│   ├── Unit/
│   └── Integration/
├── examples/
└── docs/
```

## Adding a New Operator

To add a new anonymization operator:

1. Create a new class in `src/Anonymizer/Operators/`
2. Implement the `Operator` interface
3. Add validation in `validateParams()`
4. Add tests in `tests/Unit/Operators/`
5. Update the README with usage examples

## Documentation

- Update README.md for user-facing changes
- Add PHPDoc comments for public APIs
- Include code examples for new features
- Update CHANGELOG.md for all changes

## Questions?

Feel free to open an issue with the question label or reach out to the maintainers.

## Recognition

Contributors will be recognized in:
- The project README
- Release notes
- The contributors page on GitHub

Thank you for contributing! 🎉
