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

namespace Liip\Acme\Tests\DependencyInjection;

use Liip\Acme\Tests\AppConfig\AppConfigKernel;

/**
 * Use Tests/AppConfig/AppConfigKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ConfigurationConfigTest extends ConfigurationTest
{
    /**
     * Use another Kernel to load another config file.
     */
    protected static function getKernelClass(): string
    {
        return AppConfigKernel::class;
    }

    /**
     * Override values to be tested.
     */
    public static function parametersProvider(): array
    {
        return [
            ['command_verbosity', 'very_verbose'],
            ['command_decoration', false],
            ['query.max_query_count', 0],
            ['authentication.username', 'foobar'],
            ['authentication.password', '12341234'],
        ];
    }
}
