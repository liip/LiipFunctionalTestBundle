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
     * @param string $type
     *
     * @return string
     */
    private function getExecutorClass($type)
    {
        return 'PHPCR' === $type && class_exists('Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor')
            ? 'Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor'
            : 'Doctrine\\Common\\DataFixtures\\Executor\\'.$type.'Executor';
    }

    /**
     * Get file path of the SQLite database.
     *
     * @param Connection $connection
     *
     * @return string $name
     */
    private function getNameParameter(Connection $connection)
    {
        $params = $connection->getParams();

        if (isset($params['master'])) {
            $params = $params['master'];
        }

        $name = isset($params['path']) ? $params['path'] : (isset($params['dbname']) ? $params['dbname'] : false);

        if (!$name) {
            throw new \InvalidArgumentException("Connection does not contain a 'path' or 'dbname' parameter and cannot be dropped.");
        }

        return $name;
    }

    /**
     * Purge SQLite database.
     *
     * @param ObjectManager $om
     * @param string        $omName The name of object manager to use
     */
    private function getCachedMetadatas(ObjectManager $om, $omName)
    {
        if (!isset(self::$cachedMetadatas[$omName])) {
            self::$cachedMetadatas[$omName] = $om->getMetadataFactory()->getAllMetadata();
            usort(self::$cachedMetadatas[$omName], function ($a, $b) { return strcmp($a->name, $b->name); });
        }

        return self::$cachedMetadatas[$omName];
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

        foreach ($classNames as &$className) {
            $fixtureLastModifiedDateTime = $this->getFixtureLastModified($className);
            if ($backupLastModifiedDateTime < $fixtureLastModifiedDateTime) {
                return false;
            }
        }

        return true;
    }

    /**
     * Copy SQLite backup file.
     *
     * @param ObjectManager            $om
     * @param string                   $executorClass
     * @param ProxyReferenceRepository $referenceRepository
     * @param string                   $backup              Path of the source file.
     * @param string                   $name                Path of the destination file.
     */
    private function copySqliteBackup($om, $executorClass,
                                      $referenceRepository, $backup, $name)
    {
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

    /**
     * Purge database.
     *
     * @param ObjectManager            $om
     * @param string                   $type
     * @param int                      $purgeMode
     * @param string                   $executorClass
     * @param ProxyReferenceRepository $referenceRepository
     */
    private function purgeDatabase(ObjectManager $om, $type, $purgeMode,
                                   $executorClass,
                                   ProxyReferenceRepository $referenceRepository)
    {
        $purgerClass = 'Doctrine\\Common\\DataFixtures\\Purger\\'.$type.'Purger';
        if ('PHPCR' === $type) {
            $purger = new $purgerClass($om);
            $initManager = $this->container->has('doctrine_phpcr.initializer_manager')
                ? $this->container->get('doctrine_phpcr.initializer_manager')
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

        return $executor;
    }

    /**
     * Purge database.
     *
     * @param ObjectManager            $om
     * @param array                    $metadatas
     * @param string                   $executorClass
     * @param ProxyReferenceRepository $referenceRepository
     */
    private function createSqliteSchema(ObjectManager $om,
                                        $metadatas, $executorClass,
                                        ProxyReferenceRepository $referenceRepository)
    {
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
        /** @var ManagerRegistry $registry */
        $registry = $this->container->get($registryName);
        $om = $registry->getManager($omName);
        $type = $registry->getName();

        $executorClass = $this->getExecutorClass($type);
        $referenceRepository = new ProxyReferenceRepository($om);

        $cacheDriver = $om->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }

        if ('ORM' === $type) {
            $connection = $om->getConnection();
            if ($connection->getDriver() instanceof SqliteDriver) {
                $name = $this->getNameParameter($connection);
                $metadatas = $this->getCachedMetadatas($om, $omName);

                if ($this->container->getParameter('liip_functional_test.cache_sqlite_db')) {
                    $backup = $this->container->getParameter('kernel.cache_dir').'/test_'.md5(serialize($metadatas).serialize($classNames)).'.db';
                    if (file_exists($backup) && file_exists($backup.'.ser') && $this->isBackupUpToDate($classNames, $backup)) {
                        $executor = $this->copySqliteBackup($om,
                            $executorClass, $referenceRepository,
                            $backup, $name);

                        return $executor;
                    }
                }

                $this->createSqliteSchema($om, $metadatas,
                    $executorClass, $referenceRepository);
            }
        }

        if (empty($executor)) {
            $executor = $this->purgeDatabase($om, $type, $purgeMode,
                $executorClass, $referenceRepository);
        }

        $loader = $this->getFixtureLoader($classNames);

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

        $this->loadFixtures(array());

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
            throw new \BadMethodCallException('nelmio/alice should be installed to use this method.');
        }

        /** @var ManagerRegistry $registry */
        $registry = $this->container->get($registryName);
        /** @var EntityManager $om */
        $om = $registry->getManager($omName);

        if ($append === false) {
            $this->cleanDatabase($registry, $om);
        }

        $files = $this->locateResources($paths);

        return Fixtures::load($files, $om);
    }

    /**
     * Retrieve Doctrine DataFixtures loader.
     *
     * @param array $classNames
     *
     * @return Loader
     */
    protected function getFixtureLoader(array $classNames)
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
}
