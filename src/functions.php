<?php

declare(strict_types=1);

namespace SimdJsonPolyfill;

/**
 * Global helper functions for convenient JSON decoding.
 */

if (!function_exists('SimdJsonPolyfill\fast_json_decode')) {
    /**
     * Fast JSON decode using simdjson when available.
     *
     * This is a convenience function that uses SimdJsonPolyfill automatically.
     *
     * @param string $json JSON string to decode
     * @param bool|null $associative Return associative array instead of object
     * @param int $depth Maximum nesting depth
     * @param int $flags Bitmask of JSON decode options
     * @return mixed Decoded value
     * @throws \JsonException On decode error when JSON_THROW_ON_ERROR is set
     */
    function fast_json_decode(
        string $json,
        ?bool $associative = null,
        int $depth = 512,
        int $flags = 0
    ): mixed {
        return SimdJsonPolyfill::decode($json, $associative, $depth, $flags);
    }
}

if (!function_exists('SimdJsonPolyfill\is_simdjson_available')) {
    /**
     * Check if simdjson extension is loaded.
     */
    function is_simdjson_available(): bool
    {
        return SimdJsonPolyfill::isSimdJsonAvailable();
    }
}
