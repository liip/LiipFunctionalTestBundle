<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Tests\Command;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;

/**
 * Use Tests/AppConfig/AppConfigKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 */
class ParatestCommandTest extends WebTestCase
{
    protected static function getKernelClass()
    {
        require_once __DIR__.'/../AppConfig/AppConfigKernel.php';

        return 'AppConfigKernel';
    }

    /**
     * Test paratestCommand.
     */
    public function testParatest()
    {
        $kernel = $this->getContainer()->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $this->isDecorated(false);
        $content = $this->runCommand('paratest:run', array(
            // Only launch one test class, launching more classes may start an infinite loop.
            'options' => 'Tests/Test/WebTestCaseTest.php',
        ));

        $this->assertContains('Running phpunit in 3 processes with vendor/bin/phpunit', $content);
        $this->assertContains('Initial schema created', $content);
        $this->assertNotContains('Error : Install paratest first', $content);
        $this->assertContains('Done...Running test.', $content);

        // Some tests are skipped with Symfony 2.3, the numbers of tests and
        // assertions are 18 and 55 instead of 22 and 69 on Symfony 2.7+.
        $this->assertRegExp(
            '#OK \((22|18) tests, (69|55) assertions\)#',
            $content
        );
    }
}
