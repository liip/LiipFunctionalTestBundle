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

namespace Liip\Acme\Tests\App;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles(): array
    {
        $bundles = [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Liip\FunctionalTestBundle\LiipFunctionalTestBundle(),
            new \Liip\Acme\Tests\App\AcmeBundle(),
        ];

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        if (phpversion() >= '8.1') {
            $loader->load(__DIR__.'/config_php8.yml');
        } else {
            $loader->load(__DIR__.'/config.yml');
        }

        if (Kernel::MAJOR_VERSION >= 5) {
            $loader->load(__DIR__.'/security_5.yml');
            $loader->load(__DIR__.'/session_5.yml');
        } else {
            $loader->load(__DIR__.'/security_4.yml');
            $loader->load(__DIR__.'/session_4.yml');
        }
    }

    public function getCacheDir(): string
    {
        return $this->getBaseDir().'cache';
    }

    public function getLogDir(): string
    {
        return $this->getBaseDir().'log';
    }

    protected function getBaseDir(): string
    {
        return sys_get_temp_dir().'/LiipFunctionalTestBundle/'.(new \ReflectionClass($this))->getShortName().'/var/';
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }
}
