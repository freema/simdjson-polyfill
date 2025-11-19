# 🚀 SimdJson Polyfill

[![Tests](https://github.com/freema/simdjson-polyfill/workflows/Tests/badge.svg)](https://github.com/freema/simdjson-polyfill/actions)
[![Latest Stable Version](https://poser.pugx.org/tomasgrasl/simdjson-polyfill/v)](https://packagist.org/packages/tomasgrasl/simdjson-polyfill)
[![License](https://poser.pugx.org/tomasgrasl/simdjson-polyfill/license)](https://packagist.org/packages/tomasgrasl/simdjson-polyfill)

**Automatic [simdjson](https://github.com/simdjson/simdjson) integration for PHP with multiple override strategies.**

Get **3x faster JSON parsing** with minimal code changes. Drop-in replacement for `json_decode()` with Symfony & Nette support included.

## 📋 Table of Contents

- [Why SimdJson?](#why-simdjson)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Strategies](#strategies)
- [Framework Integration](#framework-integration)
- [Benchmarks](#benchmarks)
- [Development](#development)
- [Contributing](#contributing)
- [License](#license)

## 🎯 Why SimdJson?

[simdjson](https://simdjson.org/) is the world's fastest JSON parser, using SIMD instructions to parse JSON at speeds approaching 3 GB/s. This library makes it trivial to use simdjson in your PHP applications.

**Performance gains:**
- 🚀 **3x faster** JSON parsing compared to native `json_decode()`
- ⚡ Perfect for APIs processing large JSON payloads
- 💾 Lower CPU usage and better resource utilization
- 🔥 Zero-copy parsing when possible

## 📋 Requirements

- PHP 8.0 or higher (tested on PHP 8.0, 8.1, 8.2, 8.3, and 8.4)
- Composer

**PHP 8.4 Note:** All strategies work on PHP 8.4 **except UopzStrategy**, which requires PHP ≤ 8.3 due to uopz extension incompatibility.

## 📦 Installation

```bash
composer require tomasgrasl/simdjson-polyfill
```

**Optional (for best performance):**
```bash
# Install simdjson PHP extension
pecl install simdjson
```

**Optional (for advanced strategies):**
```bash
# Install UOPZ extension for global override
pecl install uopz
```

## 🚀 Quick Start

### Option 1: Safe Polyfill (Recommended)

Use `JsonDecoder::decode()` or `fast_json_decode()` instead of `json_decode()`:

```php
use SimdJsonPolyfill\JsonDecoder;

// Using static method
$data = JsonDecoder::decode($json, true);

// Or using helper function
$data = \SimdJsonPolyfill\fast_json_decode($json, true);
```

### Option 2: Auto-Enable (Magic Mode)

Automatically use the best available strategy:

```php
use SimdJsonPolyfill\SimdJsonPolyfill;

// Enable once at application bootstrap
SimdJsonPolyfill::enable();

// Now json_decode() uses simdjson (if using aggressive strategies)
// Or use JsonDecoder::decode() for safe polyfill
```

### Option 3: Explicit Strategy

Choose a specific strategy:

```php
use SimdJsonPolyfill\SimdJsonPolyfill;

// Use UOPZ to override json_decode() globally
SimdJsonPolyfill::enable([
    'strategy' => 'uopz',
    'allow_in_production' => true, // Required for production
]);

// Now ALL json_decode() calls use simdjson!
$data = json_decode($json, true);
```

## 🎛️ Strategies

SimdJson Polyfill offers multiple strategies with different trade-offs:

| Strategy | Risk Level | Performance | Global Override | Requires Extension |
|----------|-----------|-------------|-----------------|-------------------|
| **PolyfillStrategy** | ✅ Safe | High | No | `ext-simdjson` |
| **UopzStrategy** | ⚠️ Risky | Highest | Yes | `ext-uopz`, `ext-simdjson` |
| **NamespaceStrategy** | ⚠️ Medium | High | Per-namespace | `ext-simdjson` |
| **AutoPrependStrategy** | ⚠️ Medium | High | Yes (manual) | `ext-simdjson` |
| **ComposerPluginStrategy** | 🔥 Very Risky | High | Yes (build-time) | `ext-simdjson`, `nikic/php-parser` |

### 1. PolyfillStrategy (Safe, Default)

**Recommended for most use cases.**

Provides `JsonDecoder::decode()` and `fast_json_decode()` functions. No magic, no risks.

```php
use SimdJsonPolyfill\JsonDecoder;

$data = JsonDecoder::decode($jsonString, true);
```

**Pros:**
- ✅ Zero risk - no global side effects
- ✅ Works everywhere
- ✅ Easy to test and debug

**Cons:**
- ❌ Requires code changes to use new functions
- ❌ Won't affect third-party code

### 2. UopzStrategy (Risky, Powerful)

**⚠️ WARNING: Modifies runtime behavior globally!**

Uses the [UOPZ extension](https://www.php.net/manual/en/book.uopz.php) to override `json_decode()` at runtime.

```php
SimdJsonPolyfill::enable([
    'strategy' => 'uopz',
    'allow_in_production' => true, // Required!
]);

// Now ALL json_decode() calls use simdjson
$data = json_decode($json, true);
```

**Pros:**
- ✅ Zero code changes needed
- ✅ Affects vendor code too
- ✅ Can be toggled on/off

**Cons:**
- ⚠️ Requires `ext-uopz`
- ⚠️ May have unexpected side effects
- ⚠️ Disabled in production by default
- ❌ **Not compatible with PHP 8.4+** (uopz doesn't support PHP 8.4 due to ZEND_EXIT opcode removal)

### 3. NamespaceStrategy (Medium Risk)

Generates namespace-specific `json_decode()` functions.

```php
SimdJsonPolyfill::enable([
    'strategy' => 'namespace',
    'namespaces' => ['App\\Services', 'App\\Controllers'],
    'output_dir' => '/tmp/simdjson-functions',
]);
```

**Pros:**
- ✅ Scoped to specific namespaces
- ✅ More controlled than UOPZ

**Cons:**
- ❌ Requires namespace configuration
- ❌ Generated files need to be autoloaded

### 4. AutoPrependStrategy (Medium Risk)

Generates a file for PHP's `auto_prepend_file` directive.

```php
SimdJsonPolyfill::enable([
    'strategy' => 'auto-prepend',
    'output_file' => '/var/www/simdjson-prepend.php',
]);

// Then configure php.ini:
// auto_prepend_file = "/var/www/simdjson-prepend.php"
```

**Pros:**
- ✅ Global override without UOPZ
- ✅ Works for all PHP scripts

**Cons:**
- ⚠️ Requires php.ini configuration
- ⚠️ Affects entire PHP environment

### 5. ComposerPluginStrategy (Very Risky)

**🔥 EXTREMELY RISKY: Modifies vendor code using AST rewriting!**

Rewrites `json_decode()` calls in vendor/ at install time.

```php
SimdJsonPolyfill::enable([
    'strategy' => 'composer-plugin',
    'i_understand_the_risks' => true, // Required!
    'create_backups' => true,
    'exclude_patterns' => ['*/tests/*'],
]);
```

**Pros:**
- ✅ Build-time modification
- ✅ No runtime overhead

**Cons:**
- 🔥 Modifies third-party code
- 🔥 May break updates
- 🔥 Creates .bak files everywhere
- 🔥 **Never auto-detected, always explicit**

## 🔌 Framework Integration

### Symfony

```php
// config/bundles.php
return [
    // ...
    SimdJsonPolyfill\Bridge\Symfony\SimdJsonBundle::class => ['all' => true],
];
```

```yaml
# config/packages/simdjson.yaml
simdjson:
    enabled: true
    strategy: auto  # or: polyfill, uopz, namespace, etc.
    auto_detect: true

    # UOPZ strategy config
    uopz:
        allow_in_production: false

    # Namespace strategy config
    namespace:
        namespaces:
            - 'App\Service'
            - 'App\Controller'
        output_dir: '%kernel.cache_dir%/simdjson'
```

### Nette

```php
// config/common.neon
extensions:
    simdjson: SimdJsonPolyfill\Bridge\Nette\DI\SimdJsonExtension

simdjson:
    enabled: true
    strategy: auto
    autoDetect: true

    namespace:
        namespaces:
            - App\Services
            - App\Presenters
```

## 📊 Benchmarks

Results from benchmarking on PHP 8.3:

```
========================================
SimdJsonPolyfill Benchmark
========================================
Iterations: 10000
PHP Version: 8.3.27
simdjson extension: YES
========================================

===== File: small.json (3.3 KB) =====
json_decode             98.26 ms (0.010 ms/op) | memΔ:     4 KB | peakΔ:      0 B
simdjson_decode         35.67 ms (0.004 ms/op) | memΔ:     4 KB | peakΔ:      0 B
➡️  simdjson_decode is approximately 2.75x faster (wall-time)
simdjson (polyfill)     35.69 ms (0.004 ms/op) | memΔ:     4 KB | peakΔ:      0 B

===== File: medium.json (50.6 KB) =====
json_decode            926.56 ms (0.093 ms/op) | memΔ:     4 KB | peakΔ:      0 B
simdjson_decode        379.83 ms (0.038 ms/op) | memΔ:     4 KB | peakΔ:      0 B
➡️  simdjson_decode is approximately 2.44x faster (wall-time)
simdjson (polyfill)    378.66 ms (0.038 ms/op) | memΔ:     4 KB | peakΔ:      0 B

===== File: large.json (101.1 KB) =====
json_decode          1,833.82 ms (0.183 ms/op) | memΔ:     4 KB | peakΔ:      0 B
simdjson_decode        792.74 ms (0.079 ms/op) | memΔ:     4 KB | peakΔ:      0 B
➡️  simdjson_decode is approximately 2.31x faster (wall-time)
simdjson (polyfill)    779.16 ms (0.078 ms/op) | memΔ:     4 KB | peakΔ:      0 B

===== File: xlarge.json (500.7 KB) =====
json_decode          9,130.94 ms (0.913 ms/op) | memΔ:     4 KB | peakΔ:   1.8 MB
simdjson_decode      3,752.57 ms (0.375 ms/op) | memΔ:     4 KB | peakΔ:    376 B
➡️  simdjson_decode is approximately 2.43x faster (wall-time)
simdjson (polyfill)  3,751.59 ms (0.375 ms/op) | memΔ:     4 KB | peakΔ:    376 B
```

**Run benchmarks yourself:**

```bash
task benchmark
# or
docker-compose run --rm php php tests/Benchmark/JsonDecodeBenchmark.php
```

## 🐳 Development

This project uses Docker for development and testing, with [Task](https://taskfile.dev/) as the build tool.

### Prerequisites

Install Task:
```bash
# macOS
brew install go-task

# Linux
sh -c "$(curl --location https://taskfile.dev/install.sh)" -- -d -b ~/.local/bin

# Or see https://taskfile.dev/installation/
```

### Setup

```bash
# Build containers
task build

# Install dependencies
task install

# Generate benchmark fixtures
task fixtures
```

### Testing

```bash
# Run all tests
task test

# Run tests with coverage
task test-coverage

# Test on all PHP versions
task test-all

# Run static analysis
task stan
```

### Benchmarking

```bash
# Generate fixtures
task fixtures

# Run benchmarks
task benchmark
```

### Available Commands

```bash
task --list          # Show all available commands
task shell           # Open shell in PHP container
task install         # Install Composer dependencies
task test            # Run PHPUnit tests
task benchmark       # Run benchmarks
task clean           # Clean up containers and dependencies
```

## ⚠️ Warnings and Best Practices

### When to Use Which Strategy

**Production Applications:**
- ✅ Use **PolyfillStrategy** - Safe, predictable, testable
- ⚠️ Consider **UopzStrategy** only if you thoroughly test

**Development/Staging:**
- ✅ Any strategy is fine for experimentation

**Never Use:**
- 🚫 **ComposerPluginStrategy** in production
- 🚫 **AutoPrependStrategy** without extensive testing

### Safety Tips

1. **Always test in staging first**
2. **Monitor for edge cases** - simdjson may parse JSON slightly differently
3. **Have a rollback plan** - disable with one config change
4. **Check compatibility** - some JSON edge cases may behave differently
5. **Use version control** - especially with ComposerPluginStrategy

## 🧪 Testing

```bash
# Unit tests
vendor/bin/phpunit

# With Docker
make test

# Test specific PHP version
make test-83

# Test all versions
make test-all
```

## 📄 License

MIT License. See [LICENSE](LICENSE) file for details.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 🔗 Links

- [simdjson](https://github.com/simdjson/simdjson) - The original C++ library
- [simdjson PHP extension](https://github.com/crazyxman/simdjson_php) - PHP extension for simdjson
- [Packagist](https://packagist.org/packages/tomasgrasl/simdjson-polyfill)
- [GitHub](https://github.com/freema/simdjson-polyfill)

## 📮 Support

- **Issues:** [GitHub Issues](https://github.com/freema/simdjson-polyfill/issues)
- **Discussions:** [GitHub Discussions](https://github.com/freema/simdjson-polyfill/discussions)

---

Made with ❤️ by [Tomas Grasl](https://github.com/freema)
