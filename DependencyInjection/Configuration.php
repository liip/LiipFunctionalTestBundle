<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * This class contains the configuration information for the bundle.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('liip_functional_test', 'array');
        $rootNode
            ->beforeNormalization()
                ->ifArray()->then(function ($v) {
                    if (!empty($v['query_count.max_query_count'])) {
                        // Normalization is for BC.
    // @codeCoverageIgnoreStart
     $v['query']['max_query_count'] = $v['query_count.max_query_count'];
                        unset($v['query_count.max_query_count']);
                    }
// @codeCoverageIgnoreEnd

return $v;
                })
            ->end()
            ->children()
                ->booleanNode('cache_sqlite_db')->defaultFalse()->end()
                ->scalarNode('command_verbosity')->defaultValue('normal')->end()
                ->booleanNode('command_decoration')->defaultTrue()->end()
                ->arrayNode('query')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('max_query_count')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('authentication')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('username')
                            ->defaultValue('')
                        ->end()
                        ->scalarNode('password')
                            ->defaultValue('')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('html5validation')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('url')
                            ->defaultValue('https://validator.nu/')
                        ->end()
                        ->arrayNode('ignores')
                            ->prototype('scalar')->end()
                            ->defaultValue(array())
                        ->end()
                        ->arrayNode('ignores_extract')
                            ->prototype('scalar')->end()
                            ->defaultValue(array())
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('paratest')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('process')
                            ->defaultValue(5)
                        ->end()
                        ->scalarNode('phpunit')
                            ->defaultValue('./bin/phpunit')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
