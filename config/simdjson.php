<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable SimdJson Integration
    |--------------------------------------------------------------------------
    |
    | Enable or disable simdjson integration. When enabled, the configured
    | strategy will be used to accelerate JSON parsing.
    |
    */
    'enabled' => env('SIMDJSON_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Strategy
    |--------------------------------------------------------------------------
    |
    | Strategy to use for json_decode override. Available options:
    | - auto: Automatically detect the best available strategy
    | - polyfill: Safe polyfill using JsonDecoder::decode()
    | - uopz: Global override using UOPZ extension (requires ext-uopz)
    | - namespace: Namespace-specific overrides
    | - auto-prepend: PHP auto_prepend_file approach
    | - composer-plugin: AST rewriting (risky)
    |
    */
    'strategy' => env('SIMDJSON_STRATEGY', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Auto Detect
    |--------------------------------------------------------------------------
    |
    | Automatically detect the best available strategy when strategy is 'auto'.
    |
    */
    'auto_detect' => env('SIMDJSON_AUTO_DETECT', true),

    /*
    |--------------------------------------------------------------------------
    | UOPZ Strategy Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the UOPZ strategy. This strategy uses the UOPZ
    | extension to globally override json_decode() at runtime.
    |
    | WARNING: UOPZ strategy is not compatible with PHP 8.4+
    |
    */
    'uopz' => [
        'allow_in_production' => env('SIMDJSON_UOPZ_ALLOW_PRODUCTION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespace Strategy Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the namespace strategy. This generates namespace-
    | specific json_decode() functions.
    |
    */
    'namespace' => [
        'namespaces' => [
            // 'App\\Services',
            // 'App\\Http\\Controllers',
        ],
        'output_dir' => storage_path('framework/simdjson'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Prepend Strategy Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the auto-prepend strategy. This generates a file
    | for PHP's auto_prepend_file directive.
    |
    */
    'auto_prepend' => [
        'output_file' => storage_path('framework/simdjson-prepend.php'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Composer Plugin Strategy Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Composer plugin strategy. This rewrites
    | json_decode() calls in vendor/ at install time.
    |
    | WARNING: This is extremely risky and modifies third-party code!
    |
    */
    'composer_plugin' => [
        'i_understand_the_risks' => false,
        'vendor_dir' => base_path('vendor'),
        'exclude_patterns' => [
            '*/tests/*',
            '*/Tests/*',
        ],
        'create_backups' => true,
    ],
];
