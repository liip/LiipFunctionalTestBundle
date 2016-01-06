<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Tests\Command;

use Liip\FunctionalTestBundle\Test\WebTestCase;

class CommandTest extends WebTestCase
{
    public function testRunCommand()
    {
        $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\DataFixtures\ORM\LoadUserData',
        ));

        $this->verbosityLevel = 'debug';
        $display = $this->runCommand('command:test');

        $this->assertContains('Name: foo bar', $display);
        $this->assertContains('Email: foo@bar.com', $display);
    }
}
