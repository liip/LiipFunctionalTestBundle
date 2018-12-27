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

namespace Liip\FunctionalTestBundle\Tests\AppConfigMysqlCacheDb;

use Liip\FunctionalTestBundle\Tests\AppConfigMysql\AppConfigMysqlKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppConfigMysqlKernelCacheDb extends AppConfigMysqlKernel
{
    /**
     * Load the config.yml from the current directory.
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        // Load the default file.
        parent::registerContainerConfiguration($loader);

        // Load the file with MySQL configuration
        $loader->load(__DIR__.'/config.yml');
    }
}
