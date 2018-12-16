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
 */
final class MysqlDatabaseBackup extends AbstractDatabaseBackup implements DatabaseBackupInterface
{
    protected static $referenceData;

    protected static $metadata;

    protected static $schemaUpdatedFlag = false;

    public function getBackupFilePath(): string
    {
        return $this->container->getParameter('kernel.cache_dir').'/test_mysql_'.md5(serialize($this->metadatas).serialize($this->classNames)).'.sql';
    }

    public function getReferenceBackupFilePath(): string
    {
        return $this->getBackupFilePath().'.ser';
    }

    protected function getBackup()
    {
        return file_get_contents($this->getBackupFilePath());
    }

    protected function getReferenceBackup(): string
    {
        if (empty(self::$referenceData)) {
            self::$referenceData = file_get_contents($this->getReferenceBackupFilePath());
        }

        return self::$referenceData;
    }

    public function isBackupActual(): bool
    {
        return
            file_exists($this->getBackupFilePath()) &&
            file_exists($this->getReferenceBackupFilePath()) &&
            $this->isBackupUpToDate($this->getBackupFilePath());
    }

    public function backup(AbstractExecutor $executor): void
    {
        /** @var EntityManager $em */
        $em = $executor->getReferenceRepository()->getManager();
        $connection = $em->getConnection();

        $params = $connection->getParams();
        if (isset($params['master'])) {
            $params = $params['master'];
        }

        $dbName = isset($params['dbname']) ? $params['dbname'] : '';
        $dbHost = isset($params['host']) ? $params['host'] : '';
        $dbPort = isset($params['port']) ? $params['port'] : '';
        $dbUser = isset($params['user']) ? $params['user'] : '';
        $dbPass = isset($params['password']) ? $params['password'] : '';

        $executor->getReferenceRepository()->save($this->getBackupFilePath());
        self::$metadata = $em->getMetadataFactory()->getLoadedMetadata();

        exec("MYSQL_PWD=$dbPass mysqldump --host $dbHost --port=$dbPort --user $dbUser --no-create-info --skip-triggers --no-create-db --no-tablespaces --compact $dbName > {$this->getBackupFilePath()}");
    }

    protected function updateSchemaIfNeed(EntityManager $em)
    {
        if (!self::$schemaUpdatedFlag) {
            $schemaTool = new SchemaTool($em);
            $schemaTool->dropDatabase();
            if (!empty($this->metadatas)) {
                $schemaTool->createSchema($this->metadatas);
            }

            self::$schemaUpdatedFlag = true;
        }
    }

    public function restore(AbstractExecutor $executor, array $excludedTables = []): void
    {
        /** @var EntityManager $em */
        $em = $executor->getReferenceRepository()->getManager();
        $connection = $em->getConnection();

        $connection->query('SET FOREIGN_KEY_CHECKS = 0;');
        $this->updateSchemaIfNeed($em);
        $truncateSql = [];
        foreach ($this->metadatas as $classMetadata) {
            $tableName = $classMetadata->table['name'];

            if (!in_array($tableName, $excludedTables)) {
                $truncateSql[] = 'DELETE FROM '.$tableName; // in small tables it's really faster than truncate
            }
        }
        if (!empty($truncateSql)) {
            $connection->query(implode(';', $truncateSql));
        }

        // Only run query if it exists, to avoid the following exception:
        // SQLSTATE[42000]: Syntax error or access violation: 1065 Query was empty
        $backup = $this->getBackup();
        if (!empty($backup)) {
            $connection->query($backup);
        }

        $connection->query('SET FOREIGN_KEY_CHECKS = 1;');

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
