<?php

namespace Liip\FunctionalTestBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Alias;

class SetTestClientPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (null !== $container->getParameter('liip_functional_test.query_count.max_query_count')) {
            if ($container->hasAlias('test.client')) {
                // test.client is an alias.
                // Register a private alias for this service to inject it as the parent
                $container->setAlias(
                    'liip_functional_test.query_count.query_count_client.parent',
                    new Alias((string) $container->getAlias('test.client'), false)
                );
            } else {
                // test.client is a definition.
                // Register it again as a private service to inject it as the parent
                $definition = $container->getDefinition('test.client');
                $definition->setPublic(false);
                $container->setDefinition('liip_functional_test.query_count.query_count_client.parent', $definition);
            }

            $container->setAlias('test.client', 'liip_functional_test.query_count.query_count_client');
        }
    }
}
