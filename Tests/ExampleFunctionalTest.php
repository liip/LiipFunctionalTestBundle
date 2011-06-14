<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FooBundle\Tests;

use Liip\FunctionalTestBundle\Test\WebTestCase;

class ExampleFunctionalTest extends WebTestCase
{
    public function testLoginPage()
    {
        $content = $this->fetchContent('/', 'GET', false);
        $this->assertContains('login', $content);
    }

    /**
     * Example using LiipFunctionalBundle the fixture loader
     */
    public function testUserInfo()
    {
        $this->loadFixtures(array('Liip\FunctionalTestBundle\Tests\Fixtures\LoadUserData'));

        // test if the user's name is shown on the start page if the user is authenticated authenticated
        $content = $this->fetchContent('/', 'GET', true);
        $this->assertContains('foo bar', $content);

        // check if the logout button is shown
        $this->assertContains('logout', $content);
    }
}
