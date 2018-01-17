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

namespace Liip\FunctionalTestBundle\Tests\DependencyInjection;

use Liip\FunctionalTestBundle\Test\WebTestCase;

/**
 * Test default configuration.
 */
class ConfigurationTest extends WebTestCase
{
    /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
    private $container = null;

    public function setUp(): void
    {
        $client = static::makeClient();
        $this->container = $client->getContainer();
    }

    /**
     * @dataProvider parametersProvider
     *
     * @param string $node  Array key from parametersProvider
     * @param string $value Array value from parametersProvider
     */
    public function testParameter($node, $value): void
    {
        $name = 'liip_functional_test.'.$node;

        $this->assertNotNull($this->container);

        $this->assertTrue(
            $this->container->hasParameter($name),
            $name.' parameter is not defined.'
        );

        $this->assertSame(
            $value,
            $this->container->getParameter($name)
        );
    }

    public function parametersProvider(): array
    {
        return [
            ['cache_sqlite_db', false],
            ['command_verbosity', 'normal'],
            ['command_decoration', true],
            ['query.max_query_count', null],
            ['authentication.username', ''],
            ['authentication.password', ''],
            ['paratest.process', 5],
            ['paratest.phpunit', './bin/phpunit'],
        ];
    }
}
