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

namespace Liip\FunctionalTestBundle\Tests\Test;

use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\FunctionalTestBundle\Tests\AppConfig\AppConfigKernel;

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
    /** @var \Symfony\Bundle\FrameworkBundle\Client client */
    private $client = null;

    protected static function getKernelClass(): string
    {
        return AppConfigKernel::class;
    }

    /**
     * Log in as an user.
     */
    public function testIndexAuthenticationArray(): void
    {
        $this->loadFixtures([]);

        $this->client = static::makeClient([
            'username' => 'foobar',
            'password' => '12341234',
        ]);

        $path = '/';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

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
        $this->loadFixtures([]);

        $this->client = static::makeClient(true);

        $path = '/';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

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
        $fixtures = $this->loadFixtures([
            'Liip\FunctionalTestBundle\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        /** @var \Doctrine\Common\DataFixtures\ReferenceRepository $repository */
        $repository = $fixtures->getReferenceRepository();

        $loginAs = $this->loginAs($repository->getReference('user'),
            'secured_area');

        $this->assertInstanceOf(
            'Liip\FunctionalTestBundle\Test\WebTestCase',
            $loginAs
        );

        $this->client = static::makeClient();

        $path = '/';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

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
        $fixtures = $this->loadFixtures([
            'Liip\FunctionalTestBundle\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        /** @var \Doctrine\Common\DataFixtures\ReferenceRepository $repository */
        $repository = $fixtures->getReferenceRepository();

        // There will be one query to log in the first user.
        $this->loginAs($repository->getReference('user'),
            'secured_area');

        $this->client = static::makeClient();

        // One another query to load the second user.
        $path = '/user/2';

        $this->client->request('GET', $path);
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
        $this->loadFixtures([
            'Liip\FunctionalTestBundle\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        $this->client = static::makeClient();

        // One query to load the second user
        $path = '/user/1';

        $this->client->request('GET', $path);
    }

    /**
     * Load Data Fixtures with custom loader defined in configuration.
     */
    public function testLoadFixturesFilesWithCustomProvider(): void
    {
        // Load default Data Fixtures.
        $fixtures = $this->loadFixtureFiles([
            '@AcmeBundle/App/DataFixtures/ORM/user.yml',
        ]);

        $this->assertInternalType(
            'array',
            $fixtures
        );

        // 10 users are loaded
        $this->assertCount(
            10,
            $fixtures
        );

        /** @var \Liip\FunctionalTestBundle\Tests\App\Entity\User $user */
        $user = $fixtures['id1'];

        // The custom provider has not been used successfully.
        $this->assertStringStartsNotWith(
            'foo',
            $user->getName()
        );

        // Load Data Fixtures with custom loader defined in configuration.
        $fixtures = $this->loadFixtureFiles([
            '@AcmeBundle/App/DataFixtures/ORM/user_with_custom_provider.yml',
        ]);

        /** @var \Liip\FunctionalTestBundle\Tests\App\Entity\User $user */
        $user = $fixtures['id1'];

        // The custom provider "foo" has been loaded and used successfully.
        $this->assertSame(
            'fooa string',
            $user->getName()
        );
    }

    /**
     * Update a fixture file and check that the cache will be refreshed.
     */
    public function testBackupIsRefreshed(): void
    {
        // MD5 hash corresponding to these fixtures files.
        $md5 = '0ded9d8daaeaeca1056b18b9d0d433b2';

        $fixtures = [
            'Liip\FunctionalTestBundle\Tests\App\DataFixtures\ORM\LoadDependentUserData',
        ];

        $this->loadFixtures($fixtures);

        // Load data from database
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var \Liip\FunctionalTestBundle\Tests\App\Entity\User $user1 */
        $user1 = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy(['id' => 1]);

        // Store random data, in order to check it after reloading fixtures.
        $user1Salt = $user1->getSalt();

        $dependentFixtureFilePath = $this->getContainer()->get('kernel')->locateResource(
            '@AcmeBundle/App/DataFixtures/ORM/LoadUserData.php'
        );

        $dependentFixtureFilemtime = filemtime($dependentFixtureFilePath);

        $databaseFilePath = $this->getContainer()->getParameter('kernel.cache_dir').'/test_sqlite_'.$md5.'.db';

        if (!is_file($databaseFilePath)) {
            $this->markTestSkipped($databaseFilePath.' is not a file.');
        }

        $databaseFilemtime = filemtime($databaseFilePath);

        sleep(2);

        // Reload the fixtures.
        $this->loadFixtures($fixtures);

        // The mtime of the file has not changed.
        $this->assertSame(
            $dependentFixtureFilemtime,
            filemtime($dependentFixtureFilePath),
            'File modification time of the fixture has been updated.'
        );

        // The backup has not been updated.
        $this->assertSame(
            $databaseFilemtime,
            filemtime($databaseFilePath),
            'File modification time of the backup has been updated.'
        );

        $user1 = $em->getRepository('LiipFunctionalTestBundle:User')->findOneBy(['id' => 1]);

        // Check that random data has not been changed, to ensure that backup was created and loaded successfully.
        $this->assertSame($user1Salt, $user1->getSalt());

        sleep(2);

        // Update the filemtime of the fixture file used as a dependency.
        touch($dependentFixtureFilePath);

        $this->loadFixtures($fixtures);

        // The mtime of the fixture file has been updated.
        $this->assertGreaterThan(
            $dependentFixtureFilemtime,
            filemtime($dependentFixtureFilePath),
            'File modification time of the fixture has not been updated.'
        );

        // The backup has been refreshed: mtime is greater.
        $this->assertGreaterThan(
            $databaseFilemtime,
            filemtime($databaseFilePath),
            'File modification time of the backup has not been updated.'
        );

        $user1 = $em->getRepository('LiipFunctionalTestBundle:User')->findOneBy(['id' => 1]);

        // Check that random data has been changed, to ensure that backup was not used.
        $this->assertNotSame($user1Salt, $user1->getSalt());
    }
}
