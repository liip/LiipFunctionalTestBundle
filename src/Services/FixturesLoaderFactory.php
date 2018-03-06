<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Services;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\Loader;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class FixturesLoaderFactory
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Retrieve Doctrine DataFixtures loader.
     */
    public function getFixtureLoader(array $classNames): Loader
    {
        $loader = new ContainerAwareLoader($this->container);

        foreach ($classNames as $className) {
            $this->loadFixtureClass($loader, $className);
        }

        return $loader;
    }

    /**
     * Load a data fixture class.
     */
    protected function loadFixtureClass(Loader $loader, string $className): void
    {
        $fixture = null;

        if ($this->container->has($className)) {
            $fixture = $this->container->get($className);
        } else {
            $fixture = new $className();
        }

        if ($loader->hasFixture($fixture)) {
            unset($fixture);

            return;
        }

        $loader->addFixture($fixture);

        if ($fixture instanceof DependentFixtureInterface) {
            foreach ($fixture->getDependencies() as $dependency) {
                $this->loadFixtureClass($loader, $dependency);
            }
        }
    }
}
