<?php

declare(strict_types=1);

namespace SimdJsonPolyfill;

use SimdJsonPolyfill\Strategy\AutoPrependStrategy;
use SimdJsonPolyfill\Strategy\ComposerPluginStrategy;
use SimdJsonPolyfill\Strategy\NamespaceStrategy;
use SimdJsonPolyfill\Strategy\PolyfillStrategy;
use SimdJsonPolyfill\Strategy\StrategyInterface;
use SimdJsonPolyfill\Strategy\UopzStrategy;

/**
 * Main facade class for SimdJsonPolyfill.
 *
 * Provides auto-detection of best available strategy and global enabling.
 */
final class SimdJsonPolyfill
{
    private static ?StrategyInterface $activeStrategy = null;

    /** @var array<StrategyInterface> */
    private static array $availableStrategies = [];

    /**
     * Enable simdjson integration with auto-detected or specified strategy.
     *
     * @param array<string, mixed> $config Configuration options:
     *   - 'strategy': Explicitly specify strategy name (uopz, namespace, composer-plugin, auto-prepend, polyfill)
     *   - 'auto_detect': Auto-detect best strategy (default: true)
     *   - Additional strategy-specific options
     * @throws \RuntimeException If no strategy is available
     */
    public static function enable(array $config = []): void
    {
        $strategyName = $config['strategy'] ?? null;
        $autoDetect = $config['auto_detect'] ?? true;

        if ($strategyName !== null) {
            // Use explicitly specified strategy
            $strategy = self::createStrategy($strategyName);
            if (!$strategy->isAvailable()) {
                throw new \RuntimeException(
                    "Strategy '{$strategyName}' is not available in this environment."
                );
            }
            $strategy->enable($config);
            self::$activeStrategy = $strategy;
            return;
        }

        if (!$autoDetect) {
            throw new \RuntimeException(
                'No strategy specified and auto_detect is disabled. ' .
                'Either set "strategy" or enable "auto_detect".'
            );
        }

        // Auto-detect best strategy (most aggressive first)
        $strategy = self::detectBestStrategy();
        if ($strategy === null) {
            throw new \RuntimeException(
                'No suitable strategy found. Install ext-simdjson for best performance.'
            );
        }

        $strategy->enable($config);
        self::$activeStrategy = $strategy;
    }

    /**
     * Get the currently active strategy.
     */
    public static function getActiveStrategy(): ?StrategyInterface
    {
        return self::$activeStrategy;
    }

    /**
     * Decode JSON using the active strategy.
     *
     * @param string $json JSON string to decode
     * @param bool|null $associative Return associative array instead of object
     * @param int $depth Maximum nesting depth
     * @param int $flags Bitmask of JSON decode options
     * @return mixed Decoded value
     * @throws \JsonException On decode error when JSON_THROW_ON_ERROR is set
     */
    public static function decode(
        string $json,
        ?bool $associative = null,
        int $depth = 512,
        int $flags = 0
    ): mixed {
        if (self::$activeStrategy === null) {
            // Auto-enable with default config if not yet enabled
            self::enable();
        }

        return self::$activeStrategy->decode($json, $associative, $depth, $flags);
    }

    /**
     * Auto-detect the best available strategy.
     *
     * Priority order (highest to lowest):
     * 1. UopzStrategy - Global override with ext-uopz
     * 2. NamespaceStrategy - Namespace-specific overrides
     * 3. AutoPrependStrategy - PHP auto_prepend_file
     * 4. PolyfillStrategy - Safe fallback
     *
     * Note: ComposerPluginStrategy is never auto-detected (priority 0)
     */
    private static function detectBestStrategy(): ?StrategyInterface
    {
        $strategies = self::getAvailableStrategies();

        // Sort by priority (highest first)
        usort($strategies, fn($a, $b) => $b->getPriority() <=> $a->getPriority());

        // Return first available strategy with priority > 0
        foreach ($strategies as $strategy) {
            if ($strategy->getPriority() > 0 && $strategy->isAvailable()) {
                return $strategy;
            }
        }

        return null;
    }

    /**
     * Get all available strategy instances.
     *
     * @return array<StrategyInterface>
     */
    private static function getAvailableStrategies(): array
    {
        if (empty(self::$availableStrategies)) {
            self::$availableStrategies = [
                new UopzStrategy(),
                new NamespaceStrategy(),
                new AutoPrependStrategy(),
                new PolyfillStrategy(),
                new ComposerPluginStrategy(),
            ];
        }

        return self::$availableStrategies;
    }

    /**
     * Create a strategy instance by name.
     */
    private static function createStrategy(string $name): StrategyInterface
    {
        return match ($name) {
            'uopz' => new UopzStrategy(),
            'namespace' => new NamespaceStrategy(),
            'composer-plugin' => new ComposerPluginStrategy(),
            'auto-prepend' => new AutoPrependStrategy(),
            'polyfill' => new PolyfillStrategy(),
            default => throw new \InvalidArgumentException("Unknown strategy: {$name}"),
        };
    }

    /**
     * Check if simdjson extension is loaded.
     */
    public static function isSimdJsonAvailable(): bool
    {
        return extension_loaded('simdjson');
    }

    /**
     * Get information about available strategies.
     *
     * @return array<string, array{name: string, available: bool, priority: int}>
     */
    public static function getStrategyInfo(): array
    {
        $strategies = self::getAvailableStrategies();
        $info = [];

        foreach ($strategies as $strategy) {
            $info[$strategy->getName()] = [
                'name' => $strategy->getName(),
                'available' => $strategy->isAvailable(),
                'priority' => $strategy->getPriority(),
            ];
        }

        return $info;
    }
}
