<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Doctrine\Common\Persistence\ObjectManager;
use Liip\FunctionalTestBundle\Entity\User;

class LoadUserData extends AbstractFixture implements OrderedFixtureInterface, FixtureInterface, ContainerAwareInterface
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
        $user = new User();
        $user->setId(1);
        $user->setName('foo bar');
        $user->setEmail('foo@bar.com');
        // Set according to your security context settings
        $encoder = new MessageDigestPasswordEncoder('sha1', true, 3);
        $user->setPassword($encoder->encodePassword('12341234', $user->getSalt()));
        $user->setAlgorithm('sha1');
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

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 0;
    }
}
