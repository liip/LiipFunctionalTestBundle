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

use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class MongoDBDatabaseTool extends AbstractDatabaseTool
{
    /**
     * @return string
     */
    public function getType()
    {
        return 'MongoDB';
    }

    private function getExecutor(MongoDBPurger $purger = null)
    {
        return new MongoDBExecutor($this->om, $purger);
    }

    private function getPurger()
    {
        return new MongoDBPurger($this->om);
    }

    public function loadFixtures(array $classNames)
    {
        $referenceRepository = new ProxyReferenceRepository($this->om);
        $cacheDriver = $this->om->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }

        if (empty($executor)) {
            $executor = $this->getExecutor($this->getPurger());
            $executor->setReferenceRepository($referenceRepository);
            $executor->purge();
        }

        $loader = $this->fixturesLoaderFactory->getFixtureLoader($classNames);
        $executor->execute($loader->getFixtures(), true);

        return $executor;
    }
}
