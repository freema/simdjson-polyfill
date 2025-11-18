# Contributing to SimdJson Polyfill

Thank you for considering contributing to SimdJson Polyfill! This document provides guidelines for contributing to the project.

## 🚀 Getting Started

### Prerequisites

- Docker and Docker Compose
- [Task](https://taskfile.dev/) (Go Task runner)
- Git
- Basic knowledge of PHP 8.0+

**Install Task:**
```bash
# macOS
brew install go-task

# Linux
sh -c "$(curl --location https://taskfile.dev/install.sh)" -- -d -b ~/.local/bin

# Or see https://taskfile.dev/installation/
```

### Setting Up Development Environment

```bash
# Clone the repository
git clone https://github.com/freema/simdjson-polyfill.git
cd simdjson-polyfill

# Build Docker containers
task build

# Install dependencies
task install

# Generate benchmark fixtures
task fixtures

# Run tests
task test
```

## 🧪 Testing

### Running Tests

```bash
# Run all tests
task test

# Run tests with coverage
task test-coverage

# Test on specific PHP version
task test-83

# Test on all PHP versions
task test-all
```

### Writing Tests

- All new features must include unit tests
- Aim for 100% code coverage for new code
- Place tests in `tests/Unit/` or `tests/Integration/`
- Follow PHPUnit best practices

Example test structure:

```php
<?php

declare(strict_types=1);

namespace SimdJsonPolyfill\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MyFeatureTest extends TestCase
{
    public function testSomething(): void
    {
        // Arrange
        $input = 'test';

        // Act
        $result = doSomething($input);

        // Assert
        $this->assertSame('expected', $result);
    }
}
```

## 📝 Code Style

### PSR-12 Compliance

This project follows [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards.

### Type Declarations

- Always use strict types: `declare(strict_types=1);`
- Use type hints for all parameters and return types
- Use property type hints (PHP 7.4+)

### Documentation

- Add PHPDoc blocks for all public methods
- Document complex logic with inline comments
- Keep comments in English

Example:

```php
/**
 * Decode JSON string using the active strategy.
 *
 * @param string $json JSON string to decode
 * @param bool|null $associative Return associative array instead of object
 * @param int $depth Maximum nesting depth
 * @param int $flags Bitmask of JSON decode options
 * @return mixed Decoded value
 * @throws \JsonException On decode error when JSON_THROW_ON_ERROR is set
 */
public function decode(
    string $json,
    ?bool $associative = null,
    int $depth = 512,
    int $flags = 0
): mixed {
    // Implementation
}
```

## 🔀 Pull Request Process

### Before Submitting

1. **Run tests:** `task test-all`
2. **Run static analysis:** `task stan`
3. **Ensure code coverage:** `task test-coverage`
4. **Update documentation:** Update README.md if needed
5. **Write changelog entry:** Document your changes

### PR Guidelines

1. **One feature per PR:** Keep PRs focused on a single feature or fix
2. **Clear title:** Use descriptive PR titles (e.g., "Add support for JSON5 parsing")
3. **Description:** Explain what, why, and how
4. **Link issues:** Reference related issues (e.g., "Fixes #123")
5. **Tests:** Include tests for new functionality
6. **Documentation:** Update docs as needed

### PR Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Unit tests pass
- [ ] Integration tests pass
- [ ] Manual testing completed

## Checklist
- [ ] Code follows PSR-12 standards
- [ ] PHPStan passes at max level
- [ ] Documentation updated
- [ ] Changelog updated
```

## 🐛 Reporting Bugs

### Before Reporting

1. **Check existing issues:** Search for similar issues
2. **Verify it's a bug:** Test with minimal reproduction
3. **Gather information:** PHP version, extensions, error messages

### Bug Report Template

```markdown
**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Configure strategy as '...'
2. Call method '....'
3. See error

**Expected behavior**
A clear and concise description of what you expected to happen.

**Environment:**
- PHP version: [e.g., 8.3]
- SimdJson Polyfill version: [e.g., 1.0.0]
- Extensions: [e.g., ext-simdjson, ext-uopz]
- OS: [e.g., Ubuntu 22.04]

**Additional context**
Add any other context about the problem here.
```

## 💡 Feature Requests

We welcome feature requests! Please:

1. **Check existing requests:** Avoid duplicates
2. **Explain the use case:** Why is this feature needed?
3. **Propose a solution:** How should it work?
4. **Consider alternatives:** What other approaches did you consider?

## 🏗️ Project Structure

```
simdjson-polyfill/
├── src/
│   ├── Strategy/          # Strategy implementations
│   ├── Bridge/            # Framework integrations
│   ├── SimdJsonPolyfill.php
│   ├── JsonDecoder.php
│   └── functions.php
├── tests/
│   ├── Unit/              # Unit tests
│   ├── Integration/       # Integration tests
│   └── Benchmark/         # Benchmarks
├── .github/
│   └── workflows/         # CI/CD
└── docs/                  # Documentation
```

## 🎯 Development Guidelines

### Adding a New Strategy

1. Implement `StrategyInterface`
2. Add to `SimdJsonPolyfill::createStrategy()`
3. Write comprehensive unit tests
4. Document in README.md
5. Add integration test if applicable

### Adding Framework Integration

1. Create bridge in `src/Bridge/[Framework]/`
2. Follow framework conventions
3. Write integration tests
4. Document configuration in README.md

### Performance Considerations

- Benchmark new features with `task benchmark`
- Avoid unnecessary allocations
- Profile with Xdebug/Blackfire for hot paths

## 📚 Resources

- [PHP-FIG PSR-12](https://www.php-fig.org/psr/psr-12/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [SimdJson C++ Library](https://github.com/simdjson/simdjson)

## 🙏 Recognition

Contributors will be added to the README.md acknowledgments section.

## 📄 License

By contributing, you agree that your contributions will be licensed under the MIT License.

## ❓ Questions?

- **GitHub Discussions:** For general questions
- **GitHub Issues:** For bug reports and feature requests
- **Email:** For private inquiries

---

Thank you for contributing! 🎉
