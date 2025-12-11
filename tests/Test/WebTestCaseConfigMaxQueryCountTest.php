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

use Liip\Acme\Tests\App\Entity\User;
use Liip\Acme\Tests\AppConfigMaxQueryCount\AppConfigMaxQueryCountKernel;
use Liip\Acme\Tests\Traits\LiipAcmeFixturesTrait;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Tests that AllowedQueriesExceededException is thrown.
 *
 * Use Tests/AppConfigMaxQueryCount/AppConfigMaxQueryCountKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class WebTestCaseConfigMaxQueryCountTest extends WebTestCase
{
    use LiipAcmeFixturesTrait;

    /** @var \Symfony\Bundle\FrameworkBundle\Client client */
    private $client;

    protected function tearDown(): void
    {
        parent::tearDown();

        restore_exception_handler();
    }

    protected static function getKernelClass(): string
    {
        return AppConfigMaxQueryCountKernel::class;
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

    private function skipTestIfSymfonyHasVersion7(): void
    {
        if (Kernel::MAJOR_VERSION >= 7) {
            $this->markTestSkipped('The QueryCount is not compatible with Symfony 7+');
        }
    }
}
