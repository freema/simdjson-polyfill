<?php

declare(strict_types=1);

namespace SimdJsonPolyfill\Bridge\Nette\DI;

use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use SimdJsonPolyfill\JsonDecoder;
use SimdJsonPolyfill\SimdJsonPolyfill;

/**
 * Nette DI Extension for SimdJsonPolyfill integration.
 */
final class SimdJsonExtension extends CompilerExtension
{
    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'enabled' => Expect::bool(true)
                ->info('Enable simdjson integration'),
            'strategy' => Expect::anyOf('auto', 'uopz', 'namespace', 'composer-plugin', 'auto-prepend', 'polyfill')
                ->default('auto')
                ->info('Strategy to use for json_decode override'),
            'auto_detect' => Expect::bool(true)
                ->info('Auto-detect best available strategy'),
            'uopz' => Expect::structure([
                'allow_in_production' => Expect::bool(false)
                    ->info('Allow UOPZ strategy in production environment'),
            ]),
            'namespace' => Expect::structure([
                'namespaces' => Expect::listOf('string')
                    ->default([])
                    ->info('Namespaces to generate json_decode functions for'),
                'output_dir' => Expect::string()->nullable()
                    ->info('Directory for generated namespace functions'),
            ]),
            'composer_plugin' => Expect::structure([
                'i_understand_the_risks' => Expect::bool(false)
                    ->info('Confirm understanding of vendor code modification risks'),
                'vendor_dir' => Expect::string()->nullable()
                    ->info('Vendor directory path'),
                'exclude_patterns' => Expect::listOf('string')
                    ->default(['*/tests/*', '*/Tests/*'])
                    ->info('Patterns to exclude from rewriting'),
                'create_backups' => Expect::bool(true)
                    ->info('Create .bak backups before modifying files'),
            ]),
            'auto_prepend' => Expect::structure([
                'output_file' => Expect::string()->nullable()
                    ->info('Path for auto-prepend file'),
            ]),
        ]);
    }

    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();
        $config = $this->getConfig();

        // Register JsonDecoder service
        $builder->addDefinition($this->prefix('json_decoder'))
            ->setFactory(JsonDecoder::class)
            ->setType(JsonDecoder::class);

        // Register SimdJsonPolyfill service
        $builder->addDefinition($this->prefix('polyfill'))
            ->setFactory(SimdJsonPolyfill::class)
            ->setType(SimdJsonPolyfill::class);
    }

    public function beforeCompile(): void
    {
        $config = $this->getConfig();

        if (!$config->enabled) {
            return;
        }

        $this->enableSimdJson((array) $config);
    }

    /**
     * Enable SimdJsonPolyfill with configuration.
     *
     * @param array<string, mixed> $config
     */
    private function enableSimdJson(array $config): void
    {
        $strategyConfig = [];

        // Determine strategy
        if ($config['strategy'] !== 'auto') {
            $strategyConfig['strategy'] = $config['strategy'];
            $strategyConfig['auto_detect'] = false;
        } else {
            $strategyConfig['auto_detect'] = $config['auto_detect'];
        }

        // Add strategy-specific config
        foreach (['uopz', 'namespace', 'composer_plugin', 'auto_prepend'] as $strategy) {
            if (isset($config[$strategy]) && !empty($config[$strategy])) {
                $strategyData = (array) $config[$strategy];
                $strategyConfig = array_merge($strategyConfig, $strategyData);
            }
        }

        try {
            SimdJsonPolyfill::enable($strategyConfig);
        } catch (\RuntimeException $e) {
            // Log error but don't break application bootstrap
            trigger_error(
                "Failed to enable SimdJsonPolyfill: {$e->getMessage()}",
                E_USER_WARNING
            );
        }
    }
}
