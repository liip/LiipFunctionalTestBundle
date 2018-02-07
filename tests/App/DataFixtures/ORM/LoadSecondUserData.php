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

namespace Liip\FunctionalTestBundle\Tests\App\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Liip\FunctionalTestBundle\Tests\App\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadSecondUserData extends AbstractFixture implements FixtureInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        /** @var \Liip\FunctionalTestBundle\Tests\App\Entity\User $user */
        $user = new User();
        $user->setName('bar foo');
        $user->setEmail('bar@foo.com');
        $user->setPassword('12341234');
        $user->setAlgorithm('plaintext');
        $user->setEnabled(true);
        $user->setConfirmationToken(null);

        $manager->persist($user);
        $manager->flush();
    }
}
