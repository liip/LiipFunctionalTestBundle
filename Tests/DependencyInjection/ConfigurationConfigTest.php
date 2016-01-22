<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Tests\DependencyInjection;

/**
 * Use Tests/App/Config/AppConfigKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 */
class ConfigurationConfigTest extends ConfigurationTest
{
    /**
     * Use another Kernel to load another config file.
     */
    protected static function getKernelClass()
    {
        require_once __DIR__.'/../AppConfig/AppConfigKernel.php';

        return 'AppConfigKernel';
    }

    /**
     * Override values to be tested.
     */
    public function parametersProvider()
    {
        return array(
            array('cache_sqlite_db', true),
            array('command_verbosity', 'very_verbose'),
            array('command_decoration', false),
            array('query', array(
                'max_query_count' => 5,
            )),
            array('authentication', array(
                'username' => 'foobar',
                'password' => '12341234',
            )),
            array('html5validation', array(
                'url' => 'http://example.com/',
                'ignores' => array(
                    'ignore_1',
                    'ignore_2',
                ),
                'ignores_extract' => array(
                    'ignore_extract_1',
                    'ignore_extract_2',
                ),
            )),
        );
    }
}
