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
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class MongodbDatabaseBackup extends AbstractDatabaseBackup implements DatabaseBackupInterface
{
    protected static $referenceData;

    protected static $metadata;

    protected static $databases;

    public function init(array $metadatas, array $classNames)
    {
        $this->metadatas = $metadatas;
        $this->classNames = $classNames;
    }

    public function getBackupFilePath()
    {
        return $this->container->getParameter('kernel.cache_dir').'/test_mongodb_'.md5(serialize($this->metadatas).serialize($this->classNames));
    }

    public function getReferenceBackupFilePath()
    {
        return $this->getBackupFilePath().'.ser';
    }

    protected function getReferenceBackup()
    {
        if (empty(self::$referenceData)) {
            self::$referenceData = file_get_contents($this->getReferenceBackupFilePath());
        }

        return self::$referenceData;
    }

    public function isBackupActual()
    {
        return
            file_exists($this->getBackupFilePath()) &&
            file_exists($this->getReferenceBackupFilePath()) &&
            $this->isBackupUpToDate($this->getBackupFilePath());
    }

    protected function getDatabases(DocumentManager $dm)
    {
        if (!self::$databases) {
            self::$databases = [];
            foreach ($dm->getDocumentDatabases() as $db) {
                /** @var $db \Doctrine\MongoDB\LoggableDatabase */
                $hosts = $db->getConnection()->getMongoClient()->getHosts();

                foreach ($hosts as $host) {
                    self::$databases[$db->getName()] = $host;
                }
            }
        }

        return self::$databases;
    }

    public function backup(AbstractExecutor $executor)
    {
        /** @var DocumentManager $dm */
        $dm = $executor->getReferenceRepository()->getManager();

        foreach ($this->getDatabases($dm) as $dbName => $server) {
            $dbHost = $server['host'];
            $dbPort = $server['port'];

            exec("mongodump --quiet --db $dbName --host $dbHost --port $dbPort --out {$this->getBackupFilePath()}");
        }

        $executor->getReferenceRepository()->save($this->getBackupFilePath());
        self::$metadata = $dm->getMetadataFactory()->getLoadedMetadata();
    }

    public function restore(AbstractExecutor $executor)
    {
        /** @var DocumentManager $dm */
        $dm = $executor->getReferenceRepository()->getManager();
        $connection = $dm->getConnection();

        foreach ($this->getDatabases($dm) as $dbName => $server) {
            $dbHost = $server['host'];
            $dbPort = $server['port'];

            $connection->dropDatabase($dbName);
            exec("mongorestore --quiet --db $dbName --host $dbHost --port $dbPort {$this->getBackupFilePath()}/$dbName", $output);
        }

        if (self::$metadata) {
            // it need for better performance
            foreach (self::$metadata as $class => $data) {
                $dm->getMetadataFactory()->setMetadataFor($class, $data);
            }
            $executor->getReferenceRepository()->unserialize($this->getReferenceBackup());
        } else {
            $executor->getReferenceRepository()->unserialize($this->getReferenceBackup());
            self::$metadata = $dm->getMetadataFactory()->getLoadedMetadata();
        }
    }
}
