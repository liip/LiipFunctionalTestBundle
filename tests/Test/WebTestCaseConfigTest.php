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
use Liip\Acme\Tests\Traits\LiipAcmeFixturesTrait;
use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\Acme\Tests\AppConfig\AppConfigKernel;

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

    protected static function getKernelClass(): string
    {
        return AppConfigKernel::class;
    }

    /**
     * Log in as an user.
     */
    public function testIndexAuthenticationArray(): void
    {
        $client = $this->makeClient([
            'username' => 'foobar',
            'password' => '12341234',
        ]);

        $path = '/';

        $crawler = $client->request('GET', $path);

        $this->assertStatusCode(200, $client);

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

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
    public function testIndexAuthenticationTrue(): void
    {
        $client = static::makeClient(true);

        $path = '/';

        $crawler = $client->request('GET', $path);

        $this->assertStatusCode(200, $client);

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

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
        $this->schemaUpdate();
        $user = $this->loadTestFixtures();

        $loginAs = $this->loginAs($user, 'secured_area');

        $this->assertInstanceOf(
            'Liip\FunctionalTestBundle\Test\WebTestCase',
            $loginAs
        );

        $client = static::makeClient();

        $path = '/';

        $crawler = $client->request('GET', $path);

        $this->assertStatusCode(200, $client);

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

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
     *
     * @expectedException \Liip\FunctionalTestBundle\Exception\AllowedQueriesExceededException
     */
    public function testAllowedQueriesExceededException(): void
    {
        $this->schemaUpdate();
        $user = $this->loadTestFixtures();

        $this->loginAs($user, 'secured_area');

        $client = static::makeClient();

        // One another query to load the second user.
        $path = '/user/2';

        $client->request('GET', $path);
    }

    /**
     * Expect an exception due to the QueryCount annotation.
     *
     * @QueryCount(0)
     *
     * There will be 1 query, in the annotation the limit is 0,
     * an Exception will be thrown.
     *
     * @expectedException \Liip\FunctionalTestBundle\Exception\AllowedQueriesExceededException
     */
    public function testAnnotationAndException(): void
    {
        $client = static::makeClient();

        // One query to load the second user
        $path = '/user/1';

        $client->request('GET', $path);
    }
}
