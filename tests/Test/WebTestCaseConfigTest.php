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

namespace Liip\Acme\Tests\Test;

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Liip\Acme\Tests\App\Entity\User;
use Liip\Acme\Tests\AppConfig\AppConfigKernel;
use Liip\Acme\Tests\Traits\LiipAcmeFixturesTrait;
use Liip\FunctionalTestBundle\Test\WebTestCase;

/**
 * Tests that configuration has been loaded and users can be logged in.
 *
 * Use Tests/AppConfig/AppConfigKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 *
 * Avoid conflict with PHPUnit annotation when reading QueryCount
 * annotation:
 *
 * @IgnoreAnnotation("expectedException")
 */
class WebTestCaseConfigTest extends WebTestCase
{
    use LiipAcmeFixturesTrait;

    /** @var \Symfony\Bundle\FrameworkBundle\Client client */
    private $client;

    protected static function getKernelClass(): string
    {
        return AppConfigKernel::class;
    }

    /**
     * Log in as a user.
     */
    public function testIndexClientWithCredentials(): void
    {
        $this->client = static::createClientWithParams([], 'foobar', '12341234');

        $path = '/admin';

        $crawler = $this->client->request('GET', $path);

        self::assertResponseStatusCodeSame(200);

        $this->assertSame(
            1,
            $crawler->filter('html > body')->count()
        );

        $this->assertSame(
            'Logged in as foobar.',
            $crawler->filter('p#user')->text()
        );

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );
    }
}
