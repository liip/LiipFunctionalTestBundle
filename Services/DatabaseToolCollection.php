<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Services;

use Liip\FunctionalTestBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class DatabaseToolCollection
{
    /**
     * @var AbstractDatabaseTool[][]
     */
    private $items = [];

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function add(AbstractDatabaseTool $databaseTool)
    {
        $this->items[$databaseTool->getType()][$databaseTool->getDatabasePlatform()] = $databaseTool;
    }

    /**
     * @return AbstractDatabaseTool
     */
    public function get($omName = null, $registryName = 'doctrine', $purgeMode = null, WebTestCase $webTestCase)
    {
        /** @var ManagerRegistry $registry */
        $registry = $this->container->get($registryName);
        $driverName = ('PHPCR' !== $registry->getName()) ? $registry->getConnection()->getDatabasePlatform()->getName() : 'default';

        $databaseTool = isset($this->items[$registry->getName()][$driverName])
            ? $this->items[$registry->getName()][$driverName]
            : $this->items[$registry->getName()]['default'];

        $databaseTool->setRegistry($registry);
        $databaseTool->setObjectManagerName($omName);
        $databaseTool->setPurgeMode($purgeMode);
        $databaseTool->setWebTestCase($webTestCase);

        return $databaseTool;
    }
}
