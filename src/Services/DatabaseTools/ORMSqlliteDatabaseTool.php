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
class ORMSqlliteDatabaseTool extends ORMDatabaseTool
{
    public function getDatabasePlatform(): string
    {
        return 'sqlite';
    }

    public function loadFixtures(array $classNames = [], bool $append = false): AbstractExecutor
    {
        $referenceRepository = new ProxyReferenceRepository($this->om);
        $cacheDriver = $this->om->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }

        if ($this->container->getParameter('liip_functional_test.cache_sqlite_db')) {
            $backupService = $this->container->get('liip_functional_test.services_database_backup.sqlite');
        } else {
            $backupServiceName = 'liip_functional_test.cache_db'.$this->connection->getDriver()->getName();
            if ($this->container->hasParameter($backupServiceName)) {
                $backupService = $this->container->get($backupServiceName);
            }
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

        // TODO: handle case when using persistent connections. Fail loudly?
        $schemaTool = new SchemaTool($this->om);
        $schemaTool->dropDatabase();
        if (!empty($this->getMetadatas())) {
            $schemaTool->createSchema($this->getMetadatas());
        }
        $this->webTestCase->postFixtureSetup();

        $executor = $this->getExecutor($this->getPurger());
        $executor->setReferenceRepository($referenceRepository);
        if (false === $append) {
            $executor->purge();
        }

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
