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

namespace Liip\FunctionalTestBundle\Tests\AppConfigDAMADoctrineTestBundle;

use Liip\FunctionalTestBundle\Tests\AppConfigMysqlCacheDb\AppConfigMysqlKernelCacheDb;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppConfigDAMADoctrineTestBundle extends AppConfigMysqlKernelCacheDb
{
    public function registerBundles(): array
    {
        $bundles = parent::registerBundles();

        $bundles[] = new \DAMA\DoctrineTestBundle\DAMADoctrineTestBundle();

        return $bundles;
    }

    /**
     * Load the config.yml from the current directory.
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        // Load the default file.
        parent::registerContainerConfiguration($loader);

        // Load the file with DAMADoctrineTestBundle configuration
        $loader->load(__DIR__.'/config.yml');
    }
}
