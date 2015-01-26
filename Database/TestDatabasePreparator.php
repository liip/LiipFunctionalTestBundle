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

use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;

class TestDatabasePreparator
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $type
     * @param ObjectManager $om
     * @param int $purgeMode see Doctrine\Common\DataFixtures\Purger\ORMPurger
     * @return AbstractExecutor
     */
    private function getExecutor($type, ObjectManager $om, $purgeMode = null)
    {
        $executorClass = 'PHPCR' === $type && class_exists('Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor')
        ? 'Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor'
            : 'Doctrine\\Common\\DataFixtures\\Executor\\'.$type.'Executor';

        $purgerClass = 'Doctrine\\Common\\DataFixtures\\Purger\\'.$type.'Purger';

        if ('PHPCR' === $type) {
            $purger = new $purgerClass($om);
            $initManager = $this->container->has('doctrine_phpcr.initializer_manager')
                ? $this->container->get('doctrine_phpcr.initializer_manager')
                : null;

            return new $executorClass($om, $purger, $initManager);
        }

        if($type === 'ORM' && $om->getConnection()->getDriver() instanceof SqliteDriver) {
            return new $executorClass($om);
        }

        $purger = new $purgerClass();
        if (null !== $purgeMode) {
            $purger->setPurgeMode($purgeMode);
        }

        return new $executorClass($om, $purger);
    }

    public function getExecutorWithReferenceRepository($type, ObjectManager $om, $purgeMode = null)
    {
        $executor = $this->getExecutor($type, $om, $purgeMode);
        $referenceRepository = new ProxyReferenceRepository($om);
        $executor->setReferenceRepository($referenceRepository);

        return $executor;
    }
}