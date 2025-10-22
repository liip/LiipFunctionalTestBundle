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

namespace Liip\Acme\Tests\DependencyInjection;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test default configuration.
 */
class ConfigurationTest extends WebTestCase
{
    private ?ContainerInterface $clientContainer = null;

    protected function setUp(): void
    {
        $client = static::makeClient();
        $this->clientContainer = $client->getContainer();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        restore_exception_handler();
    }

    /**
     * @dataProvider parametersProvider
     *
     * @param string $node  Array key from parametersProvider
     * @param mixed  $value Array value from parametersProvider
     */
    #[DataProvider('parametersProvider')]
    public function testParameter(string $node, $value): void
    {
        $name = 'liip_functional_test.'.$node;

        $this->assertNotNull($this->clientContainer);

        $this->assertTrue(
            $this->clientContainer->hasParameter($name),
            $name.' parameter is not defined.'
        );

        $this->assertSame(
            $value,
            $this->clientContainer->getParameter($name)
        );
    }

    public static function parametersProvider(): array
    {
        return [
            ['command_verbosity', 'normal'],
            ['command_decoration', true],
            ['query.max_query_count', null],
            ['authentication.username', ''],
            ['authentication.password', ''],
        ];
    }
}
