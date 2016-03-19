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

/**
 * Use Tests/AppConfig/AppConfigKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 */
class CommandConfigTest extends WebTestCase
{
    private $display;

    protected static function getKernelClass()
    {
        require_once __DIR__.'/../AppConfig/AppConfigKernel.php';

        return 'AppConfigKernel';
    }

    public function testRunCommand()
    {
        // Run command without options
        $this->display = $this->runCommand('liipfunctionaltestbundle:test');

        $this->assertInternalType('string', $this->display);

        // Test values from configuration
        $this->assertContains('Environment: test', $this->display);
        $this->assertContains('Verbosity level: VERY_VERBOSE', $this->display);

        $this->assertInternalType('boolean', $this->getDecorated());
        $this->assertFalse($this->getDecorated());
    }
}
