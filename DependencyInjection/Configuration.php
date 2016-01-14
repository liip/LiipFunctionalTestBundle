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
            ->ifArray()->then(
                function ($v)
                {
                    if (!empty($v['query_count.max_query_count']))
                    {
                        $v['query']['max_query_count'] = $v['query_count.max_query_count'];
                        unset($v['query_count.max_query_count']);
                    }

                    return $v;
                })->end()
            ->beforeNormalization()
            ->ifArray()->then(
                function ($v)
                {
                    if (isset($v['authentication']['username']) | isset($v['authentication']['password']))
                    {
                        // Put username and password in a new array with "default" as key
                        $username = (isset($v['authentication']['username'])) ? $v['authentication']['username'] : '';
                        $password = (isset($v['authentication']['password'])) ? $v['authentication']['password'] : '';

                        unset($v['authentication']['username']);
                        unset($v['authentication']['password']);

                        $v['authentication']['default'] = [
                            'username' => $username,
                            'password' => $password
                        ];
                    }

                    return $v;
                })->end()
            ->children()
            ->booleanNode('cache_sqlite_db')->defaultFalse()->end()
            ->scalarNode('command_verbosity')->defaultValue('normal')->end()
            ->booleanNode('command_decoration')->defaultTrue()->end()
            ->arrayNode('query')
            ->children()
            ->scalarNode('max_query_count')->end()
            ->end()
            ->end()
            ->arrayNode('authentication')
            ->prototype('array')
            ->children()
            ->scalarNode('username')->end()
            ->scalarNode('password')->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
