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

use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class MongoDBDatabaseTool extends AbstractDatabaseTool
{
    /**
     * @return string
     */
    public function getType()
    {
        return 'MongoDB';
    }

    /**
     * @return MongoDBExecutor
     */
    protected function getExecutor(MongoDBPurger $purger = null)
    {
        return new MongoDBExecutor($this->om, $purger);
    }

    /**
     * @return MongoDBPurger
     */
    protected function getPurger()
    {
        return new MongoDBPurger();
    }

    public function loadFixtures(array $classNames)
    {
        $referenceRepository = new ProxyReferenceRepository($this->om);
        $cacheDriver = $this->om->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }

        $backupServiceName = 'liip_functional_test.cache_db.'.$this->connection->getDatabasePlatform()->getName();
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
