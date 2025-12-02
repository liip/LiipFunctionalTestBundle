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

namespace Liip\FunctionalTestBundle\Utils;

use Liip\FunctionalTestBundle\Test\ValidationErrorsConstraint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HttpAssertions extends TestCase
{
    /**
     * Assert that the last validation errors within $container match the
     * expected keys.
     *
     * @param array $expected A flat array of field names
     */
    public static function assertValidationErrors(array $expected, ContainerInterface $container): void
    {
        if ($container->has('test.service_container')) {
            $container = $container->get('test.service_container');
        }

        if (!$container->has('liip_functional_test.validator')) {
            self::fail(\sprintf(
                'Method %s() can not be used as the validation component of the Symfony framework is disabled.',
                __METHOD__
            ));
        }

        self::assertThat(
            $container->get('liip_functional_test.validator')->getLastErrors(),
            new ValidationErrorsConstraint($expected),
            'Validation errors should match.'
        );
    }
}
