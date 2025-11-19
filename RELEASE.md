# Release Notes

## Version 1.1.0 (Upcoming)

**Release Date:** TBD

### 🎉 New Features

#### Laravel Support
- **NEW**: Added Laravel Service Provider for seamless integration
- **NEW**: Laravel config file with full strategy configuration support
- Easy installation: Register `SimdJsonServiceProvider` and publish config
- Works with Laravel 8.x, 9.x, 10.x, and 11.x

#### PHP 8.4 Support
- Full support for PHP 8.4 (except UopzStrategy)
- All strategies work on PHP 8.4 except UOPZ (due to ZEND_EXIT opcode removal)
- Comprehensive test coverage for PHP 8.4

### 🔧 Improvements

- **NamespaceStrategy**: Fixed auto-detection issue by setting priority to 0 (requires explicit configuration)
- **Benchmarks**: Optimized for CI/CD with configurable iterations via `BENCHMARK_ITERATIONS` environment variable
- **GitHub Actions**: Fixed permissions for PR comment posting in benchmark workflow
- **Documentation**: Added PHP 8.4 compatibility notes and requirements section

### 🐛 Bug Fixes

- Fixed NamespaceStrategy causing runtime exceptions when auto-detected without configuration
- Fixed benchmark workflow 403 errors when posting PR comments
- Fixed duplicate benchmark runs in GitHub Actions

### 📚 Documentation

- Added comprehensive Requirements section in README
- Added Laravel integration documentation
- Updated UopzStrategy documentation with PHP 8.4 incompatibility warning
- Improved framework integration examples

### 🧪 Testing

- PHP 8.4 added to test matrix
- Separate test jobs for simdjson and uopz extensions
- Optimized benchmark runs for CI (2,000 iterations vs 10,000 locally)

### 📦 Framework Support

- ✅ Symfony 5.4, 6.x, 7.x
- ✅ Nette 3.x
- ✅ **Laravel 8.x, 9.x, 10.x, 11.x** (NEW)

---

## Version 1.0.0 (Initial Release)

**Release Date:** TBD

### 🎉 Features

#### Core Functionality
- **JsonDecoder**: Safe polyfill for fast JSON parsing using simdjson
- **SimdJsonPolyfill**: Automatic strategy selection and configuration
- **3x Performance**: Leverages simdjson extension for up to 3x faster JSON parsing

#### Multiple Strategies

1. **PolyfillStrategy** (Safe, Default)
   - Drop-in replacement via `JsonDecoder::decode()`
   - Helper function `fast_json_decode()` available
   - Zero risk, works everywhere

2. **UopzStrategy** (Risky, Powerful)
   - Global `json_decode()` override using UOPZ extension
   - Affects all code including vendor packages
   - Requires ext-uopz and explicit production approval

3. **NamespaceStrategy** (Medium Risk)
   - Generates namespace-specific `json_decode()` functions
   - Scoped to configured namespaces
   - Requires namespace configuration

4. **AutoPrependStrategy** (Medium Risk)
   - Generates file for PHP's `auto_prepend_file` directive
   - Global override without UOPZ
   - Requires php.ini configuration

5. **ComposerPluginStrategy** (Very Risky)
   - AST-level rewriting of vendor code
   - Build-time modification
   - Not auto-detected, always explicit

#### Framework Integration

- **Symfony Bundle**: Complete integration with bundle configuration
- **Nette Extension**: DI container integration for Nette framework
- Auto-configuration support for both frameworks

#### Performance

- **Benchmarks**: Comprehensive benchmark suite included
- **2.4-3x faster**: Measured performance improvements across different JSON sizes
- **Memory efficient**: Lower memory usage with simdjson

### 📋 Requirements

- PHP 8.0, 8.1, 8.2, or 8.3
- Composer
- Optional: ext-simdjson (recommended)
- Optional: ext-uopz (for UopzStrategy)

### 🧪 Testing

- PHPUnit tests for all strategies
- GitHub Actions CI/CD with multiple PHP versions
- PHPStan static analysis (level max)
- Test coverage for edge cases

### 📚 Documentation

- Comprehensive README with examples
- Strategy comparison table
- Framework integration guides
- Security warnings for risky strategies
- Benchmark results and reproduction steps

### 🐳 Development

- Docker-based development environment
- Task-based build system (Taskfile)
- Multi-version PHP testing (8.0-8.3)
- Benchmark tooling included

---

## Upgrade Guide

### Upgrading from 1.0.x to 1.1.0

**Breaking Changes:** None

**New Features:**
- Laravel support is now available
- PHP 8.4 support added (except UopzStrategy)

**Migration Steps:**

1. Update composer dependency:
   ```bash
   composer update freema/simdjson-polyfill
   ```

2. If using Laravel, register the Service Provider:
   ```php
   // config/app.php
   'providers' => [
       // ...
       SimdJsonPolyfill\Bridge\Laravel\SimdJsonServiceProvider::class,
   ],
   ```

3. Publish Laravel config (optional):
   ```bash
   php artisan vendor:publish --provider="SimdJsonPolyfill\Bridge\Laravel\SimdJsonServiceProvider" --tag="config"
   ```

4. If using PHP 8.4:
   - UopzStrategy will automatically be disabled
   - Use PolyfillStrategy, NamespaceStrategy, or AutoPrependStrategy instead
   - Update configuration if you explicitly specified 'uopz' strategy

**Deprecations:** None

---

## Release Checklist

Before releasing to Packagist:

- [ ] All tests passing on GitHub Actions
- [ ] PHPStan analysis passing
- [ ] Benchmarks running successfully
- [ ] Documentation updated
- [ ] CHANGELOG.md updated (if exists)
- [ ] Version tag created (v1.1.0)
- [ ] Merge to main branch
- [ ] Push tag to GitHub
- [ ] Packagist webhook triggered
- [ ] Verify installation: `composer require freema/simdjson-polyfill:^1.1`

---

## Support

- **Issues:** [GitHub Issues](https://github.com/freema/simdjson-polyfill/issues)
- **Discussions:** [GitHub Discussions](https://github.com/freema/simdjson-polyfill/discussions)
- **Documentation:** [README.md](README.md)
