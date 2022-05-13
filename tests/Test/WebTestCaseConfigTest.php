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
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Liip\FunctionalTestBundle\Test\WebTestCase;

/**
 * Tests that configuration has been loaded and users can be logged in.
 *
 * Use Tests/AppConfig/AppConfigKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
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
    private $client = null;

    protected static function getKernelClass(): string
    {
        return AppConfigKernel::class;
    }

    /**
     * Log in as an user.
     */
    public function testIndexClientWithCredentials(): void
    {
        $this->client = static::makeClientWithCredentials('foobar', '12341234');

        $path = '/admin';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

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

    /**
     * Log in as the user defined in the
     * "liip_functional_test.authentication"
     * node from the configuration file.
     */
    public function testIndexAuthenticatedClient(): void
    {
        $this->client = static::makeAuthenticatedClient();

        $path = '/admin';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

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

    /**
     * Log in as the user defined in the Data Fixture.
     */
    public function testIndexAuthenticationLoginAs(): void
    {
        $user = $this->loadTestFixtures();

        $loginAs = $this->loginAs($user, 'secured_area');

        $this->assertInstanceOf(
            'Liip\FunctionalTestBundle\Test\WebTestCase',
            $loginAs
        );

        $this->client = static::makeClient();

        $path = '/';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        $this->assertSame(
            1,
            $crawler->filter('html > body')->count()
        );

        $this->assertSame(
            'Logged in as foo bar.',
            $crawler->filter('p#user')->text()
        );

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );
    }

    /**
     * Log in as the user defined in the Data Fixture.
     */
    public function testIndexAuthenticationLoginClient(): void
    {
        $user = $this->loadTestFixtures();

        $this->client = static::makeClient();

        $this->loginClient($this->client, $user, 'secured_area');

        $path = '/';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        $this->assertSame(
            1,
            $crawler->filter('html > body')->count()
        );

        $this->assertSame(
            'Logged in as foo bar.',
            $crawler->filter('p#user')->text()
        );

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );
    }

    /**
     * Log in as the user defined in the Data Fixtures and except an
     * AllowedQueriesExceededException exception.
     *
     * There will be 2 queries, in the configuration the limit is 1,
     * an Exception will be thrown.
     */
    public function testAllowedQueriesExceededException(): void
    {
        $user = $this->loadTestFixtures();

        $this->assertInstanceOf(
            User::class,
            $user
        );

        $this->loginAs($user, 'secured_area');

        $this->client = static::makeClient();

        // One another query to load the second user.
        $path = '/user/2';

        $this->expectException(\Liip\FunctionalTestBundle\Exception\AllowedQueriesExceededException::class);

        $this->client->request('GET', $path);
        $this->assertStatusCode(200, $this->client);
    }

    /**
     * Expect an exception due to the QueryCount annotation.
     *
     * @QueryCount(0)
     *
     * There will be 1 query, in the annotation the limit is 0,
     * an Exception will be thrown.
     */
    public function testAnnotationAndException(): void
    {
        $this->client = static::makeClient();

        // One query to load the second user
        $path = '/user/1';

        $this->expectException(\Liip\FunctionalTestBundle\Exception\AllowedQueriesExceededException::class);

        $this->client->request('GET', $path);
        $this->assertStatusCode(200, $this->client);
    }
}
