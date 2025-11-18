<?php

declare(strict_types=1);

namespace SimdJsonPolyfill\Bridge\Symfony\DependencyInjection;

use SimdJsonPolyfill\JsonDecoder;
use SimdJsonPolyfill\SimdJsonPolyfill;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * DI Extension for SimdJsonBundle.
 */
final class SimdJsonExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Load services
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yaml');

        // Set parameters
        $container->setParameter('simdjson.enabled', $config['enabled']);
        $container->setParameter('simdjson.strategy', $config['strategy']);
        $container->setParameter('simdjson.config', $config);

        // Enable SimdJsonPolyfill if configured
        if ($config['enabled']) {
            $this->enableSimdJson($config);
        }
    }

    public function getAlias(): string
    {
        return 'simdjson';
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
                $strategyConfig = array_merge($strategyConfig, $config[$strategy]);
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
