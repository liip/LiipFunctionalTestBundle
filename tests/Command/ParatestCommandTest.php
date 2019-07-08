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

        $this->isDecorated(false);
        $content = $this->runCommand('paratest:run', [
            // Only launch one test class, launching more classes may start an infinite loop.
            'options' => 'Tests/Test/WebTestCaseTest.php',
        ]);

        $this->assertStringContainsString('Running phpunit in 3 processes with vendor/bin/phpunit', $content);
        $this->assertStringContainsString('Initial schema created', $content);
        $this->assertStringNotContainsString('Error : Install paratest first', $content);
        $this->assertStringContainsString('Done...Running test.', $content);

        $this->assertStringContainsString(
            'OK (22 tests, 69 assertions)',
            $content
        );
    }
}
