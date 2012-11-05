<?php

namespace Liip\FunctionalTestBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class SetTestClientPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (null !== $container->getParameter('liip_functional_test.query_count.max_query_count')) {
            $container->setAlias('test.client', 'liip_functional_test.query_count.query_count_client');
        }
    }
}
