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
class DatabaseBackupCollection
{
    /** @var AbstractDatabaseBackup[][] */
    private $backupServices = [];

    public function add(AbstractDatabaseBackup $databaseBackup): void
    {
        $this->backupServices[$databaseBackup->getType()][$databaseBackup->getDriverName()] = $databaseBackup;
    }

    public function get(ManagerRegistry $registry): AbstractDatabaseBackup
    {
        $registryName = $registry->getName();
        $driverName = ('ORM' === $registry->getName()) ? $registry->getConnection()->getDriver()->getName() : 'default';

        return $this->backupServices[$registryName][$driverName] ?? $this->backupServices[$registryName]['default'];
    }
}
