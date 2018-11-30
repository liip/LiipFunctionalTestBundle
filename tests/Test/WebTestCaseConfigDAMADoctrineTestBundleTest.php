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

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Liip\FunctionalTestBundle\Tests\AppConfigDAMADoctrineTestBundle\AppConfigDAMADoctrineTestBundle;

/**
 * Test MySQL database.
 *
 * The following tests require a connection to a MySQL database,
 * they are disabled by default (see phpunit.xml.dist).
 *
 * In order to run them, you have to set the MySQL connection
 * parameters in the Tests/AppConfigDAMADoctrineTestBundle/config.yml file and
 * add “--exclude-group ""” when running PHPUnit.
 *
 * Use Tests/AppConfigMysql/AppConfigDAMADoctrineTestBundle.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class WebTestCaseConfigDAMADoctrineTestBundleTest extends WebTestCaseConfigMysqlTest
{
    protected static function getKernelClass(): string
    {
        return AppConfigDAMADoctrineTestBundle::class;
    }

    public function setUp()
    {
        if (!class_exists(DAMADoctrineTestBundle::class)) {
            $this->markTestSkipped('Need dama/doctrine-test-bundle package.');
        }
    }
}
