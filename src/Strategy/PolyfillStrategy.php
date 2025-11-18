<?php

declare(strict_types=1);

namespace SimdJsonPolyfill\Strategy;

/**
 * Safe polyfill strategy - provides wrapper functions without overriding native json_decode().
 * This is the safest option with no side effects.
 */
final class PolyfillStrategy implements StrategyInterface
{
    private bool $enabled = false;

    public function isAvailable(): bool
    {
        return true; // Always available
    }

    public function enable(array $config = []): void
    {
        $this->enabled = true;
    }

    public function decode(
        string $json,
        ?bool $associative = null,
        int $depth = 512,
        int $flags = 0
    ): mixed {
        // Use simdjson if available, otherwise fall back to native json_decode
        if (extension_loaded('simdjson')) {
            return $this->decodeWithSimdJson($json, $associative, $depth, $flags);
        }

        return $this->decodeWithNative($json, $associative, $depth, $flags);
    }

    public function getName(): string
    {
        return 'polyfill';
    }

    public function getPriority(): int
    {
        return 10; // Lowest priority - fallback option
    }

    /**
     * @param string $json
     * @param bool|null $associative
     * @param int $depth
     * @param int $flags
     * @return mixed
     * @throws \JsonException
     */
    private function decodeWithSimdJson(
        string $json,
        ?bool $associative,
        int $depth,
        int $flags
    ): mixed {
        try {
            // simdjson_decode signature: simdjson_decode(string $json, bool $assoc = false, int $depth = 512)
            $assoc = $associative ?? false;
            $result = simdjson_decode($json, $assoc, $depth);

            // Handle JSON_THROW_ON_ERROR flag
            if ($result === null && $flags & JSON_THROW_ON_ERROR) {
                $error = json_last_error();
                if ($error !== JSON_ERROR_NONE) {
                    throw new \JsonException(json_last_error_msg(), $error);
                }
            }

            return $result;
        } catch (\Exception $e) {
            if ($flags & JSON_THROW_ON_ERROR) {
                throw new \JsonException($e->getMessage(), $e->getCode(), $e);
            }
            return null;
        }
    }

    /**
     * @param string $json
     * @param bool|null $associative
     * @param int $depth
     * @param int $flags
     * @return mixed
     * @throws \JsonException
     */
    private function decodeWithNative(
        string $json,
        ?bool $associative,
        int $depth,
        int $flags
    ): mixed {
        return json_decode($json, $associative, $depth, $flags);
    }
}
