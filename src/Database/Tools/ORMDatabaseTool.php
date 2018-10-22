<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Database\Tools;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class ORMDatabaseTool extends AbstractDatabaseTool
{
    /** @var EntityManager */
    protected $om;

    /** @var ProxyReferenceRepository|null */
    protected static $referenceRepository;

    protected static $cachedMetadatas = [];

    public function getType(): string
    {
        return 'ORM';
    }

    public function getExecutor(bool $append = false): AbstractExecutor
    {
        if (false === $append || !self::$referenceRepository) {
            self::$referenceRepository = new ProxyReferenceRepository($this->om);
        }

        $executor = new ORMExecutor($this->om, $this->getPurger());
        $executor->setReferenceRepository(self::$referenceRepository);

        return $executor;
    }

    public function getPurger(): PurgerInterface
    {
        return new ORMPurger($this->om, $this->excludedDoctrineTables);
    }

    public function purgeData(): void
    {
        $this->getPurger()->purge();
    }

    public function getDatabasesList(): array
    {
        return $this->connection->getSchemaManager()->listDatabases();
    }

    public function createDatabase(): void
    {
        $params = $this->connection->getParams();
        if (isset($params['master'])) {
            $params = $params['master'];
        }
        $dbName = isset($params['dbname']) ? $params['dbname'] : '';
        unset($params['dbname']);

        $tmpConnection = DriverManager::getConnection($params);
        $tmpConnection->connect();
        $tmpConnection->getSchemaManager()->createDatabase($dbName);
        $tmpConnection->close();
    }

    public function dropDatabase(): void
    {
        $schemaTool = new SchemaTool($this->om);
        $schemaTool->dropDatabase();
    }

    public function isDatabaseExists(): bool
    {
        $params = $this->registry->getConnection()->getParams();
        if (isset($params['master'])) {
            $params = $params['master'];
        }
        $dbName = isset($params['dbname']) ? $params['dbname'] : '';

        return in_array($dbName, $this->getDatabasesList());
    }

    public function createSchema(): void
    {
        $schemaTool = new SchemaTool($this->om);

        $schemaTool->createSchema($this->getMetadatas());
    }

    public function dropSchema(): void
    {
        $schemaTool = new SchemaTool($this->om);

        $schemaTool->dropSchema($this->getMetadatas());
    }

    public function updateSchema(): void
    {
        $schemaTool = new SchemaTool($this->om);

        $schemaTool->updateSchema($this->getMetadatas());
    }

    protected function getMetadatas(): array
    {
        $key = $this->getDriverName().$this->getType().$this->omName;

        if (!isset(self::$cachedMetadatas[$key])) {
            self::$cachedMetadatas[$key] = $this->om->getMetadataFactory()->getAllMetadata();
            usort(self::$cachedMetadatas[$key], function ($a, $b) {
                return strcmp($a->name, $b->name);
            });
        }

        return self::$cachedMetadatas[$key];
    }
}
