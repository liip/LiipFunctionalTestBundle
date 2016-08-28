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
use Symfony\Component\HttpKernel\Kernel;

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

        // Symfony 2.7+.
        if (interface_exists('Symfony\Component\Validator\Validator\ValidatorInterface')) {
            $this->assertContains(
                'OK (22 tests, 69 assertions)',
                $content
            );
        }
        // Travis CI: --prefer-lowest.
        // Symfony 2.3.27.
        elseif ('20327' === Kernel::VERSION_ID) {
            self::markTestSkipped('Ignore Symfony 2.3.27');
        }
        // Symfony 2.3.*.
        // Some tests will be skipped.
        else {
            $this->assertContains(
                'OK (18 tests, 55 assertions)',
                $content
            );
        }
    }
}
