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

use Liip\FunctionalTestBundle\Test\WebTestCase;

/**
 * Tests that paratest works.
 *
 * Use Tests/AppConfig/AppConfigParatestKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * These tests must be launched from paratest, they are disabled by default
 * and launched by Tests/Command/ParatestCommandTest.php.
 *
 * @runTestsInSeparateProcesses
 */
class WebTestCaseConfigParatestTest extends WebTestCase
{
    protected static function getKernelClass()
    {
        require_once __DIR__.'/../AppConfigParatest/AppConfigParatestKernel.php';

        return 'AppConfigParatestKernel';
    }

    /**
     * @group paratest
     */
    public function test1()
    {
        $this->loadFixtures(array());

        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findAll();

        $this->assertSame(
            0,
            count($users)
        );

        $this->assertFileExists(
            $this->getContainer()->getParameter('kernel.cache_dir').'/dbTest0.db'
        );
    }

    /**
     * @group paratest
     */
    public function test2()
    {
        $this->loadFixtureFiles(array(
            '@LiipFunctionalTestBundle/Tests/App/DataFixtures/ORM/user.yml',
        ));

        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findAll();

        $this->assertSame(
            10,
            count($users)
        );

        $this->assertFileExists(
            $this->getContainer()->getParameter('kernel.cache_dir').'/dbTest0.db'
        );
    }
}
