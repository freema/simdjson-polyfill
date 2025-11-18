<?php

declare(strict_types=1);

namespace SimdJsonPolyfill\Strategy;

/**
 * Interface for different simdjson integration strategies.
 */
interface StrategyInterface
{
    /**
     * Check if this strategy can be used in the current environment.
     */
    public function isAvailable(): bool;

    /**
     * Enable this strategy globally.
     *
     * @param array<string, mixed> $config Configuration options
     * @throws \RuntimeException If strategy cannot be enabled
     */
    public function enable(array $config = []): void;

    /**
     * Decode JSON string using this strategy.
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
    ): mixed;

    /**
     * Get strategy name for identification.
     */
    public function getName(): string;

    /**
     * Get priority for auto-detection (higher = preferred).
     */
    public function getPriority(): int;
}
