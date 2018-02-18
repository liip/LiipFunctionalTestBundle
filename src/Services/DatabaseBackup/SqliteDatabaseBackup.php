<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Services\DatabaseBackup;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class SqliteDatabaseBackup extends AbstractDatabaseBackup
{
    public function getBackupName(): string
    {
        return $this->container->getParameter('kernel.cache_dir').'/test_sqllite_'.md5(serialize($this->metadatas).serialize($this->classNames)).'.db';
    }

    private function getDatabaseName(): string
    {
        $params = $this->connection->getParams();
        if (isset($params['master'])) {
            $params = $params['master'];
        }

        $name = $params['path'] ?? ($params['dbname'] ?? false);
        if (!$name) {
            throw new \InvalidArgumentException("Connection does not contain a 'path' or 'dbname' parameter and cannot be dropped.");
        }

        return $name;
    }

    public function isBackupActual(): bool
    {
        $backupDBFileName = $this->getBackupName();
        $backupReferenceFileName = $backupDBFileName.'.ser';

        return file_exists($backupDBFileName) && file_exists($backupReferenceFileName) && $this->isBackupUpToDate($backupDBFileName);
    }

    public function backup(AbstractExecutor $executor): void
    {
        $executor->getReferenceRepository()->save($this->getBackupName());
        copy($this->getDatabaseName(), $this->getBackupName());
    }

    public function restore(AbstractExecutor $executor): void
    {
        copy($this->getBackupName(), $this->getDatabaseName());
        $executor->getReferenceRepository()->load($this->getBackupName());
    }
}