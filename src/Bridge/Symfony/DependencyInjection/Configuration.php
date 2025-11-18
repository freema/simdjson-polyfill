<?php

declare(strict_types=1);

namespace SimdJsonPolyfill\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for SimdJsonBundle.
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('simdjson');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->booleanNode('enabled')
                    ->defaultTrue()
                    ->info('Enable simdjson integration')
                ->end()
                ->enumNode('strategy')
                    ->values(['auto', 'uopz', 'namespace', 'composer-plugin', 'auto-prepend', 'polyfill'])
                    ->defaultValue('auto')
                    ->info('Strategy to use for json_decode override')
                ->end()
                ->booleanNode('auto_detect')
                    ->defaultTrue()
                    ->info('Auto-detect best available strategy')
                ->end()
                ->arrayNode('uopz')
                    ->children()
                        ->booleanNode('allow_in_production')
                            ->defaultFalse()
                            ->info('Allow UOPZ strategy in production environment')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('namespace')
                    ->children()
                        ->arrayNode('namespaces')
                            ->scalarPrototype()->end()
                            ->info('Namespaces to generate json_decode functions for')
                        ->end()
                        ->scalarNode('output_dir')
                            ->defaultNull()
                            ->info('Directory for generated namespace functions')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('composer_plugin')
                    ->children()
                        ->booleanNode('i_understand_the_risks')
                            ->defaultFalse()
                            ->info('Confirm understanding of vendor code modification risks')
                        ->end()
                        ->scalarNode('vendor_dir')
                            ->defaultNull()
                            ->info('Vendor directory path')
                        ->end()
                        ->arrayNode('exclude_patterns')
                            ->scalarPrototype()->end()
                            ->defaultValue(['*/tests/*', '*/Tests/*'])
                            ->info('Patterns to exclude from rewriting')
                        ->end()
                        ->booleanNode('create_backups')
                            ->defaultTrue()
                            ->info('Create .bak backups before modifying files')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('auto_prepend')
                    ->children()
                        ->scalarNode('output_file')
                            ->defaultNull()
                            ->info('Path for auto-prepend file')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
