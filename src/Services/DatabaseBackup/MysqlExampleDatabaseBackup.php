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
use Doctrine\ORM\Tools\SchemaTool;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 *
 * It's class created just for example that how to create database backup/restore service
 */
class MysqlExampleDatabaseBackup extends AbstractDatabaseBackup implements DatabaseBackupInterface
{
    protected static $referenceData;

    protected static $sql;

    protected static $metadata;

    protected static $schemaUpdatedFlag = false;

    public function getBackupName(): string
    {
        return $this->container->getParameter('kernel.cache_dir').'/test_mysql_'.md5(serialize($this->metadatas).serialize($this->classNames)).'.sql';
    }

    public function getReferenceBackupName(): string
    {
        return $this->getBackupName().'.ser';
    }

    protected function getBackup(): string
    {
        if (empty(self::$sql)) {
            self::$sql = file_get_contents($this->getBackupName());
        }

        return self::$sql;
    }

    protected function getReferenceBackup(): string
    {
        if (empty(self::$referenceData)) {
            self::$referenceData = file_get_contents($this->getReferenceBackupName());
        }

        return self::$referenceData;
    }

    public function isBackupActual(): bool
    {
        return
            file_exists(file_exists($this->getBackupName())) &&
            file_exists(file_exists($this->getReferenceBackupName())) &&
            $this->isBackupUpToDate($this->getBackupName());
    }

    public function backup(AbstractExecutor $executor): void
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

        exec("mysqldump -h $dbHost -u $dbUser -p$dbPass --no-create-info --skip-triggers --no-create-db --no-tablespaces --compact $dbName > {$this->getBackupName()}");
    }

    protected function updateSchemaIfNeed(AbstractExecutor $executor): void
    {
        if (!self::$schemaUpdatedFlag) {
            $schemaTool = new SchemaTool($executor->getReferenceRepository()->getManager());
            $schemaTool->dropDatabase();
            if (!empty($this->metadatas)) {
                $schemaTool->createSchema($this->metadatas);
            }

            self::$schemaUpdatedFlag = true;
        }
    }

    public function restore(AbstractExecutor $executor): void
    {
        $this->connection->query('SET FOREIGN_KEY_CHECKS = 0;');
        $this->updateSchemaIfNeed($executor);
        $truncateSql = [];
        foreach ($this->metadatas as $classMetadata) {
            $truncateSql[] = 'DELETE FROM '.$classMetadata->table['name']; // in small tables it's really faster than truncate
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
