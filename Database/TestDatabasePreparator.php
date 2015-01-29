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
use Doctrine\DBAL\Driver\PDOSqlite\Driver as SqliteDriver;

/**
 * This class is experimental!
 */
class TestDatabasePreparator
{
    const POST_FIXTURE_RESTORE = 'postFixtureRestore';
    const POST_FIXTURE_SETUP = 'postFixtureSetup';

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
     * Set the database to the provided fixtures.
     *
     * Drops the current database and then loads fixtures using the specified
     * classes. The parameter is a list of fully qualified class names of
     * classes that implement Doctrine\Common\DataFixtures\FixtureInterface
     * so that they can be loaded by the DataFixtures Loader::addFixture
     *
     * When using SQLite this method will automatically make a copy of the
     * loaded schema and fixtures which will be restored automatically in
     * case the same fixture classes are to be loaded again. Caveat: changes
     * to references and/or identities may go undetected.
     *
     * Depends on the doctrine data-fixtures library being available in the
     * class path.
     *
     * @param array $classNames List of fully qualified class names of fixtures to load
     * @param int $purgeMode Sets the ORM purge mode
     * @param callable $callback
     *
     * @return null|Doctrine\Common\DataFixtures\Executor\AbstractExecutor
     */
    public function loadFixtures(array $classNames, $purgeMode = null, $callback = null)
    {
        $this->deleteAllCaches();

        if ($this->isSQLite()) {
            $dbCache = new TestDatabaseCache($this->container);
            if ($dbCache->isCacheEnabled()) {
                $executor = $dbCache->getCachedExecutor($this, $classNames);
                if (!is_null($executor)) {
                    if (is_callable($callback)) {
                        call_user_func($callback, self::POST_FIXTURE_RESTORE);
                    }

                    return $executor;
                }
            }

            $this->createSchema($dbCache->getSQLiteName($this->om->getConnection()->getParams()));
            if (is_callable($callback)) {
                call_user_func($callback, self::POST_FIXTURE_SETUP);
            }

            $executor = $this->getExecutorWithReferenceRepository();
        }

        if (empty($executor)) {
            $executor = $this->getExecutorWithReferenceRepository($purgeMode);
            $executor->purge();
        }

        $loader = $this->getFixtureLoader($classNames);

        $executor->execute($loader->getFixtures(), true);

        if (isset($dbCache) && $dbCache->isCacheEnabled()) {
            $dbCache->storeToCache($executor, $this, $classNames);
        }

        return $executor;
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

        if ($this->isSQLite()) {
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

    private function isSQLite()
    {
        return $this->type === 'ORM' && $this->om->getConnection()->getDriver() instanceof SqliteDriver;
    }

    private function createSchema($name)
    {
        // TODO: handle case when using persistent connections. Fail loudly?
        $schemaTool = new SchemaTool($this->om);
        $schemaTool->dropDatabase($name);
        $metadatas = $this->getMetaDatas();
        if (!empty($metadatas)) {
            $schemaTool->createSchema($metadatas);
        }
    }

    private function deleteAllCaches()
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
     * @param array $classNames
     *
     * @return \Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader
     */
    private function getFixtureLoader(array $classNames)
    {
        $loaderClass = class_exists('Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader')
        ? 'Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader'
            : (class_exists('Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader')
                ? 'Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader'
                : 'Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader');

        $loader = new $loaderClass($this->container);

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
