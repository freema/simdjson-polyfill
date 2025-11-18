<?php

declare(strict_types=1);

namespace SimdJsonPolyfill;

/**
 * Static facade for convenient JSON decoding with simdjson.
 *
 * Provides a drop-in replacement for json_decode() when using PolyfillStrategy.
 */
final class JsonDecoder
{
    /**
     * Decode JSON string using simdjson when available.
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
        return SimdJsonPolyfill::decode($json, $associative, $depth, $flags);
    }

    /**
     * Check if simdjson extension is available.
     */
    public static function isSimdJsonAvailable(): bool
    {
        return SimdJsonPolyfill::isSimdJsonAvailable();
    }
}
