<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Database;

use Liip\FunctionalTestBundle\Database\Collection\DatabaseToolCollection;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class DatabaseManagerFactory
{
    private $container;
    private $databaseToolCollection;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->databaseToolCollection = new DatabaseToolCollection($container);
    }

    public function createDatabaseManager(string $omName = null, string $registryName = 'doctrine'): DatabaseManagerInterface
    {
        /** @var ManagerRegistry $registry */
        $registry = $this->container->get($registryName);

        $databaseManager = $this->databaseToolCollection->getManager($registry, $omName);

        return $databaseManager;
    }
}
