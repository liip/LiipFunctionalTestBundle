<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FooBundle\Tests\Fixtures;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * @author Lea Haensenberger
 */
class LoadUserData implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $user = new \Liip\FooBundle\Entity\User();
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
    }
}
