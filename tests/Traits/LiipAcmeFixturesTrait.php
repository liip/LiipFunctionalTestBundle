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

namespace Liip\Acme\Tests\Traits;

use Liip\Acme\Tests\App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

trait LiipAcmeFixturesTrait
{
    public function schemaUpdate(): void
    {
        // Create database
        $kernel = $this->getContainer()->get('kernel');

        $application = new Application($kernel);

        $command = $application->find('doctrine:schema:update');
        $commandTester = new CommandTester($command);
        $return = $commandTester->execute([
            '--force' => true,
        ]);

        $this->assertSame(0, $return, $commandTester->getDisplay());
    }

    public function loadTestFixtures(): User
    {
        $user1 = new User();
        $user1->setId(1);
        $user1->setName('foo bar');
        $user1->setEmail('foo@bar.com');
        $user1->setPassword('12341234');
        $user1->setAlgorithm('plaintext');
        $user1->setEnabled(true);
        $user1->setConfirmationToken(null);

        $manager = $this->getContainer()->get('doctrine')->getManager();
        $manager->persist($user1);

        $user2 = clone $user1;

        $user2->setId(2);

        $manager->persist($user2);
        $manager->flush();

        return $user1;
    }
}
