<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Database\Collection;


use Liip\FunctionalTestBundle\Database\Backup\AbstractDatabaseBackup;
use Liip\FunctionalTestBundle\Database\Manager\AbstractDatabaseManager;
use Liip\FunctionalTestBundle\Database\Tools\AbstractDatabaseTool;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class DatabaseToolCollection
{
    /** @var AbstractDatabaseTool[][] */
    private $toolServices = [];

    /** @var AbstractDatabaseBackup[][] */
    private $backupServices = [];

    /** @var AbstractDatabaseManager[][] */
    private $managerServices = [];

    public function addTool(AbstractDatabaseTool $databaseTool): void
    {
        $this->toolServices[$databaseTool->getType()][$databaseTool->getDriverName()] = $databaseTool;
    }

    public function addBackup(AbstractDatabaseBackup $databaseBackup): void
    {
        $this->backupServices[$databaseBackup->getType()][$databaseBackup->getDriverName()] = $databaseBackup;
    }

    public function addManager(AbstractDatabaseManager $databaseManager): void
    {
        $this->managerServices[$databaseManager->getType()][$databaseManager->getDriverName()] = $databaseManager;
    }

    public function getManager(ManagerRegistry $registry, string $omName = null): AbstractDatabaseManager
    {
        $registryName = $registry->getName();
        $driverName = ('ORM' === $registry->getName()) ? $registry->getConnection()->getDriver()->getName() : 'default';

        $tool = $this->toolServices[$registryName][$driverName] ?? $this->toolServices[$registryName]['default'];
        $backup = $this->backupServices[$registryName][$driverName] ?? $this->backupServices[$registryName]['default'];
        $manager = $this->managerServices[$registryName][$driverName] ?? $this->managerServices[$registryName]['default'];
        $manager->init($registry, $omName, $tool, $backup);

        return $manager;
    }
}
