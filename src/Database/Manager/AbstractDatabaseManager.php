<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Database\Manager;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Liip\FunctionalTestBundle\Database\Backup\AbstractDatabaseBackup;
use Liip\FunctionalTestBundle\Database\Backup\DatabaseBackupInterface;
use Liip\FunctionalTestBundle\Database\DatabaseManagerInterface;
use Liip\FunctionalTestBundle\Database\Tools\AbstractDatabaseTool;
use Liip\FunctionalTestBundle\Database\Tools\DatabaseToolInterface;
use Liip\FunctionalTestBundle\Services\FixturesLoaderFactory;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
abstract class AbstractDatabaseManager implements DatabaseManagerInterface
{
    protected $container;

    protected $fixturesLoaderFactory;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $omName;

    /** @var ObjectManager */
    protected $om;

    /** @var Connection */
    protected $connection;

    /** @var AbstractDatabaseTool */
    private $databaseTool;

    /** @var AbstractDatabaseBackup|null */
    private $databaseBackup;

    /** @var int|null */
    protected $purgeMode;

    protected $excludedDoctrineTables = [];

    /** @var callable */
    protected $postFixtureSetupCallback;

    /** @var callable */
    protected $preFixtureBackupRestoreCallback;

    /** @var callable */
    protected $postFixtureBackupRestoreCallback;

    /** @var callable */
    protected $preReferenceSaveCallback;

    /** @var callable */
    protected $postReferenceSaveCallback;

    public function __construct(ContainerInterface $container, FixturesLoaderFactory $fixturesLoaderFactory)
    {
        $this->container = $container;
        $this->fixturesLoaderFactory = $fixturesLoaderFactory;
    }

    abstract public function getType(): string;

    public function getDriverName(): string
    {
        return 'default';
    }

    public function init(
        ManagerRegistry $registry,
        string $omName,
        DatabaseToolInterface $databaseTool,
        DatabaseBackupInterface $databaseBackup = null
    ): void {
        $this->registry = $registry;
        $this->omName = $omName;
        $this->om = $this->registry->getManager($omName);
        $this->connection = $this->registry->getConnection($omName);
        $this->databaseTool = $databaseTool;
        $this->databaseBackup = $databaseBackup;
    }

    public function setPurgeMode(int $purgeMode = null): void
    {
        $this->purgeMode = $purgeMode;
    }

    public function setExcludedDoctrineTables(array $excludedDoctrineTables): void
    {
        $this->excludedDoctrineTables = $excludedDoctrineTables;
    }

    public function setPostFixtureSetupCallback(callable $callback): void
    {
        $this->postFixtureSetupCallback = $callback;
    }

    public function setPreFixtureBackupRestoreCallback(callable $callback): void
    {
        $this->preFixtureBackupRestoreCallback = $callback;
    }

    public function setPostFixtureBackupRestoreCallback(callable $callback): void
    {
        $this->postFixtureBackupRestoreCallback = $callback;
    }

    public function setPreReferenceSaveCallback(callable $callback): void
    {
        $this->preReferenceSaveCallback = $callback;
    }

    public function setPostReferenceSaveCallback(callable $callback): void
    {
        $this->postReferenceSaveCallback = $callback;
    }

    public function loadFixtures(array $classNames = [], bool $append = false): AbstractExecutor
    {
        $executor = $this->getDatabaseTool()->getExecutor($append);

        $loader = $this->fixturesLoaderFactory->getFixtureLoader($classNames);
        $executor->execute($loader->getFixtures(), $append);

        return $executor;
    }

    /**
     * @throws \BadMethodCallException
     */
    public function loadAliceFixture(array $paths = [], bool $append = false): array
    {
        $persisterLoaderServiceName = 'fidry_alice_data_fixtures.loader.doctrine';
        if (!$this->container->has($persisterLoaderServiceName)) {
            throw new \BadMethodCallException('theofidry/alice-data-fixtures must be installed to use this method.');
        }

        if (false === $append) {
            $this->purgeData();
        }

        $files = $this->locateResources($paths);

        return $this->container->get($persisterLoaderServiceName)->load($files);
    }

    /**
     * Locate fixture files.
     *
     * @throws \InvalidArgumentException if a wrong path is given outside a bundle
     */
    protected function locateResources(array $paths): array
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

    public function isDatabaseExists(): bool
    {
        return $this->getDatabaseTool()->isDatabaseExists();
    }

    public function createDatabase(): void
    {
        $this->getDatabaseTool()->createDatabase();
    }

    public function dropDatabase(): void
    {
        $this->getDatabaseTool()->dropDatabase();
    }

    public function createSchema(): void
    {
        $this->getDatabaseTool()->createSchema();
    }

    public function dropSchema(): void
    {
        $this->getDatabaseTool()->dropSchema();
    }

    public function updateSchema(): void
    {
        $this->getDatabaseTool()->updateSchema();
    }

    public function purgeData(): void
    {
        $this->getDatabaseTool()->purgeData();
    }

    public function isBackupExists(): bool
    {
        $this->getDatabaseBackup()->isBackupExists();
    }

    public function backup(): void
    {
        $executor = $this->getDatabaseTool()->getExecutor();

        $this->getDatabaseBackup()->backup($executor);
    }

    public function restore(): AbstractExecutor
    {
        $executor = $this->getDatabaseTool()->getExecutor();
        $this->getDatabaseBackup()->restore($executor);

        return $executor;
    }

    protected function getDatabaseTool(): DatabaseToolInterface
    {
        $this->databaseTool->init($this->registry, $this->omName);

        return $this->databaseTool;
    }

    protected function getDatabaseBackup(): DatabaseBackupInterface
    {
        if (!($this->databaseBackup instanceof DatabaseBackupInterface)) {
            throw new \Exception('some text');
        }

        $this->databaseBackup->init($this->registry, $this->omName);

        return $this->databaseBackup;
    }
}
