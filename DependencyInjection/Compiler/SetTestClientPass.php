<?php

namespace Liip\FunctionalTestBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Alias;

class SetTestClientPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
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
        } elseif ($container->hasAlias('test.client')) {
            // Symfony <2.8
            $container->setAlias(
                'liip_functional_test.query.count_client.parent',
                new Alias((string) $container->getAlias('test.client'), false)
            );
        } else {
            throw new \Exception('The LiipFunctionalTestBundle\'s Query Counter can only be used in the test environment.'.PHP_EOL.'See https://github.com/liip/LiipFunctionalTestBundle#only-in-test-environment');
        }

        $container->setAlias('test.client', 'liip_functional_test.query.count_client');
    }
}
