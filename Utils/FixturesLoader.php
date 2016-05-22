<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOSqlite\Driver as SqliteDriver;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Nelmio\Alice\Fixtures;

class FixturesLoader
{
    /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
    private $container;

    /**
     * @var array
     */
    private static $cachedMetadatas = array();

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * This function finds the time when the data blocks of a class definition
     * file were being written to, that is, the time when the content of the
     * file was changed.
     *
     * @param string $class The fully qualified class name of the fixture class to
     *                      check modification date on.
     *
     * @return \DateTime|null
     */
    protected function getFixtureLastModified($class)
    {
        $lastModifiedDateTime = null;

        $reflClass = new \ReflectionClass($class);
        $classFileName = $reflClass->getFileName();

        if (file_exists($classFileName)) {
            $lastModifiedDateTime = new \DateTime();
            $lastModifiedDateTime->setTimestamp(filemtime($classFileName));
        }

        return $lastModifiedDateTime;
    }

    /**
     * Determine if the Fixtures that define a database backup have been
     * modified since the backup was made.
     *
     * @param array  $classNames The fixture classnames to check
     * @param string $backup     The fixture backup SQLite database file path
     *
     * @return bool TRUE if the backup was made since the modifications to the
     *              fixtures; FALSE otherwise
     */
    protected function isBackupUpToDate(array $classNames, $backup)
    {
        $backupLastModifiedDateTime = new \DateTime();
        $backupLastModifiedDateTime->setTimestamp(filemtime($backup));

        /** @var \Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader $loader */
        $loader = $this->getFixtureLoader($this->container, $classNames);

        // Use loader in order to fetch all the dependencies fixtures.
        foreach ($loader->getFixtures() as $className) {
            $fixtureLastModifiedDateTime = $this->getFixtureLastModified($className);
            if ($backupLastModifiedDateTime < $fixtureLastModifiedDateTime) {
                return false;
            }
        }

        return true;
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
     * @param array  $classNames   List of fully qualified class names of fixtures to load
     * @param string $omName       The name of object manager to use
     * @param string $registryName The service id of manager registry to use
     * @param int    $purgeMode    Sets the ORM purge mode
     *
     * @return null|AbstractExecutor
     */
    public function loadFixtures(array $classNames, $omName = null, $registryName = 'doctrine', $purgeMode = null)
    {
        $container = $this->container;
        /** @var ManagerRegistry $registry */
        $registry = $container->get($registryName);
        /** @var ObjectManager $om */
        $om = $registry->getManager($omName);
        $type = $registry->getName();

        $executorClass = 'PHPCR' === $type && class_exists('Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor')
            ? 'Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor'
            : 'Doctrine\\Common\\DataFixtures\\Executor\\'.$type.'Executor';
        $referenceRepository = new ProxyReferenceRepository($om);
        $cacheDriver = $om->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }

        if ('ORM' === $type) {
            $connection = $om->getConnection();
            if ($connection->getDriver() instanceof SqliteDriver) {
                $params = $connection->getParams();
                if (isset($params['master'])) {
                    $params = $params['master'];
                }

                $name = isset($params['path']) ? $params['path'] : (isset($params['dbname']) ? $params['dbname'] : false);
                if (!$name) {
                    throw new \InvalidArgumentException("Connection does not contain a 'path' or 'dbname' parameter and cannot be dropped.");
                }

                if (!isset(self::$cachedMetadatas[$omName])) {
                    self::$cachedMetadatas[$omName] = $om->getMetadataFactory()->getAllMetadata();
                    usort(self::$cachedMetadatas[$omName], function ($a, $b) { return strcmp($a->name, $b->name); });
                }
                $metadatas = self::$cachedMetadatas[$omName];

                if ($container->getParameter('liip_functional_test.cache_sqlite_db')) {
                    $backup = $container->getParameter('kernel.cache_dir').'/test_'.md5(serialize($metadatas).serialize($classNames)).'.db';
                    if (file_exists($backup) && file_exists($backup.'.ser') && $this->isBackupUpToDate($classNames, $backup)) {
                        $om->flush();
                        $om->clear();

                        $this->preFixtureRestore($om, $referenceRepository);

                        copy($backup, $name);

                        $executor = new $executorClass($om);
                        $executor->setReferenceRepository($referenceRepository);
                        $executor->getReferenceRepository()->load($backup);

                        $this->postFixtureRestore();

                        return $executor;
                    }
                }

                // TODO: handle case when using persistent connections. Fail loudly?
                $schemaTool = new SchemaTool($om);
                $schemaTool->dropDatabase();
                if (!empty($metadatas)) {
                    $schemaTool->createSchema($metadatas);
                }
                $this->postFixtureSetup();

                $executor = new $executorClass($om);
                $executor->setReferenceRepository($referenceRepository);
            }
        }

        if (empty($executor)) {
            $purgerClass = 'Doctrine\\Common\\DataFixtures\\Purger\\'.$type.'Purger';
            if ('PHPCR' === $type) {
                $purger = new $purgerClass($om);
                $initManager = $container->has('doctrine_phpcr.initializer_manager')
                    ? $container->get('doctrine_phpcr.initializer_manager')
                    : null;

                $executor = new $executorClass($om, $purger, $initManager);
            } else {
                $purger = new $purgerClass();
                if (null !== $purgeMode) {
                    $purger->setPurgeMode($purgeMode);
                }

                $executor = new $executorClass($om, $purger);
            }

            $executor->setReferenceRepository($referenceRepository);
            $executor->purge();
        }

        $loader = $this->getFixtureLoader($container, $classNames);

        $executor->execute($loader->getFixtures(), true);

        if (isset($name) && isset($backup)) {
            $this->preReferenceSave($om, $executor, $backup);

            $executor->getReferenceRepository()->save($backup);
            copy($name, $backup);

            $this->postReferenceSave($om, $executor, $backup);
        }

        return $executor;
    }

