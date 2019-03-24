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
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\Acme\Tests\AppConfigMysql\AppConfigMysqlKernel;

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
 * @preserveGlobalState disabled
 * @IgnoreAnnotation("group")
 */
class WebTestCaseConfigMysqlTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return AppConfigMysqlKernel::class;
    }

    /**
     * Data fixtures.
     *
     * @group mysql
     */
    public function testLoadEmptyFixtures(): void
    {
        $fixtures = $this->loadFixtures([]);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );
    }

    /**
     * @group mysql
     */
    public function testLoadFixtures(): void
    {
        $fixtures = $this->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

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

        /** @var \Liip\Acme\Tests\App\Entity\User $user */
        $user = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy([
                'id' => 1,
            ]);

        $this->assertSame(
            'foo@bar.com',
            $user->getEmail()
        );

        $this->assertTrue(
            $user->getEnabled()
        );
    }

    /**
     * @group mysql
     */
    public function testAppendFixtures(): void
    {
        $this->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        $this->loadFixtures(
            ['Liip\Acme\Tests\App\DataFixtures\ORM\LoadSecondUserData'],
            true
        );

        // Load data from database
        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findAll();

        // Check that there are 3 users.
        $this->assertCount(
            3,
            $users
        );

        /** @var \Liip\Acme\Tests\App\Entity\User $user */
        $user1 = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy([
                'id' => 1,
            ]);

        $this->assertNotNull($user1);

        $this->assertSame(
            'foo@bar.com',
            $user1->getEmail()
        );

        $this->assertTrue(
            $user1->getEnabled()
        );

        /** @var \Liip\Acme\Tests\App\Entity\User $user */
        $user3 = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy([
                'id' => 3,
            ]);

        $this->assertNotNull($user3);

        $this->assertSame(
            'bar@foo.com',
            $user3->getEmail()
        );

        $this->assertTrue(
            $user3->getEnabled()
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
    public function testLoadFixturesAndExcludeFromPurge(): void
    {
        $fixtures = $this->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

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

        $this->setExcludedDoctrineTables(['liip_user']);
        $this->loadFixtures([], false, null, 'doctrine', ORMPurger::PURGE_MODE_TRUNCATE);

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
    public function testLoadFixturesAndPurge(): void
    {
        $fixtures = $this->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findAll();

        // Check that there are 2 users.
        $this->assertCount(
            2,
            $users
        );

        $this->loadFixtures([], false, null, 'doctrine', ORMPurger::PURGE_MODE_DELETE);

        // The purge worked: there is no user.
        $users = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findAll();

        $this->assertCount(
            0,
            $users
        );

        // Reload fixtures
        $this->loadFixtures([
            'Liip\Acme\Tests\App\DataFixtures\ORM\LoadUserData',
        ]);

        $users = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findAll();

        // Check that there are 2 users.
        $this->assertCount(
            2,
            $users
        );

        $this->loadFixtures([], false, null, 'doctrine', ORMPurger::PURGE_MODE_TRUNCATE);

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
    public function testLoadFixturesFiles(): void
    {
        $fixtures = $this->loadFixtureFiles([
            '@AcmeBundle/DataFixtures/ORM/user.yml',
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

        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findAll();

        $this->assertSame(
            10,
            count($users)
        );

        /** @var \Liip\Acme\Tests\App\Entity\User $user */
        $user = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy([
                'id' => 1,
            ]);

        $this->assertTrue(
            $user->getEnabled()
        );

        $user = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy([
                'id' => 10,
            ]);

        $this->assertTrue(
            $user->getEnabled()
        );
    }
}
