<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * @see http://www.whitewashing.de/2012/02/25/symfony2_controller_testing.html
 */

require_once __DIR__.'/../App/AppKernel.php';

use Symfony\Component\Config\Loader\LoaderInterface;

class AppConfigMysqlKernel extends AppKernel
{
    /**
     * Load the config.yml from the current directory.
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        // Load the default file.
        parent::registerContainerConfiguration($loader);

        // Load the file with MySQL configuration
        $loader->load(__DIR__.'/config.yml');
    }
}
