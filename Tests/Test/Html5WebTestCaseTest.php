<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Tests\Test;

use Liip\FunctionalTestBundle\Test\Html5WebTestCase;

class Html5WebTestCaseTest extends Html5WebTestCase
{
    private $client = null;

    public function setUp()
    {
        $this->client = static::makeClient();
    }

    public function testIndex()
    {
        $this->loadFixtures(array());

        $path = '/';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );

        $this->assertIsValidHtml5(
            $this->client->getResponse()->getContent()
        );
    }
}
