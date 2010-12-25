<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Main\Tests\Functional;

use Bundle\Liip\FunctionalTestBundle\Test\Html5WebTestCase;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Output\Output;

/**
 * @author Lukas Smith
 * @author Daniel Barsotti
 */
class Html5Test extends Html5WebTestCase
{
    public function testIndex()
    {
        $content = $this->getPage('/');
        $this->assertIsValidHtml5($content, '/');
    }

    public function testBasicAuthentication()
    {
        $this->loadFixtures(array('App\Main\Tests\Fixtures\LoadUserData'));
        $client = $this->makeClient(true);

        $client->request('GET', '/');
        $this->assertEquals('Hello!', $client->getResponse()->getContent());
    }

    public function testListRoutesMissingDir()
    {
        $kernel = $this->createKernel();

        $command = new \Application\MyBundle\Command\FooCommand();
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $tester = new ApplicationTester($application);

        $tester->run(array(
            'command'  => $command->getFullName(),
            '..' => '..',
        ), array('interactive' => false, 'decorated' => false, 'verbosity' => Output::VERBOSITY_VERBOSE));

        $this->assertFalse('..');
    }
}
