<?php

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

/**
 * Tests that configuration has been loaded and users can be logged in.
 *
 * Use Tests/AppConfig/AppConfigKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
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

    protected static function getKernelClass()
    {
        require_once __DIR__.'/../AppConfig/AppConfigKernel.php';

        return 'AppConfigKernel';
    }

    /**
     * Log in as an user.
     */
    public function testIndexAuthenticationArray()
    {
        $this->loadFixtures(array());

        $this->client = static::makeClient(array(
            'username' => 'foobar',
            'password' => '12341234',
        ));

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
    public function testIndexAuthenticationTrue()
    {
        $this->loadFixtures(array());

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
    public function testIndexAuthenticationLoginAs()
    {
        $fixtures = $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\Tests\App\DataFixtures\ORM\LoadUserData',
        ));

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
    public function testAllowedQueriesExceededException()
    {
        $fixtures = $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\Tests\App\DataFixtures\ORM\LoadUserData',
        ));

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
    public function testAnnotationAndException()
    {
        $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\Tests\App\DataFixtures\ORM\LoadUserData',
        ));

        $this->client = static::makeClient();

        // One query to load the second user
        $path = '/user/1';

        $this->client->request('GET', $path);
    }

    /**
     * Test if there is a call-out to the service if defined.
     */
    public function testHautelookServiceUsage()
    {
        $hautelookLoaderMock = $this->getMockBuilder('\Hautelook\AliceBundle\Alice\DataFixtures\Loader')
            ->disableOriginalConstructor()
            ->setMethods(array('load'))
            ->getMock();
        $hautelookLoaderMock->expects(self::once())->method('load');

        $this->getContainer()->set('hautelook_alice.fixtures.loader', $hautelookLoaderMock);

        $this->loadFixtureFiles(array(
            '@LiipFunctionalTestBundle/Tests/App/DataFixtures/ORM/user.yml',
        ));
    }

    /**
     * Use hautelook.
     */
    public function testLoadFixturesFilesWithHautelook()
    {
        if (!class_exists('Hautelook\AliceBundle\Faker\Provider\ProviderChain')) {
            self::markTestSkipped('Please use hautelook/alice-bundle >=1.2');
        }

        $fakerProcessorChain = new \Hautelook\AliceBundle\Faker\Provider\ProviderChain(array());
        $aliceProcessorChain = new \Hautelook\AliceBundle\Alice\ProcessorChain(array());
        $fixtureLoader = new \Hautelook\AliceBundle\Alice\DataFixtures\Fixtures\Loader('en_US', $fakerProcessorChain);
        $loader = new \Hautelook\AliceBundle\Alice\DataFixtures\Loader($fixtureLoader, $aliceProcessorChain, true, 10);
        $this->getContainer()->set('hautelook_alice.fixtures.loader', $loader);

        $fixtures = $this->loadFixtureFiles(array(
            '@LiipFunctionalTestBundle/Tests/App/DataFixtures/ORM/user.yml',
        ));

        $this->assertInternalType(
            'array',
            $fixtures
        );

        // 10 users are loaded
        $this->assertCount(
            10,
            $fixtures
        );

        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findAll();

        $this->assertSame(
            10,
            count($users)
        );

        /** @var \Liip\FunctionalTestBundle\Tests\App\Entity\User $user */
        $user = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy(array(
                'id' => 1,
            ));

        $this->assertTrue(
            $user->getEnabled()
        );

        $user = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy(array(
                'id' => 10,
            ));

        $this->assertTrue(
            $user->getEnabled()
        );
    }

    /**
     * Load Data Fixtures with hautelook and custom loader defined in configuration.
     */
    public function testLoadFixturesFilesWithHautelookCustomProvider()
    {
        if (!class_exists('Hautelook\AliceBundle\Faker\Provider\ProviderChain')) {
            self::markTestSkipped('Please use hautelook/alice-bundle >=1.2');
        }

        // Load default Data Fixtures.
        $fixtures = $this->loadFixtureFiles(array(
            '@LiipFunctionalTestBundle/Tests/App/DataFixtures/ORM/user.yml',
        ));

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
        $fixtures = $this->loadFixtureFiles(array(
            '@LiipFunctionalTestBundle/Tests/App/DataFixtures/ORM/user_with_custom_provider.yml',
        ));

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
    public function testBackupIsRefreshed()
    {
        // This value is generated in loadFixtures().
        $md5 = '0ded9d8daaeaeca1056b18b9d0d433b2';

        $fixtures = array(
            'Liip\FunctionalTestBundle\Tests\App\DataFixtures\ORM\LoadDependentUserData',
        );

        $this->loadFixtures($fixtures);

        $dependentFixtureFilePath = $this->getContainer()->get('kernel')->locateResource(
            '@LiipFunctionalTestBundle/Tests/App/DataFixtures/ORM/LoadUserData.php'
        );

        $dependentFixtureFilemtime = filemtime($dependentFixtureFilePath);

        $databaseFilePath = $this->getContainer()->getParameter('kernel.cache_dir')
            .'/test_'.$md5.'.db';

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

        sleep(2);

        // Update the filemtime of the fixture file used as a dependency:
        // set a date in the future.
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
    }
}
