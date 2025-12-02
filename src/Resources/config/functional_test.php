<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('liip_functional_test.exception_listener', \Liip\FunctionalTestBundle\EventListener\ExceptionListener::class)
        ->public()
        ->tag('kernel.event_subscriber');

    $services->set('liip_functional_test.query.count_client', \Liip\FunctionalTestBundle\QueryCountClient::class)
        ->args([
            service('kernel'),
            '%test.client.parameters%',
            service('test.client.history'),
            service('test.client.cookiejar'),
        ])
        ->call('setQueryCounter', [service('liip_functional_test.query.counter')]);

    $services->set('liip_functional_test.query.counter', \Liip\FunctionalTestBundle\QueryCounter::class)
        ->args([
            '%liip_functional_test.query.max_query_count%',
            service('annotation_reader')->nullOnInvalid(),
        ]);
};
