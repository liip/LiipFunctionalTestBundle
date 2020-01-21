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
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
        $loader->load(__DIR__.'/config.yml');

        $loader->load(function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'assets' => null,
            ]);
        });
    }

    public function getCacheDir()
    {
        return $this->getBaseDir().'cache';
    }

    public function getLogDir()
    {
        return $this->getBaseDir().'log';
    }

    protected function getBaseDir()
    {
        return sys_get_temp_dir().'/LiipFunctionalTestBundle/'.(new \ReflectionClass($this))->getShortName().'/var/';
    }

    public function getProjectDir()
    {
        return __DIR__;
    }
}
