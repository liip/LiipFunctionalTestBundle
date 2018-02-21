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

use Doctrine\Bundle\PHPCRBundle\Initializer\InitializerManager;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\PHPCRPurger;
use Doctrine\ODM\PHPCR\DocumentManager;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class PHPCRDatabaseTool extends AbstractDatabaseTool
{
    /**
     * @var DocumentManager
     */
    protected $om;

    public function getType(): string
    {
        return 'PHPCR';
    }

    protected function getExecutor(PHPCRPurger $purger = null, InitializerManager $initializerManager = null): AbstractExecutor
    {
        $executorClass = class_exists('Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor')
            ? 'Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor'
            : 'Doctrine\\Common\\DataFixtures\\Executor\\'.$this->getType().'Executor';

        return new $executorClass($this->om, $purger, $initializerManager);
    }

    protected function getPurger(): PHPCRPurger
    {
        return new PHPCRPurger($this->om);
    }

    protected function getInitializerManager(): ?InitializerManager
    {
        $serviceName = 'doctrine_phpcr.initializer_manager';

        return $this->container->has($serviceName) ? $this->container->get($serviceName) : null;
    }

    public function loadFixtures(array $classNames = [], bool $append = false): AbstractExecutor
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

                $this->webTestCase->preFixtureBackupRestore($this->om, $referenceRepository, $backupService->getBackupName());
                $executor = $this->getExecutor($this->getPurger());
                $executor->setReferenceRepository($referenceRepository);
                $backupService->restore($executor);
                $this->webTestCase->postFixtureBackupRestore($backupService->getBackupName());

                return $executor;
            }
        }

        $executor = $this->getExecutor($this->getPurger(), $this->getInitializerManager());
        $executor->setReferenceRepository($referenceRepository);
        if (false === $append) {
            $executor->purge();
        }

        $loader = $this->fixturesLoaderFactory->getFixtureLoader($classNames);
        $executor->execute($loader->getFixtures(), true);

        if ($backupService) {
            $this->webTestCase->preReferenceSave($this->om, $executor, $backupService->getBackupName());
            $backupService->backup($executor);
            $this->webTestCase->postReferenceSave($this->om, $executor, $backupService->getBackupName());
        }

        return $executor;
    }
}
