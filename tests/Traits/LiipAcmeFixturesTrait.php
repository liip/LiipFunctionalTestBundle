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
    public function loadTestFixtures(): User
    {
        $this->resetSchema();

        $user1 = new User();
        $user1->setId(1);
        $user1->setName('foo bar');
        $user1->setEmail('foo@example');
        $user1->setPassword('12341234');
        $user1->setAlgorithm('plaintext');
        $user1->setEnabled(true);
        $user1->setConfirmationToken(null);

        $manager = $this->getContainer()->get('doctrine')->getManager();
        $manager->persist($user1);

        $user2 = clone $user1;

        $user2->setId(2);
        $user2->setName('alice bob');
        $user2->setEmail('alice@example.com');

        $manager->persist($user2);
        $manager->flush();

        return $user1;
    }

    private function resetSchema(): void
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

        $manager = $this->getContainer()->get('doctrine')->getManager();

        $connection = $manager->getConnection();
        $connection->query('DELETE FROM liip_user');
    }
}
