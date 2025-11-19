<?php

declare(strict_types=1);

namespace SimdJsonPolyfill\Strategy;

/**
 * UOPZ strategy - overrides native json_decode() globally using the UOPZ extension.
 *
 * ⚠️ WARNING: This is a risky strategy that modifies runtime behavior globally.
 * Requires ext-uopz to be installed.
 *
 * ⚠️ NOT COMPATIBLE WITH PHP 8.4+: The uopz extension does not support PHP 8.4 due to
 * the removal of the ZEND_EXIT opcode. Use an alternative strategy for PHP 8.4+.
 */
final class UopzStrategy implements StrategyInterface
{
    private PolyfillStrategy $polyfill;

    public function __construct()
    {
        $this->polyfill = new PolyfillStrategy();
    }

    public function isAvailable(): bool
    {
        // uopz is not compatible with PHP 8.4+ due to ZEND_EXIT opcode removal
        if (PHP_VERSION_ID >= 80400) {
            return false;
        }

        return extension_loaded('uopz') && extension_loaded('simdjson');
    }

    public function enable(array $config = []): void
    {
        // Check PHP version compatibility first
        if (PHP_VERSION_ID >= 80400) {
            throw new \RuntimeException(
                'UopzStrategy is not supported on PHP 8.4 or higher. ' .
                'The uopz extension is incompatible with PHP 8.4 due to the removal of the ZEND_EXIT opcode. ' .
                'Please use an alternative strategy (PolyfillStrategy, AutoPrependStrategy, or NamespaceStrategy).'
            );
        }

        if (!$this->isAvailable()) {
            throw new \RuntimeException(
                'UopzStrategy requires both ext-uopz and ext-simdjson to be installed.'
            );
        }

        // Safety check: verify we're not in production unless explicitly allowed
        $allowInProduction = $config['allow_in_production'] ?? false;
        if (!$allowInProduction && $this->isProductionEnvironment()) {
            throw new \RuntimeException(
                'UopzStrategy is disabled in production by default. ' .
                'Set allow_in_production => true in config to override.'
            );
        }

        // Override json_decode globally
        uopz_set_return(
            'json_decode',
            function (
                string $json,
                ?bool $associative = null,
                int $depth = 512,
                int $flags = 0
            ): mixed {
                return $this->polyfill->decode($json, $associative, $depth, $flags);
            },
            true // Execute as userland function
        );
    }

    public function decode(
        string $json,
        ?bool $associative = null,
        int $depth = 512,
        int $flags = 0
    ): mixed {
        return $this->polyfill->decode($json, $associative, $depth, $flags);
    }

    public function getName(): string
    {
        return 'uopz';
    }

    public function getPriority(): int
    {
        return 100; // Highest priority - most aggressive
    }

    /**
     * Detect if we're running in a production environment.
     */
    private function isProductionEnvironment(): bool
    {
        $env = getenv('APP_ENV') ?: getenv('ENVIRONMENT') ?: 'production';
        return in_array(strtolower($env), ['prod', 'production'], true);
    }
}
