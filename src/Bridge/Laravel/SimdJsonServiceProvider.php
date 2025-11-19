<?php

declare(strict_types=1);

namespace SimdJsonPolyfill\Bridge\Laravel;

use Illuminate\Support\ServiceProvider;
use SimdJsonPolyfill\JsonDecoder;
use SimdJsonPolyfill\SimdJsonPolyfill;

/**
 * Laravel Service Provider for SimdJsonPolyfill integration.
 */
final class SimdJsonServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/simdjson.php',
            'simdjson'
        );

        // Register JsonDecoder as singleton
        $this->app->singleton(JsonDecoder::class, function () {
            return new JsonDecoder();
        });

        // Register SimdJsonPolyfill as singleton
        $this->app->singleton(SimdJsonPolyfill::class, function () {
            return new SimdJsonPolyfill();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../../config/simdjson.php' => config_path('simdjson.php'),
        ], 'config');

        // Enable SimdJsonPolyfill if configured
        if (config('simdjson.enabled', false)) {
            $this->enableSimdJson();
        }
    }

    /**
     * Enable SimdJsonPolyfill with configuration.
     */
    private function enableSimdJson(): void
    {
        $strategyConfig = [];

        // Determine strategy
        $strategy = config('simdjson.strategy', 'auto');
        if ($strategy !== 'auto') {
            $strategyConfig['strategy'] = $strategy;
            $strategyConfig['auto_detect'] = false;
        } else {
            $strategyConfig['auto_detect'] = config('simdjson.auto_detect', true);
        }

        // Add strategy-specific config
        foreach (['uopz', 'namespace', 'composer_plugin', 'auto_prepend'] as $strategyName) {
            $strategySettings = config("simdjson.{$strategyName}", []);
            if (!empty($strategySettings)) {
                $strategyConfig = array_merge($strategyConfig, $strategySettings);
            }
        }

        try {
            SimdJsonPolyfill::enable($strategyConfig);
        } catch (\RuntimeException $e) {
            // Log error but don't break application bootstrap
            if ($this->app->has('log')) {
                $this->app->make('log')->warning(
                    "Failed to enable SimdJsonPolyfill: {$e->getMessage()}"
                );
            }
        }
    }
}
