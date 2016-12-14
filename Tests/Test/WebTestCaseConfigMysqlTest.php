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

use Doctrine\ORM\Tools\SchemaTool;
use Liip\FunctionalTestBundle\Test\WebTestCase;

/**
 * Test MySQL database.
 *
 * The following tests require a connection to a MySQL database,
 * they are disabled by default (see phpunit.xml.dist).
 *
 * In order to run them, you have to set the MySQL connection
 * parameters in the Tests/AppConfigMysql/config.yml file and
 * add “--exclude-group ""” when running PHPUnit.
 *
 * Use Tests/AppConfigMysql/AppConfigMysqlKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 */
class WebTestCaseConfigMysqlTest extends WebTestCase
{
    protected static function getKernelClass()
    {
        require_once __DIR__.'/../AppConfigMysql/AppConfigMysqlKernel.php';

        return 'AppConfigMysqlKernel';
    }

    public function setUp()
    {
        // https://github.com/liip/LiipFunctionalTestBundle#non-sqlite
        $em = $this->getContainer()->get('doctrine')->getManager();
        if (!isset($metadatas)) {
            $metadatas = $em->getMetadataFactory()->getAllMetadata();
        }
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        if (!empty($metadatas)) {
            $schemaTool->createSchema($metadatas);
        }
    }

    /**
     * Data fixtures.
     *
     * @group mysql
     */
    public function testLoadEmptyFixtures()
    {
        $fixtures = $this->loadFixtures(array());

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );
    }

    /**
     * @group mysql
     */
    public function testLoadFixtures()
    {
        $fixtures = $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\Tests\App\DataFixtures\ORM\LoadUserData',
        ));

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $repository = $fixtures->getReferenceRepository();

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $user1 = $repository->getReference('user');

        $this->assertSame(1, $user1->getId());
        $this->assertSame('foo bar', $user1->getName());
        $this->assertSame('foo@bar.com', $user1->getEmail());
        $this->assertTrue($user1->getEnabled());

        // Load data from database
        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        /** @var \Liip\FunctionalTestBundle\Tests\App\Entity\User $user */
        $user = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy(array(
                'id' => 1,
            ));

        $this->assertSame(
            'foo@bar.com',
            $user->getEmail()
        );

        $this->assertTrue(
            $user->getEnabled()
        );
    }

    /**
     * Data fixtures and purge.
     *
     * Purge modes are defined in
     * Doctrine\Common\DataFixtures\Purger\ORMPurger.
     *
     * @group mysql
     */
    public function testLoadFixturesAndExcludeFromPurge()
    {
        $fixtures = $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\Tests\App\DataFixtures\ORM\LoadUserData',
        ));

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        // Check that there are 2 users.
        $this->assertSame(
            2,
            count($em->getRepository('LiipFunctionalTestBundle:User')
                ->findAll())
        );

        $this->setExcludedDoctrineTables(array('liip_user'));
        $this->loadFixtures(array(), null, 'doctrine', 2);

        // The exclusion from purge worked, the user table is still alive and well.
        $this->assertSame(
            2,
            count($em->getRepository('LiipFunctionalTestBundle:User')
                ->findAll())
        );
    }

    /**
     * Data fixtures and purge.
     *
     * Purge modes are defined in
     * Doctrine\Common\DataFixtures\Purger\ORMPurger.
     *
     * @group mysql
     */
    public function testLoadFixturesAndPurge()
    {
        $fixtures = $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\Tests\App\DataFixtures\ORM\LoadUserData',
        ));

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        // Check that there are 2 users.
        $this->assertSame(
            2,
            count($em->getRepository('LiipFunctionalTestBundle:User')
                ->findAll())
        );

        // 1 → ORMPurger::PURGE_MODE_DELETE
        $this->loadFixtures(array(), null, 'doctrine', 1);

        // The purge worked: there is no user.
        $this->assertSame(
            0,
            count($em->getRepository('LiipFunctionalTestBundle:User')
                ->findAll())
        );

        // Reload fixtures
        $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\Tests\App\DataFixtures\ORM\LoadUserData',
        ));

        // Check that there are 2 users.
        $this->assertSame(
            2,
            count($em->getRepository('LiipFunctionalTestBundle:User')
                ->findAll())
        );

        // 2 → ORMPurger::PURGE_MODE_TRUNCATE
        $this->loadFixtures(array(), null, 'doctrine', 2);

        // The purge worked: there is no user.
        $this->assertSame(
            0,
            count($em->getRepository('LiipFunctionalTestBundle:User')
                ->findAll())
        );
    }

    /**
     * Use nelmio/alice.
     *
     * @group mysql
     */
    public function testLoadFixturesFiles()
    {
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
}
