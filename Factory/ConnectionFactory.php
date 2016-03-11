<?php

namespace Liip\FunctionalTestBundle\Factory;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory as BaseConnectionFactory;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;

/**
 * Creates a connection taking the db name from the env with
 * a unique number defined by current process ID.
 */
class ConnectionFactory extends BaseConnectionFactory
{
    /**
     * Create a connection by name.
     *
     * @param array         $params
     * @param Configuration $config
     * @param EventManager  $eventManager
     * @param array         $mappingTypes
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function createConnection(array $params, Configuration $config = null, EventManager $eventManager = null, array $mappingTypes = array())
    {
        $dbName = $this->getDbNameFromEnv($params['dbname']);

        if ($params['driver'] === 'pdo_sqlite') {
            $params['path'] = str_replace('__DBNAME__', $dbName, $params['path']);
        } else {
            $params['dbname'] = $dbName;
        }

        return parent::createConnection($params, $config, $eventManager, $mappingTypes);
    }

    private function getDbNameFromEnv($dbName)
    {
        return 'dbTest'.getenv('TEST_TOKEN');
    }
}
