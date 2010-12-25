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

/**
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
}
