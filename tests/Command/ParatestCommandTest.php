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

namespace Liip\Acme\Tests\Command;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\Acme\Tests\AppConfig\AppConfigKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Use Tests/AppConfig/AppConfigKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ParatestCommandTest extends WebTestCase
{
    protected static function getKernelClass()
    {
        return AppConfigKernel::class;
    }

    /**
     * Test paratestCommand.
     */
    public function testParatest(): void
    {
        $kernel = $this->getContainer()->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $name = 'paratest:run';
        $command = $application->find($name);
        $commandTester = new CommandTester($command);

        // Only launch one test class (WebTestCaseTest.php), launching more classes may start an infinite loop.
        $commandTester->execute(
            ['options' => 'tests/Test/WebTestCaseTest.php']
        );
        $content = $commandTester->getDisplay();

        $this->assertStringContainsString('Running phpunit in 3 processes with vendor/bin/phpunit', $content);
        $this->assertStringNotContainsString('Error : Install paratest first', $content);

        $this->assertStringContainsString(
            'OK (17 tests, 45 assertions)',
            $content
        );
    }
}
