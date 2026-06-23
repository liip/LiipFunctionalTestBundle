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

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;
use Symfony\Component\Console\Tester\ExecutionResult;

abstract class ModernWebTestCase extends SymfonyWebTestCase
{
    /**
     * Runs a console command and returns the execution result.
     */
    public static function runCommand(
        string $name,
        array $input = [],
        mixed $interactiveInputs = [],
        ?bool $interactive = null,
        ?bool $decorated = null,
        ?int $verbosity = null,
        array $normalizers = []
    ): ExecutionResult {
        if (\is_bool($interactiveInputs)) {
            $reuseKernel = $interactiveInputs;
            if (!$reuseKernel) {
                static::ensureKernelShutdown();
            }
            $interactiveInputs = [];
        }

        // Retrieve properties from the active test instance if not explicitly provided
        if (null === $decorated && WebTestCase::$activeInstance) {
            $decorated = WebTestCase::$activeInstance->getDecorated();
        }
        if (null === $verbosity && WebTestCase::$activeInstance) {
            $verbosity = WebTestCase::$activeInstance->getVerbosityLevel();
        }
        if (empty($interactiveInputs) && WebTestCase::$activeInstance) {
            $interactiveInputs = WebTestCase::$activeInstance->getInputs() ?? [];
            WebTestCase::$activeInstance->inputs = null;
        }

        return parent::runCommand($name, $input, $interactiveInputs, $interactive, $decorated, $verbosity, $normalizers);
    }
}
