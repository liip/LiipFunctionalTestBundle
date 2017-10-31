<?php

declare(strict_types=1);

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

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
    public function createConnection(array $params, Configuration $config = null, EventManager $eventManager = null, array $mappingTypes = [])
    {
        $dbName = $this->getDbNameFromEnv($params['dbname']);

        if ('pdo_sqlite' === $params['driver']) {
            $params['path'] = str_replace('__DBNAME__', $dbName, $params['path']);
        } else {
            $params['dbname'] = $dbName;
        }

        return parent::createConnection($params, $config, $eventManager, $mappingTypes);
    }

    private function getDbNameFromEnv(string $dbName)
    {
        return 'dbTest'.getenv('TEST_TOKEN');
    }
}
