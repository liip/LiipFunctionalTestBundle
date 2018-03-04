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
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class SqliteDatabaseBackup extends AbstractDatabaseBackup implements DatabaseBackupInterface
{
    public function getBackupFilePath()
    {
        return $this->container->getParameter('kernel.cache_dir').'/test_sqlite_'.md5(serialize($this->metadatas).serialize($this->classNames)).'.db';
    }

    private function getDatabaseName(Connection $connection)
    {
        $params = $connection->getParams();
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
        /** @var EntityManager $em */
        $em = $executor->getReferenceRepository()->getManager();
        $connection = $em->getConnection();

        $executor->getReferenceRepository()->save($this->getBackupFilePath());
        copy($this->getDatabaseName($connection), $this->getBackupFilePath());
    }

    public function restore(AbstractExecutor $executor)
    {
        /** @var EntityManager $em */
        $em = $executor->getReferenceRepository()->getManager();
        $connection = $em->getConnection();

        copy($this->getBackupFilePath(), $this->getDatabaseName($connection));
        $executor->getReferenceRepository()->load($this->getBackupFilePath());
    }
}
