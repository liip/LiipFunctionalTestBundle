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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Kernel;

class CommandTest extends WebTestCase
{
    private $display;

    /**
     * This method tests both the default setting of `runCommand()` and the kernel reusing, as, to reuse kernel,
     * it is needed a kernel is yet instantiated. So we test these two conditions here, to not repeat the code.
     */
    public function testRunCommandWithoutOptionsAndReuseKernel()
    {
        // Run command without options
        $this->display = $this->runCommand('liipfunctionaltestbundle:test');

        $this->assertInternalType('string', $this->display);

        // Test default values
        $this->assertContains('Environment: test', $this->display);
        $this->assertContains('Verbosity level: NORMAL', $this->display);

        $this->assertInternalType('boolean', $this->getDecorated());
        $this->assertTrue($this->getDecorated());

        // Run command and reuse kernel
        $this->display = $this->runCommand('liipfunctionaltestbundle:test', [], true);

        $this->assertContains('Environment: test', $this->display);
        $this->assertContains('Verbosity level: NORMAL', $this->display);
    }

    public function testRunCommandWithoutOptionsAndNotReuseKernel()
    {
        // Run command without options
        $this->display = $this->runCommand('liipfunctionaltestbundle:test');

        // Test default values
        $this->assertContains('Environment: test', $this->display);
        $this->assertContains('Verbosity level: NORMAL', $this->display);

        $this->assertInternalType('boolean', $this->getDecorated());
        $this->assertTrue($this->getDecorated());

        // Run command and not reuse kernel
        $this->environment = 'prod';
        $this->display = $this->runCommand('liipfunctionaltestbundle:test', [], true);

        $this->assertContains('Environment: prod', $this->display);
        $this->assertContains('Verbosity level: NORMAL', $this->display);
    }

    public function testRunCommandWithoutDecoration()
    {
        // Set `decorated` to false
        $this->isDecorated(false);

        $this->display = $this->runCommand('liipfunctionaltestbundle:test');

        $this->assertInternalType('string', $this->display);

        $this->assertContains('Verbosity level: NORMAL', $this->display);

        $this->assertInternalType('boolean', $this->getDecorated());
        $this->assertFalse($this->getDecorated());
    }

    public function testRunCommandVerbosityQuiet()
    {
        $this->setVerbosityLevel('quiet');
        $this->assertSame(OutputInterface::VERBOSITY_QUIET, $this->getVerbosityLevel());

        $this->isDecorated(false);
        $this->assertInternalType('boolean', $this->getDecorated());
        $this->assertFalse($this->getDecorated());

        $this->display = $this->runCommand('liipfunctionaltestbundle:test');

        $this->assertInternalType('string', $this->display);

        $this->assertEmpty($this->display);
        $this->assertNotContains('Verbosity level: NORMAL', $this->display);
        $this->assertNotContains('Verbosity level: VERBOSE', $this->display);
        $this->assertNotContains('Verbosity level: VERY_VERBOSE', $this->display);
        $this->assertNotContains('Verbosity level: DEBUG', $this->display);
    }

    public function testRunCommandVerbosityImplicitlyNormal()
    {
        // Run command without setting verbosity: default set is normal
        $this->assertSame(OutputInterface::VERBOSITY_NORMAL, $this->getVerbosityLevel());

        $this->isDecorated(false);
        $this->assertInternalType('boolean', $this->getDecorated());
        $this->assertFalse($this->getDecorated());

        $this->display = $this->runCommand('liipfunctionaltestbundle:test');

        $this->assertContains('Verbosity level: NORMAL', $this->display);

        $this->assertInternalType('string', $this->display);

        $this->assertNotContains('Verbosity level: VERBOSE', $this->display);

        $this->assertNotContains('Verbosity level: VERY_VERBOSE', $this->display);
        $this->assertNotContains('Verbosity level: DEBUG', $this->display);
    }

    public function testRunCommandVerbosityExplicitlyNormal()
    {
        $this->setVerbosityLevel('normal');
        $this->assertSame(OutputInterface::VERBOSITY_NORMAL, $this->getVerbosityLevel());

        $this->isDecorated(false);
        $this->display = $this->runCommand('liipfunctionaltestbundle:test');

        $this->assertContains('Verbosity level: NORMAL', $this->display);

        $this->assertInternalType('string', $this->display);

        // In this version of Symfony, NORMAL is practically equal to VERBOSE
        if ('203' === substr(Kernel::VERSION_ID, 0, 3)) {
            $this->assertContains('Verbosity level: VERBOSE', $this->display);
        } else {
            $this->assertNotContains('Verbosity level: VERBOSE', $this->display);
        }

        $this->assertNotContains('Verbosity level: VERY_VERBOSE', $this->display);
        $this->assertNotContains('Verbosity level: DEBUG', $this->display);
    }

    public function testRunCommandVerbosityVerbose()
    {
        $this->setVerbosityLevel('verbose');
        $this->assertSame(OutputInterface::VERBOSITY_VERBOSE, $this->getVerbosityLevel());

        $this->display = $this->runCommand('liipfunctionaltestbundle:test');

        $this->assertInternalType('string', $this->display);

        $this->assertContains('Verbosity level: NORMAL', $this->display);
        $this->assertContains('Verbosity level: VERBOSE', $this->display);
        $this->assertNotContains('Verbosity level: VERY_VERBOSE', $this->display);
        $this->assertNotContains('Verbosity level: DEBUG', $this->display);
    }

    public function testRunCommandVerbosityVeryVerbose()
    {
        $this->setVerbosityLevel('very_verbose');
        $this->assertSame(OutputInterface::VERBOSITY_VERY_VERBOSE, $this->getVerbosityLevel());

        $this->isDecorated(false);
        $this->assertInternalType('boolean', $this->getDecorated());
        $this->assertFalse($this->getDecorated());

        $this->display = $this->runCommand('liipfunctionaltestbundle:test');

        $this->assertInternalType('string', $this->display);

        $this->assertContains('Verbosity level: NORMAL', $this->display);
        $this->assertContains('Verbosity level: VERBOSE', $this->display);
        $this->assertContains('Verbosity level: VERY_VERBOSE', $this->display);
        $this->assertNotContains('Verbosity level: DEBUG', $this->display);
    }

    public function testRunCommandVerbosityDebug()
    {
        $this->setVerbosityLevel('debug');
        $this->assertSame(OutputInterface::VERBOSITY_DEBUG, $this->getVerbosityLevel());

        $this->isDecorated(false);
        $this->assertInternalType('boolean', $this->getDecorated());
        $this->assertFalse($this->getDecorated());

        $this->display = $this->runCommand('liipfunctionaltestbundle:test');

        $this->assertInternalType('string', $this->display);

        $this->assertContains('Verbosity level: NORMAL', $this->display);
        $this->assertContains('Verbosity level: VERBOSE', $this->display);
        $this->assertContains('Verbosity level: VERY_VERBOSE', $this->display);
        $this->assertContains('Verbosity level: DEBUG', $this->display);
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testRunCommandVerbosityOutOfBound()
    {
        $this->setVerbosityLevel('foobar');

        $this->runCommand('command:test');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->display);
    }
}
