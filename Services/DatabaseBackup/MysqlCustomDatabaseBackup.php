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
use Doctrine\ORM\EntityManager;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 *
 * It's class created just for example that how to create database backup/restore service
 */
class MysqlCustomDatabaseBackup extends AbstractDatabaseBackup
{
    static protected $referenceData;

    static protected $sql;

    static protected $metadata;

    public function getBackupName()
    {
        return $this->container->getParameter('kernel.cache_dir').'/test_mysql_'.md5(serialize($this->metadatas).serialize($this->classNames)).'.sql';
    }

    public function getReferenceBackupName()
    {
        return $this->getBackupName().'.ser';
    }

    protected function getBackup()
    {
        if (empty(self::$sql)) {
            self::$sql = file_get_contents($this->getBackupName());
        }

        return self::$sql;
    }

    protected function getReferenceBackup()
    {
        if (empty(self::$referenceData)) {
            self::$referenceData = file_get_contents($this->getReferenceBackupName());
        }

        return self::$referenceData;
    }

    public function isBackupActual()
    {
        return
            file_exists($this->getBackupName()) &&
            file_exists($this->getReferenceBackupName()) &&
            $this->isBackupUpToDate($this->getBackupName());
    }

    public function backup(AbstractExecutor $executor)
    {
        $params = $this->connection->getParams();
        if (isset($params['master'])) {
            $params = $params['master'];
        }

        $dbName = isset($params['dbname']) ? $params['dbname'] : '';
        $dbHost = isset($params['host']) ? $params['host'] : '';
        $dbPort = isset($params['port']) ? $params['port'] : '';
        $dbUser = isset($params['user']) ? $params['user'] : '';
        $dbPass = isset($params['password']) ? $params['password'] : '';

        $executor->getReferenceRepository()->save($this->getBackupName());
        /** @var EntityManager $em */
        $em = $executor->getReferenceRepository()->getManager();
        self::$metadata = $em->getMetadataFactory()->getLoadedMetadata();

        exec("mysqldump -h $dbHost -u $dbUser -p$dbPass --no-create-info --skip-triggers --no-create-db $dbName > {$this->getBackupName()}");
    }

    public function restore(AbstractExecutor $executor)
    {
        $this->connection->query('SET FOREIGN_KEY_CHECKS = 0;');
        $truncateSql = [];
        foreach ($this->metadatas as $classMetadata) {
            $truncateSql[] = 'TRUNCATE '.$classMetadata->table['name'];
        }
        $this->connection->query(implode(';', $truncateSql));
        $this->connection->query($this->getBackup());
        $this->connection->query('SET FOREIGN_KEY_CHECKS = 1;');

        /** @var EntityManager $em */
        $em = $executor->getReferenceRepository()->getManager();
        if (self::$metadata) {
            // it need for better performance
            foreach (self::$metadata as $class => $data) {
                $em->getMetadataFactory()->setMetadataFor($class, $data);
            }
            $executor->getReferenceRepository()->unserialize($this->getReferenceBackup());
        } else {
            $executor->getReferenceRepository()->unserialize($this->getReferenceBackup());
            self::$metadata = $em->getMetadataFactory()->getLoadedMetadata();
        }
    }
}
