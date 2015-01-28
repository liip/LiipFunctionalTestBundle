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
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Common\Persistence\ManagerRegistry;

class TestDatabasePreparator
{
    private $container;

    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @var string storage Type, e.g. 'ORM' or 'PHPRC'
     */
    private $type;

    /**
     * @var string
     */
    private $omName;

    /**
     * @var array
     */
    static private $cachedMetadatas = [];

    public function __construct(Container $container, ManagerRegistry $registry, $omName = null)
    {
        $this->container = $container;
        $this->omName = $omName;
        $this->om = $registry->getManager($omName);
        $this->type = $registry->getName();
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getObjectManager()
    {
        return $this->om;
    }

    /**
     * @param string $type
     * @param ObjectManager $om
     * @param int $purgeMode see Doctrine\Common\DataFixtures\Purger\ORMPurger
     * @return AbstractExecutor
     */
    private function getExecutor($purgeMode = null)
    {
        $executorClass = 'PHPCR' === $this->type && class_exists('Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor')
        ? 'Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor'
            : 'Doctrine\\Common\\DataFixtures\\Executor\\'.$this->type.'Executor';

        $purgerClass = 'Doctrine\\Common\\DataFixtures\\Purger\\'.$this->type.'Purger';

        if ('PHPCR' === $this->type) {
            $purger = new $purgerClass($this->om);
            $initManager = $this->container->has('doctrine_phpcr.initializer_manager')
                ? $this->container->get('doctrine_phpcr.initializer_manager')
                : null;

            return new $executorClass($this->om, $purger, $initManager);
        }

        if($this->type === 'ORM' && $this->om->getConnection()->getDriver() instanceof SqliteDriver) {
            return new $executorClass($this->om);
        }

        $purger = new $purgerClass();
        if (null !== $purgeMode) {
            $purger->setPurgeMode($purgeMode);
        }

        return new $executorClass($this->om, $purger);
    }

    public function getExecutorWithReferenceRepository($purgeMode = null)
    {
        $executor = $this->getExecutor($purgeMode);
        $referenceRepository = new ProxyReferenceRepository($this->om);
        $executor->setReferenceRepository($referenceRepository);

        return $executor;
    }

    public function createSchema($name)
    {
        // TODO: handle case when using persistent connections. Fail loudly?
        $schemaTool = new SchemaTool($this->om);
        $schemaTool->dropDatabase($name);
        $metadatas = $this->getMetaDatas();
        if (!empty($metadatas)) {
            $schemaTool->createSchema($metadatas);
        }
    }

    public function deleteAllCaches()
    {
        $cacheDriver = $this->om->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }
    }


    public function getMetaDatas()
    {
        if (!isset(self::$cachedMetadatas[$this->omName])) {
            self::$cachedMetadatas[$this->omName] = $this->om->getMetadataFactory()->getAllMetadata();
        }

        return self::$cachedMetadatas[$this->omName];
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