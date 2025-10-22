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
use Symfony\Component\HttpKernel\Kernel;

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
     * Log in as a user.
     */
    public function testIndexClientWithCredentials(): void
    {
        $this->skipTestIfSymfonyHasVersion7();

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
        $this->skipTestIfSymfonyHasVersion7();

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
     *
     * loginAs() is deprecated, but we test if for Backward Compatibility.
     */
    public function testIndexAuthenticationLoginAs(): void
    {
        $this->skipTestIfSymfonyHasVersion7();

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
        $this->skipTestIfSymfonyHasVersion7();

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
     * There will be 2 queries:
     * - the user 1 is loaded from the database when logging in
     * - the user 2 is loaded by the controller
     *
     * In the configuration the limit is 1, an Exception will be thrown.
     */
    public function testAllowedQueriesExceededException(): void
    {
        $this->skipTestIfSymfonyHasVersion7();

        $user = $this->loadTestFixtures();

        $this->assertInstanceOf(
            User::class,
            $user
        );

        $this->client = static::makeClient();

        $this->loginClient($this->client, $user, 'secured_area');

        $path = '/user/2';

        $this->expectException(\Liip\FunctionalTestBundle\Exception\AllowedQueriesExceededException::class);

        $crawler = $this->client->request('GET', $path);

        // The following code is called if no exception has been thrown, it should help to understand why
        $this->assertStatusCode(200, $this->client);
        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );
        $this->assertSame(
            'Logged in as foo bar.',
            $crawler->filter('p#user')->text()
        );
        $this->assertSame(
            'Name: alice bob',
            $crawler->filter('div#content p:nth-child(1)')->text()
        );
        $this->assertSame(
            'Email: alice@example.com',
            $crawler->filter('div#content p:nth-child(2)')->text()
        );
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
        $this->skipTestIfSymfonyHasVersion7();

        $this->loadTestFixtures();

        $this->client = static::makeClient();

        // One query to load the second user
        $path = '/user/1';

        $this->expectException(\Liip\FunctionalTestBundle\Exception\AllowedQueriesExceededException::class);

        $this->client->request('GET', $path);
        $this->assertStatusCode(200, $this->client);
    }

    private function skipTestIfSymfonyHasVersion7(): void
    {
        if (Kernel::MAJOR_VERSION >= 7) {
            $this->markTestSkipped('The QueryCount is not compatible with Symfony 7+');
        }
    }
}
