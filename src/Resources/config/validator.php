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

    $services->set('liip_functional_test.validator', \Liip\FunctionalTestBundle\Validator\DataCollectingValidator::class)
        ->public()
        ->decorate('validator', 'validator.inner', 0, \Symfony\Component\DependencyInjection\ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
        ->args([service('validator.inner')->ignoreOnInvalid()])
        ->tag('kernel.event_subscriber');
};
