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
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class ORMSqliteDatabaseTool extends ORMDatabaseTool
{
    public function getDriverName(): string
    {
        return 'pdo_sqlite';
    }

    public function loadFixtures(array $classNames = [], bool $append = false): AbstractExecutor
    {
        $referenceRepository = new ProxyReferenceRepository($this->om);
        $cacheDriver = $this->om->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }

        $backupService = $this->getBackupService();
        if ($backupService && $this->databaseCacheEnabled) {
            $backupService->init($this->getMetadatas(), $classNames);

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

        if (false === $append) {
            // TODO: handle case when using persistent connections. Fail loudly?
            $schemaTool = new SchemaTool($this->om);
            $schemaTool->dropDatabase();
            if (!empty($this->getMetadatas())) {
                $schemaTool->createSchema($this->getMetadatas());
            }
        }
        $this->webTestCase->postFixtureSetup();

        $executor = $this->getExecutor($this->getPurger());
        $executor->setReferenceRepository($referenceRepository);
        if (false === $append) {
            $executor->purge();
        }

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
