<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Services\DatabaseTools;

use Doctrine\Bundle\PHPCRBundle\Initializer\InitializerManager;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\PHPCRPurger;
use Doctrine\ODM\PHPCR\DocumentManager;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class PHPCRDatabaseTool extends AbstractDatabaseTool
{
    /**
     * @var DocumentManager
     */
    protected $om;

    /**
     * @return string
     */
    public function getType()
    {
        return 'PHPCR';
    }

    private function getExecutor(PHPCRPurger $purger = null, InitializerManager $initializerManager = null)
    {
        $executorClass = class_exists('Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor')
            ? 'Doctrine\Bundle\PHPCRBundle\DataFixtures\PHPCRExecutor'
            : 'Doctrine\\Common\\DataFixtures\\Executor\\' . $this->getType() . 'Executor';

        return new $executorClass($this->om, $purger, $initializerManager);
    }

    private function getPurger()
    {
        return new PHPCRPurger($this->om);
    }

    public function loadFixtures(array $classNames)
    {
        $referenceRepository = new ProxyReferenceRepository($this->om);
        $cacheDriver = $this->om->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }

        if (empty($executor)) {
            $initManager = $this->container->has('doctrine_phpcr.initializer_manager')
                ? $this->container->get('doctrine_phpcr.initializer_manager')
                : null;

            $executor = $this->getExecutor($this->getPurger(), $initManager);

            $executor->setReferenceRepository($referenceRepository);
            $executor->purge();
        }

        $loader = $this->fixturesLoaderFactory->getFixtureLoader($classNames);
        $executor->execute($loader->getFixtures(), true);

        return $executor;
    }
}
