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

class Html5WebTestCaseFakeTest extends Html5WebTestCaseFake
{
    /** @var \Symfony\Bundle\FrameworkBundle\Client client */
    private $client = null;

    public function setUp()
    {
        $this->client = static::makeClient();

        $this->loadFixtures(array());
    }

    public function testIndex()
    {
        $path = '/';

        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
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

    public function testSnippet()
    {
        $this->assertIsValidHtml5Snippet(
            '<p>Hello World!</p>'
        );
    }

    public function testSetHtml5Wrapper()
    {
        $this->setHtml5Wrapper('foo bar');

        $this->assertSame(
            'foo bar',
            $this->html5Wrapper
        );
    }
}
