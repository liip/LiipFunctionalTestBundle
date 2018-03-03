<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Services\DatabaseTools;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Liip\FunctionalTestBundle\Services\DatabaseBackup\DatabaseBackupInterface;
use Liip\FunctionalTestBundle\Services\FixturesLoaderFactory;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Nelmio\Alice\Fixtures;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
abstract class AbstractDatabaseTool
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var FixturesLoaderFactory
     */
    protected $fixturesLoaderFactory;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $omName;

    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $purgeMode;

    /**
     * @var WebTestCase
     */
    protected $webTestCase;

    /**
     * @var array
     */
    private static $cachedMetadatas = [];

    protected $excludedDoctrineTables = [];

    public function __construct(ContainerInterface $container, FixturesLoaderFactory $fixturesLoaderFactory)
    {
        $this->container = $container;
        $this->fixturesLoaderFactory = $fixturesLoaderFactory;
    }

    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $omName
     */
    public function setObjectManagerName($omName = null)
    {
        $this->omName = $omName;
        $this->om = $this->registry->getManager($omName);
        $this->connection = $this->registry->getConnection($omName);
    }

    /**
     * @param string $purgeMode
     */
    public function setPurgeMode($purgeMode)
    {
        $this->purgeMode = $purgeMode;
    }

    /**
     * @param WebTestCase $webTestCase
     */
    public function setWebTestCase(WebTestCase $webTestCase)
    {
        $this->webTestCase = $webTestCase;
    }

    /**
     * @return string
     */
    abstract public function getType();

    /**
     * @return string
     */
    public function getDriverName()
    {
        return 'default';
    }

    /**
     * @return DatabaseBackupInterface|null
     */
    protected function getBackupService()
    {
        $backupServiceParamName = strtolower('liip_functional_test.cache_db.'.(
            ('ORM' === $this->registry->getName())
                ? $this->connection->getDatabasePlatform()->getName()
                : $this->getType()
            ));

        if ($this->container->hasParameter($backupServiceParamName)) {
            $backupServiceName = $this->container->getParameter($backupServiceParamName);
            if ($this->container->has($backupServiceName)) {
                $backupService = $this->container->get($backupServiceName);
            }
        }

        return (isset($backupService) && $backupService instanceof DatabaseBackupInterface) ? $backupService : null;
    }

    /**
     * @param array $classNames
     *
     * @return AbstractExecutor
     */
    abstract public function loadFixtures(array $classNames);

    /**
     * @param array $paths
     * @param bool  $append
     *
     * @return array
     */
    public function loadAliceFixture(array $paths = [], $append = false)
    {
        if (!class_exists('Nelmio\Alice\Fixtures')) {
            // This class is available during tests, no exception will be thrown.
            // @codeCoverageIgnoreStart
            throw new \BadMethodCallException('nelmio/alice should be installed to use this method.');
            // @codeCoverageIgnoreEnd
        }

        if (false === $append) {
            $this->cleanDatabase();
        }

        $files = $this->locateResources($paths);

        // Check if the Hautelook AliceBundle is registered and if yes, use it instead of Nelmio Alice
        $hautelookLoaderServiceName = 'hautelook_alice.fixtures.loader';
        if ($this->container->has($hautelookLoaderServiceName)) {
            $loaderService = $this->container->get($hautelookLoaderServiceName);
            $persisterClass = class_exists('Nelmio\Alice\ORM\Doctrine') ?
                'Nelmio\Alice\ORM\Doctrine' :
                'Nelmio\Alice\Persister\Doctrine';

            return $loaderService->load(new $persisterClass($this->om), $files);
        }

        return Fixtures::load($files, $this->om);
    }

    protected function cleanDatabase()
    {
        $this->loadFixtures([]);
    }

    /**
     * Locate fixture files.
     *
     * @param array $paths
     *
     * @throws \InvalidArgumentException if a wrong path is given outside a bundle
     *
     * @return array $files
     */
    protected function locateResources($paths)
    {
        $files = [];

        $kernel = $this->container->get('kernel');

        foreach ($paths as $path) {
            if ('@' !== $path[0]) {
                if (!file_exists($path)) {
                    throw new \InvalidArgumentException(sprintf('Unable to find file "%s".', $path));
                }
                $files[] = $path;

                continue;
            }

            $files[] = $kernel->locateResource($path);
        }

        return $files;
    }

    protected function getMetadatas()
    {
        if (!isset(self::$cachedMetadatas[$this->omName])) {
            self::$cachedMetadatas[$this->omName] = $this->om->getMetadataFactory()->getAllMetadata();
            usort(self::$cachedMetadatas[$this->omName], function ($a, $b) {
                return strcmp($a->name, $b->name);
            });
        }

        return self::$cachedMetadatas[$this->omName];
    }

    public function setExcludedDoctrineTables(array $excludedDoctrineTables)
    {
        $this->excludedDoctrineTables = $excludedDoctrineTables;
    }
}
