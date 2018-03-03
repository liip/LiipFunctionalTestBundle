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

        $backupService = $this->getBackupService();
        if ($backupService) {
            $backupService->init($this->connection, $this->getMetadatas(), $classNames);

            if ($backupService->isBackupActual()) {
                if (null !== $this->connection) {
                    $this->connection->close();
                }

                $this->om->flush();
                $this->om->clear();

                $this->webTestCase->preFixtureBackupRestore($this->om, $referenceRepository, $backupService->getBackupFilePath());
                $executor = $this->getExecutor($this->getPurger());
                $executor->setReferenceRepository($referenceRepository);
                $backupService->restore($executor);
                $this->webTestCase->postFixtureBackupRestore($backupService->getBackupFilePath());

                return $executor;
            }
        }

        $executor = $this->getExecutor($this->getPurger());
        $executor->setReferenceRepository($referenceRepository);
        $executor->purge();

        $loader = $this->fixturesLoaderFactory->getFixtureLoader($classNames);
        $executor->execute($loader->getFixtures(), true);

        if ($backupService) {
            $this->webTestCase->preReferenceSave($this->om, $executor, $backupService->getBackupFilePath());
            $backupService->backup($executor);
            $this->webTestCase->postReferenceSave($this->om, $executor, $backupService->getBackupFilePath());
        }

        return $executor;
    }
}
