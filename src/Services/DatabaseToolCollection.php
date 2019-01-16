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

use Doctrine\Common\Annotations\AnnotationReader;
use Liip\FunctionalTestBundle\Annotations\DisableDatabaseCache;
use Liip\FunctionalTestBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
final class DatabaseToolCollection
{
    private $container;

    private $annotationReader;

    /**
     * @var AbstractDatabaseTool[][]
     */
    private $items = [];

    public function __construct(ContainerInterface $container, AnnotationReader $annotationReader)
    {
        $this->container = $container;
        $this->annotationReader = $annotationReader;
    }

    public function add(AbstractDatabaseTool $databaseTool): void
    {
        $this->items[$databaseTool->getType()][$databaseTool->getDriverName()] = $databaseTool;
    }

    public function get($omName = null, $registryName = 'doctrine', $purgeMode = null, WebTestCase $webTestCase): AbstractDatabaseTool
    {
        /** @var ManagerRegistry $registry */
        $registry = $this->container->get($registryName);
        $driverName = ('ORM' === $registry->getName()) ? $registry->getConnection()->getDriver()->getName() : 'default';

        $databaseTool = isset($this->items[$registry->getName()][$driverName])
            ? $this->items[$registry->getName()][$driverName]
            : $this->items[$registry->getName()]['default'];

        $databaseTool->setRegistry($registry);
        $databaseTool->setObjectManagerName($omName);
        $databaseTool->setPurgeMode($purgeMode);
        $databaseTool->setWebTestCase($webTestCase);

        $databaseTool->setDatabaseCacheEnabled($this->isCacheEnabled());

        return $databaseTool;
    }

    public function isCacheEnabled(): bool
    {
        foreach (debug_backtrace() as $step) {
            if ('test' === substr($step['function'], 0, 4)) { //TODO: handle tests with the @test annotation
                $annotations = $this->annotationReader->getMethodAnnotations(
                    new \ReflectionMethod($step['class'], $step['function'])
                );

                foreach ($annotations as $annotationClass) {
                    if ($annotationClass instanceof DisableDatabaseCache) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
