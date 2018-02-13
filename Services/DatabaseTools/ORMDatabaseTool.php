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

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManager;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class ORMDatabaseTool extends AbstractDatabaseTool
{
    /**
     * @var EntityManager
     */
    protected $om;

    /**
     * @return string
     */
    public function getType()
    {
        return 'ORM';
    }

    protected function getExecutor(ORMPurger $purger = null)
    {
        return new ORMExecutor($this->om, $purger);
    }

    protected function getPurger()
    {
        $purger = new ORMPurger($this->om, $this->excludedDoctrineTables);

        if (null !== $this->purgeMode) {
            $purger->setPurgeMode($this->purgeMode);
        }

        return $purger;
    }

    protected function cleanDatabase()
    {
        $isMysql = ($this->connection->getDatabasePlatform() instanceof MySqlPlatform);

        if ($isMysql) {
            $this->connection->query('SET FOREIGN_KEY_CHECKS=0');
        }

        $this->loadFixtures([]);

        if ($isMysql) {
            $this->connection->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function loadFixtures(array $classNames)
    {
        $referenceRepository = new ProxyReferenceRepository($this->om);
        $cacheDriver = $this->om->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }

        $backupServiceName = 'liip_functional_test.cache_db.' . $this->connection->getDatabasePlatform()->getName();
        if ($this->container->hasParameter($backupServiceName)) {
            $backupService = $this->container->get($this->container->getParameter($backupServiceName));
        }

        if (isset($backupService)) {
            $backupService->init($this->connection, $this->getMetadatas(), $classNames);

            if ($backupService->isBackupActual()) {
                if (null !== $this->connection) {
                    $this->connection->close();
                }

                $this->om->flush();
                $this->om->clear();

                $this->webTestCase->preFixtureBackupRestore($this->om, $referenceRepository, $backupService->getBackupName());
                $executor = $this->getExecutor($this->getPurger());
                $executor->setReferenceRepository($referenceRepository);
                $backupService->restore($executor);
                $this->webTestCase->postFixtureBackupRestore($backupService->getBackupName());

                return $executor;
            }
        }

        $executor = $this->getExecutor($this->getPurger());
        $executor->setReferenceRepository($referenceRepository);
        $executor->purge();

        $loader = $this->fixturesLoaderFactory->getFixtureLoader($classNames);
        $executor->execute($loader->getFixtures(), true);

        if (isset($backupService)) {
            $this->webTestCase->preReferenceSave($this->om, $executor, $backupService->getBackupName());
            $backupService->backup($executor);
            $this->webTestCase->postReferenceSave($this->om, $executor, $backupService->getBackupName());
        }

        return $executor;
    }
}
