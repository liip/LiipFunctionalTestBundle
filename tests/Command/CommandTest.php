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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class CommandTest extends WebTestCase
{
    private $commandTester;

    /**
     * This method tests both the default setting of `runCommand()` and the kernel reusing, as, to reuse kernel,
     * it is needed a kernel is yet instantiated. So we test these two conditions here, to not repeat the code.
     */
    public function testRunCommandWithoutOptionsAndReuseKernel(): void
    {
        // Run command without options
        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');

        // Test default values
        $this->assertContains('Environment: test', $this->commandTester->getDisplay());
        $this->assertContains('Verbosity level: NORMAL', $this->commandTester->getDisplay());
        $this->assertFalse($this->commandTester->getInput()->isInteractive());

        $this->assertInternalType('boolean', $this->getDecorated());
        $this->assertTrue($this->getDecorated());

        // Run command and reuse kernel
        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test', [], true);

        $this->assertInstanceOf(CommandTester::class, $this->commandTester);
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $this->assertContains('Environment: test', $this->commandTester->getDisplay());
        $this->assertContains('Verbosity level: NORMAL', $this->commandTester->getDisplay());
    }

    public function testRunCommandWithInputs(): void
    {
        $this->setInputs(['foo']);
        $this->assertSame(['foo'], $this->getInputs());

        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test:interactive');

        $this->assertNull($this->getInputs());
        $this->assertTrue($this->commandTester->getInput()->isInteractive());
        $this->assertContains('Value of answer: foo', $this->commandTester->getDisplay());

        // Run command again
        $this->assertNull($this->getInputs());

        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test:interactive');

        $this->assertNull($this->getInputs());
        $this->assertFalse($this->commandTester->getInput()->isInteractive());
        // The default value is shown
        $this->assertContains('Value of answer: AcmeDemoBundle', $this->commandTester->getDisplay());
    }

    public function testRunCommandWithoutOptionsAndNotReuseKernel(): void
    {
        // Run command without options
        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');

        $this->assertInstanceOf(CommandTester::class, $this->commandTester);
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        // Test default values
        $this->assertContains('Environment: test', $this->commandTester->getDisplay());
        $this->assertContains('Verbosity level: NORMAL', $this->commandTester->getDisplay());

        $this->assertInternalType('boolean', $this->getDecorated());
        $this->assertTrue($this->getDecorated());

        // Run command and not reuse kernel
        $this->environment = 'prod';
        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test', [], true);

        $this->assertInstanceOf(CommandTester::class, $this->commandTester);

        $this->assertContains('Environment: prod', $this->commandTester->getDisplay());
        $this->assertContains('Verbosity level: NORMAL', $this->commandTester->getDisplay());
    }

    public function testRunCommandWithoutDecoration(): void
    {
        // Set `decorated` to false
        $this->isDecorated(false);

        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');

        $this->assertInstanceOf(CommandTester::class, $this->commandTester);
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $this->assertContains('Verbosity level: NORMAL', $this->commandTester->getDisplay());

        $this->assertInternalType('boolean', $this->getDecorated());
        $this->assertFalse($this->getDecorated());
    }

    public function testRunCommandVerbosityQuiet(): void
    {
        $this->setVerbosityLevel('quiet');
        $this->assertSame(OutputInterface::VERBOSITY_QUIET, $this->getVerbosityLevel());

        $this->isDecorated(false);
        $this->assertInternalType('boolean', $this->getDecorated());
        $this->assertFalse($this->getDecorated());

        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');

        $this->assertInstanceOf(CommandTester::class, $this->commandTester);
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $this->assertEmpty($this->commandTester->getDisplay());
        $this->assertNotContains('Verbosity level: NORMAL', $this->commandTester->getDisplay());
        $this->assertNotContains('Verbosity level: VERBOSE', $this->commandTester->getDisplay());
        $this->assertNotContains('Verbosity level: VERY_VERBOSE', $this->commandTester->getDisplay());
        $this->assertNotContains('Verbosity level: DEBUG', $this->commandTester->getDisplay());
    }

    public function testRunCommandVerbosityImplicitlyNormal(): void
    {
        // Run command without setting verbosity: default set is normal
        $this->assertSame(OutputInterface::VERBOSITY_NORMAL, $this->getVerbosityLevel());

        $this->isDecorated(false);
        $this->assertInternalType('boolean', $this->getDecorated());
        $this->assertFalse($this->getDecorated());

        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $this->assertInstanceOf(CommandTester::class, $this->commandTester);

        $this->assertContains('Verbosity level: NORMAL', $this->commandTester->getDisplay());
        $this->assertNotContains('Verbosity level: VERBOSE', $this->commandTester->getDisplay());
        $this->assertNotContains('Verbosity level: VERY_VERBOSE', $this->commandTester->getDisplay());
        $this->assertNotContains('Verbosity level: DEBUG', $this->commandTester->getDisplay());
    }

    public function testRunCommandVerbosityExplicitlyNormal(): void
    {
        $this->setVerbosityLevel('normal');
        $this->assertSame(OutputInterface::VERBOSITY_NORMAL, $this->getVerbosityLevel());

        $this->isDecorated(false);
        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $this->assertInstanceOf(CommandTester::class, $this->commandTester);

        $this->assertContains('Verbosity level: NORMAL', $this->commandTester->getDisplay());
        $this->assertNotContains('Verbosity level: VERBOSE', $this->commandTester->getDisplay());
        $this->assertNotContains('Verbosity level: VERY_VERBOSE', $this->commandTester->getDisplay());
        $this->assertNotContains('Verbosity level: DEBUG', $this->commandTester->getDisplay());
    }

    public function testRunCommandVerbosityVerbose(): void
    {
        $this->setVerbosityLevel('verbose');
        $this->assertSame(OutputInterface::VERBOSITY_VERBOSE, $this->getVerbosityLevel());

        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $this->assertInstanceOf(CommandTester::class, $this->commandTester);

        $this->assertContains('Verbosity level: NORMAL', $this->commandTester->getDisplay());
        $this->assertContains('Verbosity level: VERBOSE', $this->commandTester->getDisplay());
        $this->assertNotContains('Verbosity level: VERY_VERBOSE', $this->commandTester->getDisplay());
        $this->assertNotContains('Verbosity level: DEBUG', $this->commandTester->getDisplay());
    }

    public function testRunCommandVerbosityVeryVerbose(): void
    {
        $this->setVerbosityLevel('very_verbose');
        $this->assertSame(OutputInterface::VERBOSITY_VERY_VERBOSE, $this->getVerbosityLevel());

        $this->isDecorated(false);
        $this->assertInternalType('boolean', $this->getDecorated());
        $this->assertFalse($this->getDecorated());

        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $this->assertInstanceOf(CommandTester::class, $this->commandTester);

        $this->assertContains('Verbosity level: NORMAL', $this->commandTester->getDisplay());
        $this->assertContains('Verbosity level: VERBOSE', $this->commandTester->getDisplay());
        $this->assertContains('Verbosity level: VERY_VERBOSE', $this->commandTester->getDisplay());
        $this->assertNotContains('Verbosity level: DEBUG', $this->commandTester->getDisplay());
    }

    public function testRunCommandVerbosityDebug(): void
    {
        $this->setVerbosityLevel('debug');
        $this->assertSame(OutputInterface::VERBOSITY_DEBUG, $this->getVerbosityLevel());

        $this->isDecorated(false);
        $this->assertInternalType('boolean', $this->getDecorated());
        $this->assertFalse($this->getDecorated());

        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $this->assertInstanceOf(CommandTester::class, $this->commandTester);

        $this->assertContains('Verbosity level: NORMAL', $this->commandTester->getDisplay());
        $this->assertContains('Verbosity level: VERBOSE', $this->commandTester->getDisplay());
        $this->assertContains('Verbosity level: VERY_VERBOSE', $this->commandTester->getDisplay());
        $this->assertContains('Verbosity level: DEBUG', $this->commandTester->getDisplay());
    }

    public function testRunCommandStatusCode(): void
    {
        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test-status-code');

        $this->assertInstanceOf(CommandTester::class, $this->commandTester);

        $this->assertEquals(10, $this->commandTester->getStatusCode());
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testRunCommandVerbosityOutOfBound(): void
    {
        $this->setVerbosityLevel('foobar');

        $this->runCommand('liipfunctionaltestbundle:test');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->commandTester);
    }
}
