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
 * Use Tests/AppConfigParatest/AppConfigParatestKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 */
class ParatestCommandTest extends WebTestCase
{
    protected static function getKernelClass()
    {
        require_once __DIR__.'/../AppConfigParatest/AppConfigParatestKernel.php';

        return 'AppConfigParatestKernel';
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
            // Don't ignore the "paratest" group that is ignored by default.
            'options' => 'Tests/Test/WebTestCaseConfigParatestTest.php --group "paratest"',
        ));

        $this->assertContains('Running phpunit in 3 processes', $content);
        $this->assertNotContains('Error : Install paratest first', $content);
        $this->assertContains('Done...Running test.', $content);
        $this->assertContains('OK (2 tests, 4 assertions)', $content);
    }
}
