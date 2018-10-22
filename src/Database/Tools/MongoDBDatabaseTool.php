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
use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Doctrine\Common\DataFixtures\Purger\PurgerInterface;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class MongoDBDatabaseTool extends AbstractDatabaseTool
{
    protected static $databaseCreated = false;

    /** @var ProxyReferenceRepository|null */
    protected static $referenceRepository;

    public function getType(): string
    {
        return 'MongoDB';
    }

    public function getExecutor(bool $append = false): AbstractExecutor
    {
        if (false === $append || !self::$referenceRepository) {
            self::$referenceRepository = new ProxyReferenceRepository($this->om);
        }

        $executor = new MongoDBExecutor($this->om, $this->getPurger());
        $executor->setReferenceRepository(self::$referenceRepository);

        return $executor;
    }

    public function getPurger(): PurgerInterface
    {
        return new MongoDBPurger($this->om);
    }

    protected function createDatabaseOnce(): void
    {
        if (!self::$databaseCreated) {
            $sm = $this->om->getSchemaManager();
            $sm->createDatabases();
            $sm->updateIndexes();
            self::$databaseCreated = true;
        }
    }

    public function loadFixtures(array $classNames = [], bool $append = false): AbstractExecutor
    {
        $referenceRepository = new ProxyReferenceRepository($this->om);
        $cacheDriver = $this->om->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }

        $this->createDatabaseOnce();

        $backupService = $this->getBackupService();
        if ($backupService) {
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