    /**
     * Clean database.
     *
     * @param ManagerRegistry $registry
     * @param EntityManager   $om
     */
    private function cleanDatabase(ManagerRegistry $registry, EntityManager $om)
    {
        $connection = $om->getConnection();

        $mysql = ($registry->getName() === 'ORM'
            && $connection->getDatabasePlatform() instanceof MySqlPlatform);

        if ($mysql) {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
        }

        $this->container->get('liip_functional_test.fixtures_loader')->loadFixtures(array());

        if ($mysql) {
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Locate fixture files.
     *
     * @param array $paths
     *
     * @return array $files
     */
    private function locateResources($paths)
    {
        $files = array();

        $kernel = $this->container->get('kernel');

        foreach ($paths as $path) {
            if ($path[0] !== '@' && file_exists($path) === true) {
                $files[] = $path;
                continue;
            }

            $files[] = $kernel->locateResource($path);
        }

        return $files;
    }

    /**
     * @param array  $paths        Either symfony resource locators (@ BundleName/etc) or actual file paths
     * @param bool   $append
     * @param null   $omName
     * @param string $registryName
     *
     * @return array
     *
     * @throws \BadMethodCallException
     */
    public function loadFixtureFiles(array $paths = array(), $append = false, $omName = null, $registryName = 'doctrine')
    {
        if (!class_exists('Nelmio\Alice\Fixtures')) {
            // This class is available during tests, no exception will be thrown.
            // @codeCoverageIgnoreStart
            throw new \BadMethodCallException('nelmio/alice should be installed to use this method.');
            // @codeCoverageIgnoreEnd
        }

        /** @var ContainerInterface $container */
        $container = $this->container;

        /** @var ManagerRegistry $registry */
        $registry = $container->get($registryName);

        /** @var EntityManager $om */
        $om = $registry->getManager($omName);

        if ($append === false) {
            $this->cleanDatabase($registry, $om);
        }

        $files = $this->locateResources($paths);

        // Check if the Hautelook AliceBundle is registered and if yes, use it instead of Nelmio Alice
        $hautelookLoaderServiceName = 'hautelook_alice.fixtures.loader';
        if ($container->has($hautelookLoaderServiceName)) {
            $loaderService = $container->get($hautelookLoaderServiceName);
            $persisterClass = class_exists('Nelmio\Alice\ORM\Doctrine') ?
                'Nelmio\Alice\ORM\Doctrine' :
                'Nelmio\Alice\Persister\Doctrine';

            return $loaderService->load(new $persisterClass($om), $files);
        }

        return Fixtures::load($files, $om);
    }

    /**
     * Callback function to be executed after Schema creation.
     * Use this to execute acl:init or other things necessary.
     */
    protected function postFixtureSetup()
    {
    }

    /**
     * Callback function to be executed after Schema restore.
     *
     * @return WebTestCase
     */
    protected function postFixtureRestore()
    {
    }

    /**
     * Callback function to be executed before Schema restore.
     *
     * @param ObjectManager            $manager             The object manager
     * @param ProxyReferenceRepository $referenceRepository The reference repository
     *
     * @return WebTestCase
     */
    protected function preFixtureRestore(ObjectManager $manager, ProxyReferenceRepository $referenceRepository)
    {
    }

    /**
     * Callback function to be executed after save of references.
     *
     * @param ObjectManager    $manager        The object manager
     * @param AbstractExecutor $executor       Executor of the data fixtures
     * @param string           $backupFilePath Path of file used to backup the references of the data fixtures
     *
     * @return WebTestCase
     */
    protected function postReferenceSave(ObjectManager $manager, AbstractExecutor $executor, $backupFilePath)
    {
    }

    /**
     * Callback function to be executed before save of references.
     *
     * @param ObjectManager    $manager        The object manager
     * @param AbstractExecutor $executor       Executor of the data fixtures
     * @param string           $backupFilePath Path of file used to backup the references of the data fixtures
     *
     * @return WebTestCase
     */
    protected function preReferenceSave(ObjectManager $manager, AbstractExecutor $executor, $backupFilePath)
    {
    }

    /**
     * Retrieve Doctrine DataFixtures loader.
     *
     * @param ContainerInterface $container
     * @param array              $classNames
     *
     * @return Loader
     */
    protected function getFixtureLoader(ContainerInterface $container, array $classNames)
    {
        $loaderClass = class_exists('Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader')
            ? 'Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader'
            : (class_exists('Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader')
                // This class is not available during tests.
                // @codeCoverageIgnoreStart
                ? 'Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader'
                // @codeCoverageIgnoreEnd
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
     * @param Loader $loader
     * @param string $className
     */
    protected function loadFixtureClass($loader, $className)
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
