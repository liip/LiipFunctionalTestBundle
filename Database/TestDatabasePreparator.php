<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Database;

use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TestDatabasePreparator
{
    private $container;

    /**
     * @var array
     */
    static private $cachedMetadatas = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $type
     * @param ObjectManager $om
     * @param int $purgeMode see Doctrine\Common\DataFixtures\Purger\ORMPurger
     * @return AbstractExecutor
     */
    private function getExecutor($type, ObjectManager $om, $purgeMode = null)
    {
        $executorClass = 'PHPCR' === $type && class_exists('Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor')
        ? 'Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor'
            : 'Doctrine\\Common\\DataFixtures\\Executor\\'.$type.'Executor';

        $purgerClass = 'Doctrine\\Common\\DataFixtures\\Purger\\'.$type.'Purger';

        if ('PHPCR' === $type) {
            $purger = new $purgerClass($om);
            $initManager = $this->container->has('doctrine_phpcr.initializer_manager')
                ? $this->container->get('doctrine_phpcr.initializer_manager')
                : null;

            return new $executorClass($om, $purger, $initManager);
        }

        if($type === 'ORM' && $om->getConnection()->getDriver() instanceof SqliteDriver) {
            return new $executorClass($om);
        }

        $purger = new $purgerClass();
        if (null !== $purgeMode) {
            $purger->setPurgeMode($purgeMode);
        }

        return new $executorClass($om, $purger);
    }

    public function getExecutorWithReferenceRepository($type, ObjectManager $om, $purgeMode = null)
    {
        $executor = $this->getExecutor($type, $om, $purgeMode);
        $referenceRepository = new ProxyReferenceRepository($om);
        $executor->setReferenceRepository($referenceRepository);

        return $executor;
    }

    public function getMetaDatas(ObjectManager $om, $omName)
    {
        if (!isset(self::$cachedMetadatas[$omName])) {
            self::$cachedMetadatas[$omName] = $om->getMetadataFactory()->getAllMetadata();
        }

        return self::$cachedMetadatas[$omName];
    }

    /**
     * Retrieve Doctrine DataFixtures loader.
     *
     * @param ContainerInterface $container
     * @param array $classNames
     *
     * @return \Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader
     */
    public function getFixtureLoader(ContainerInterface $container, array $classNames)
    {
        $loaderClass = class_exists('Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader')
        ? 'Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader'
            : (class_exists('Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader')
                ? 'Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader'
                : 'Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader');

        $loader = new $loaderClass($container);

        foreach ($classNames as $className) {
            $this->loadFixtureClass($loader, $className);
        }

        return $loader;
    }

    /**
     * Load a data fixture class.
     *
     * @param \Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader $loader
     * @param string $className
     */
    private function loadFixtureClass($loader, $className)
    {
        $fixture = new $className();

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