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

namespace Liip\Acme\Tests\App\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Liip\Acme\Tests\App\Entity\User;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class LoadUserWithServiceData extends AbstractFixture implements FixtureInterface
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $this->tokenStorage->setToken('id', 'value');

        /** @var \Liip\Acme\Tests\App\Entity\User $user */
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
