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
class SqliteDatabaseBackup extends AbstractDatabaseBackup implements DatabaseBackupInterface
{
    public function getBackupFilePath()
    {
        return $this->container->getParameter('kernel.cache_dir').'/test_sqlite_'.md5(serialize($this->metadatas).serialize($this->classNames)).'.db';
    }

    private function getDatabaseName()
    {
        $params = $this->connection->getParams();
        if (isset($params['master'])) {
            $params = $params['master'];
        }

        $name = isset($params['path']) ? $params['path'] : (isset($params['dbname']) ? $params['dbname'] : false);
        if (!$name) {
            throw new \InvalidArgumentException("Connection does not contain a 'path' or 'dbname' parameter and cannot be dropped.");
        }

        return $name;
    }

    public function isBackupActual()
    {
        $backupDBFileName = $this->getBackupFilePath();
        $backupReferenceFileName = $backupDBFileName.'.ser';

        return file_exists($backupDBFileName) && file_exists($backupReferenceFileName) && $this->isBackupUpToDate($backupDBFileName);
    }

    public function backup(AbstractExecutor $executor)
    {
        $executor->getReferenceRepository()->save($this->getBackupFilePath());
        copy($this->getDatabaseName(), $this->getBackupFilePath());
    }

    public function restore(AbstractExecutor $executor)
    {
        copy($this->getBackupFilePath(), $this->getDatabaseName());
        $executor->getReferenceRepository()->load($this->getBackupFilePath());
    }
}
