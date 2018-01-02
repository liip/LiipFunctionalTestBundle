<?php

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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Liip\FunctionalTestBundle\Tests\App\Entity\User;

class LoadUserData extends AbstractFixture implements FixtureInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var \Liip\FunctionalTestBundle\Tests\App\Entity\User $user */
        $user = new User();
        $user->setId(1);
        $user->setName('foo bar');
        $user->setEmail('foo@bar.com');
        $user->setPassword('12341234');
        $user->setAlgorithm('plaintext');
        $user->setEnabled(true);
        $user->setConfirmationToken(null);

        $manager->persist($user);
        $manager->flush();

        $this->addReference('user', $user);

        $user = clone $this->getReference('user');

        $user->setId(2);

        $manager->persist($user);
        $manager->flush();
    }
}
