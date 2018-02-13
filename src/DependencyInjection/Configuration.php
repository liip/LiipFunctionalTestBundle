<?php

declare(strict_types=1);

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class contains the configuration information for the bundle.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('liip_functional_test', 'array');
        $rootNode
            ->children()
                ->booleanNode('cache_sqlite_db')->defaultFalse()->end()
                ->arrayNode('cache_db')
                    ->addDefaultsIfNotSet()
                    ->ignoreExtraKeys(false)
                        ->children()
                            ->scalarNode('sqlite')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
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
