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
    protected $container;

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

    public function setRegistry(ManagerRegistry $registry): void
    {
        $this->registry = $registry;
    }

    public function setObjectManagerName(string $omName = null): void
    {
        $this->omName = $omName;
        $this->om = $this->registry->getManager($omName);
        $this->connection = $this->registry->getConnection($omName);
    }

    public function setPurgeMode(string $purgeMode = null): void
    {
        $this->purgeMode = $purgeMode;
    }

    public function setWebTestCase(WebTestCase $webTestCase): void
    {
        $this->webTestCase = $webTestCase;
    }

    abstract public function getType(): string;

    public function getDatabasePlatform(): string
    {
        return 'default';
    }

    abstract public function loadFixtures(array $classNames = [], bool $append = false): AbstractExecutor;

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
            $this->cleanDatabase();
        }

        $files = $this->locateResources($paths);

        return $this->container->get($persisterLoaderServiceName)->load($files);
    }

    protected function cleanDatabase(): void
    {
        $this->loadFixtures([]);
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

    protected function getMetadatas(): array
    {
        if (!isset(self::$cachedMetadatas[$this->omName])) {
            self::$cachedMetadatas[$this->omName] = $this->om->getMetadataFactory()->getAllMetadata();
            usort(self::$cachedMetadatas[$this->omName], function ($a, $b) {
                return strcmp($a->name, $b->name);
            });
        }

        return self::$cachedMetadatas[$this->omName];
    }

    public function setExcludedDoctrineTables(array $excludedDoctrineTables): void
    {
        $this->excludedDoctrineTables = $excludedDoctrineTables;
    }
}
