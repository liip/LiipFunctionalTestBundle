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

namespace Liip\FunctionalTestBundle\Test;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

abstract class LegacyWebTestCase extends SymfonyWebTestCase
{
    /**
     * Runs a command and returns a CommandTester.
     */
    protected function runCommand(string $name, array $params = [], bool $reuseKernel = false): CommandTester
    {
        if (!$reuseKernel) {
            if (null !== static::$kernel) {
                static::ensureKernelShutdown();
            }

            $kernel = static::$kernel = static::createKernel(['environment' => $this->environment ?? static::$env]);
            $kernel->boot();
        } else {
            $kernel = $this->getContainer()->get('kernel');
        }

        $application = new Application($kernel);

        $options = [
            'interactive' => false,
            'decorated' => $this->getDecorated(),
            'verbosity' => $this->getVerbosityLevel(),
        ];

        $command = $application->find($name);
        $commandTester = new CommandTester($command);

        if (null !== $inputs = $this->getInputs()) {
            $commandTester->setInputs($inputs);
            $options['interactive'] = true;
            $this->inputs = null;
        }

        $commandTester->execute(
            array_merge(['command' => $command->getName()], $params),
            $options
        );

        return $commandTester;
    }
}
