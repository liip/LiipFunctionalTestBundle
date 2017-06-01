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
 * Test PHPCR.
 *
 * Use Tests/AppConfigPhpcr/AppConfigMysqlKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 */
class WebTestCaseConfigPhpcrTest extends WebTestCase
{
    protected static function getKernelClass()
    {
        require_once __DIR__.'/../AppConfigPhpcr/AppConfigPhpcrKernel.php';

        return 'AppConfigPhpcrKernel';
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

        // Needed to define the PHPCR root, used in fixtures.
        $this->runCommand('doctrine:phpcr:repository:init');
    }

    public function testLoadFixturesPhPCr()
    {
        $fixtures = $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\Tests\AppConfigPhpcr\DataFixtures\PHPCR\LoadTaskData',
        ), null, 'doctrine_phpcr');

        $this->assertInstanceOf(
            'Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor',
            $fixtures
        );

        $repository = $fixtures->getReferenceRepository();

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\ProxyReferenceRepository',
            $repository
        );
    }
}
