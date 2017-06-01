<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Tests\AppConfigPhpcr\DataFixtures\PHPCR;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Liip\FunctionalTestBundle\Tests\AppConfigPhpcr\Document\Task;

class LoadTaskData implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        if (!$manager instanceof DocumentManager) {
            $class = get_class($manager);
            throw new \RuntimeException("Fixture requires a PHPCR ODM DocumentManager instance, instance of '$class' given.");
        }

        $rootTask = $manager->find(null, '/');

        if (!$rootTask) {
            throw new \Exception('Could not find / document!');
        }

        $task = new Task();
        $task->setDescription('Finish CMF project');
        $task->setParentDocument($rootTask);

        $manager->persist($task);

        $manager->flush();
    }
}
