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
use PHPUnit\Framework\Attributes\DataProvider;
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
        $this->assertStringContainsString('Environment: test', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Verbosity level: NORMAL', $this->commandTester->getDisplay());
        if ($this->commandTester instanceof CommandTester) {
            $this->assertFalse($this->commandTester->getInput()->isInteractive());
        }

        $this->assertIsBool($this->getDecorated());
        $this->assertTrue($this->getDecorated());

        // Run command and reuse kernel
        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test', [], true);

        $this->assertCommandResultType($this->commandTester);
        $this->assertSame(0, $this->getStatusCode($this->commandTester));

        $this->assertStringContainsString('Environment: test', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Verbosity level: NORMAL', $this->commandTester->getDisplay());
    }

    public function testRunCommandWithInputs(): void
    {
        $this->setInputs(['foo']);
        $this->assertSame(['foo'], $this->getInputs());

        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test:interactive');

        $this->assertNull($this->getInputs());
        if ($this->commandTester instanceof CommandTester) {
            $this->assertTrue($this->commandTester->getInput()->isInteractive());
        }
        $this->assertStringContainsString('Value of answer: foo', $this->commandTester->getDisplay());

        // Run command again
        $this->assertNull($this->getInputs());

        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test:interactive');

        $this->assertNull($this->getInputs());
        if ($this->commandTester instanceof CommandTester) {
            $this->assertFalse($this->commandTester->getInput()->isInteractive());
        }
        // The default value is shown
        $this->assertStringContainsString('Value of answer: AcmeDemoBundle', $this->commandTester->getDisplay());
    }

    /**
     * @dataProvider useEnvProvider
     */
    #[DataProvider('useEnvProvider')]
    public function testRunCommandWithoutOptionsAndNotReuseKernel(bool $useEnv): void
    {
        if ($useEnv) {
            static::$env = 'test';
        } else {
            $this->environment = 'test';
        }

        // Run command without options
        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');

        $this->assertCommandResultType($this->commandTester);
        $this->assertSame(0, $this->getStatusCode($this->commandTester));

        // Test default values
        $this->assertStringContainsString('Environment: test', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Verbosity level: NORMAL', $this->commandTester->getDisplay());

        $this->assertIsBool($this->getDecorated());
        $this->assertTrue($this->getDecorated());

        // Run command and reuse kernel
        if ($useEnv) {
            static::$env = 'prod';
        } else {
            $this->environment = 'prod';
        }

        self::ensureKernelShutdown();
        $this->getContainer();
        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test', [], true);

        $this->assertCommandResultType($this->commandTester);

        $this->assertStringContainsString('Environment: prod', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Verbosity level: NORMAL', $this->commandTester->getDisplay());
    }

    public static function useEnvProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    public function testRunCommandWithoutDecoration(): void
    {
        // Set `decorated` to false
        $this->isDecorated(false);

        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');

        $this->assertCommandResultType($this->commandTester);
        $this->assertSame(0, $this->getStatusCode($this->commandTester));

        $this->assertStringContainsString('Verbosity level: NORMAL', $this->commandTester->getDisplay());

        $this->assertIsBool($this->getDecorated());
        $this->assertFalse($this->getDecorated());
    }

    public function testRunCommandVerbosityQuiet(): void
    {
        $this->setVerbosityLevel('quiet');
        $this->assertSame(OutputInterface::VERBOSITY_QUIET, $this->getVerbosityLevel());

        $this->isDecorated(false);
        $this->assertIsBool($this->getDecorated());
        $this->assertFalse($this->getDecorated());

        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');

        $this->assertCommandResultType($this->commandTester);
        $this->assertSame(0, $this->getStatusCode($this->commandTester));

        $this->assertEmpty($this->commandTester->getDisplay());
        $this->assertStringNotContainsString('Verbosity level: NORMAL', $this->commandTester->getDisplay());
        $this->assertStringNotContainsString('Verbosity level: VERBOSE', $this->commandTester->getDisplay());
        $this->assertStringNotContainsString('Verbosity level: VERY_VERBOSE', $this->commandTester->getDisplay());
        $this->assertStringNotContainsString('Verbosity level: DEBUG', $this->commandTester->getDisplay());
    }

    public function testRunCommandVerbosityImplicitlyNormal(): void
    {
        // Run command without setting verbosity: default set is normal
        $this->assertSame(OutputInterface::VERBOSITY_NORMAL, $this->getVerbosityLevel());

        $this->isDecorated(false);
        $this->assertIsBool($this->getDecorated());
        $this->assertFalse($this->getDecorated());

        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');
        $this->assertSame(0, $this->getStatusCode($this->commandTester));

        $this->assertCommandResultType($this->commandTester);

        $this->assertStringContainsString('Verbosity level: NORMAL', $this->commandTester->getDisplay());
        $this->assertStringNotContainsString('Verbosity level: VERBOSE', $this->commandTester->getDisplay());
        $this->assertStringNotContainsString('Verbosity level: VERY_VERBOSE', $this->commandTester->getDisplay());
        $this->assertStringNotContainsString('Verbosity level: DEBUG', $this->commandTester->getDisplay());
    }

    public function testRunCommandVerbosityExplicitlyNormal(): void
    {
        $this->setVerbosityLevel('normal');
        $this->assertSame(OutputInterface::VERBOSITY_NORMAL, $this->getVerbosityLevel());

        $this->isDecorated(false);
        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');
        $this->assertSame(0, $this->getStatusCode($this->commandTester));

        $this->assertCommandResultType($this->commandTester);

        $this->assertStringContainsString('Verbosity level: NORMAL', $this->commandTester->getDisplay());
        $this->assertStringNotContainsString('Verbosity level: VERBOSE', $this->commandTester->getDisplay());
        $this->assertStringNotContainsString('Verbosity level: VERY_VERBOSE', $this->commandTester->getDisplay());
        $this->assertStringNotContainsString('Verbosity level: DEBUG', $this->commandTester->getDisplay());
    }

    public function testRunCommandVerbosityVerbose(): void
    {
        $this->setVerbosityLevel('verbose');
        $this->assertSame(OutputInterface::VERBOSITY_VERBOSE, $this->getVerbosityLevel());

        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');
        $this->assertSame(0, $this->getStatusCode($this->commandTester));

        $this->assertCommandResultType($this->commandTester);

        $this->assertStringContainsString('Verbosity level: NORMAL', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Verbosity level: VERBOSE', $this->commandTester->getDisplay());
        $this->assertStringNotContainsString('Verbosity level: VERY_VERBOSE', $this->commandTester->getDisplay());
        $this->assertStringNotContainsString('Verbosity level: DEBUG', $this->commandTester->getDisplay());
    }

    public function testRunCommandVerbosityVeryVerbose(): void
    {
        $this->setVerbosityLevel('very_verbose');
        $this->assertSame(OutputInterface::VERBOSITY_VERY_VERBOSE, $this->getVerbosityLevel());

        $this->isDecorated(false);
        $this->assertIsBool($this->getDecorated());
        $this->assertFalse($this->getDecorated());

        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');
        $this->assertSame(0, $this->getStatusCode($this->commandTester));

        $this->assertCommandResultType($this->commandTester);

        $this->assertStringContainsString('Verbosity level: NORMAL', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Verbosity level: VERBOSE', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Verbosity level: VERY_VERBOSE', $this->commandTester->getDisplay());
        $this->assertStringNotContainsString('Verbosity level: DEBUG', $this->commandTester->getDisplay());
    }

    public function testRunCommandVerbosityDebug(): void
    {
        $this->setVerbosityLevel('debug');
        $this->assertSame(OutputInterface::VERBOSITY_DEBUG, $this->getVerbosityLevel());

        $this->isDecorated(false);
        $this->assertIsBool($this->getDecorated());
        $this->assertFalse($this->getDecorated());

        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test');
        $this->assertSame(0, $this->getStatusCode($this->commandTester));

        $this->assertCommandResultType($this->commandTester);

        $this->assertStringContainsString('Verbosity level: NORMAL', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Verbosity level: VERBOSE', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Verbosity level: VERY_VERBOSE', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Verbosity level: DEBUG', $this->commandTester->getDisplay());
    }

    public function testRunCommandStatusCode(): void
    {
        $this->commandTester = $this->runCommand('liipfunctionaltestbundle:test-status-code');

        $this->assertCommandResultType($this->commandTester);

        $this->assertSame(10, $this->getStatusCode($this->commandTester));
    }

    public function testRunCommandVerbosityOutOfBound(): void
    {
        $this->setVerbosityLevel('foobar');

        $this->expectException(\OutOfBoundsException::class);

        $this->runCommand('liipfunctionaltestbundle:test');
    }

    private function assertCommandResultType($result): void
    {
        if (class_exists(\Symfony\Component\Console\Tester\ExecutionResult::class)) {
            $this->assertInstanceOf(\Symfony\Component\Console\Tester\ExecutionResult::class, $result);
        } else {
            $this->assertInstanceOf(CommandTester::class, $result);
        }
    }

    private function getStatusCode($result): int
    {
        return ($result instanceof CommandTester) ? $result->getStatusCode() : $result->statusCode;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->commandTester);
        restore_exception_handler();
    }
}
