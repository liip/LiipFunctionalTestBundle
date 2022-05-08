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

namespace Liip\FunctionalTestBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SetTestClientPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (null === $container->getParameter('liip_functional_test.query.max_query_count')) {
            $container->removeDefinition('liip_functional_test.query.count_client');

            return;
        }

        if ($container->hasDefinition('test.client')) {
            // test.client is a definition.
            // Register it again as a private service to inject it as the parent
            $definition = $container->getDefinition('test.client');
            $definition->setPublic(false);
            $container->setDefinition('liip_functional_test.query.count_client.parent', $definition);
        } else {
            throw new \Exception('The LiipFunctionalTestBundle\'s Query Counter can only be used in the test environment.'.\PHP_EOL.'See https://github.com/liip/LiipFunctionalTestBundle#only-in-test-environment');
        }

        $container->setAlias('test.client', new Alias('liip_functional_test.query.count_client', true));
    }
}
