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

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class WebTestCaseTest.
 *
 * This class doesn't inherit from Liip\FunctionalTestBundle\Test\WebTestCase
 * and use the services from this Bundle.
 */
class ServicesTest extends WebTestCase
{
    /** @var \Symfony\Bundle\FrameworkBundle\Client client */
    private $client = null;

    public function setUp()
    {
        $this->client = static::createClient();
    }

    public function testIndexIsSuccesful()
    {
        $path = '/';

        $this->client->request('GET', $path);

        $this->client->getContainer()
            ->get('liip_functional_test.http_assertions')
            ->isSuccessful($this->client->getResponse());
    }

    public function test404Error()
    {
        $path = '/missing_page';

        $this->client->request('GET', $path);

        $httpAssertions = $this->client->getContainer()
            ->get('liip_functional_test.http_assertions');

        $httpAssertions->assertStatusCode(404, $this->client);

        $httpAssertions->isSuccessful($this->client->getResponse(), false);
    }

    /**
     * Form.
     */
    public function testForm()
    {
        if (!interface_exists('Symfony\Component\Validator\Validator\ValidatorInterface')) {
            $this->markTestSkipped('The Symfony\Component\Validator\Validator\ValidatorInterface does not exist');
        }

        $path = '/form';

        $crawler = $this->client->request('GET', $path);

        $httpAssertions = $this->client->getContainer()
            ->get('liip_functional_test.http_assertions');

        $httpAssertions->assertStatusCode(200, $this->client);

        $form = $crawler->selectButton('Submit')->form();
        $crawler = $this->client->submit($form);

        $httpAssertions->assertStatusCode(200, $this->client);

        $httpAssertions->assertValidationErrors(array('children[name].data'), $this->client->getContainer());

        // Try again with the fields filled out.
        $form = $crawler->selectButton('Submit')->form();
        $form->setValues(array('form[name]' => 'foo bar'));
        $crawler = $this->client->submit($form);

        $httpAssertions->assertStatusCode(200, $this->client);

        $this->assertContains(
            'Name submitted.',
            $crawler->filter('div.flash-notice')->text()
        );
    }

    /**
     * @depends testForm
     */
    public function testFormWithException()
    {
        if (!interface_exists('Symfony\Component\Validator\Validator\ValidatorInterface')) {
            $this->markTestSkipped('The Symfony\Component\Validator\Validator\ValidatorInterface does not exist');
        }

        $path = '/form';

        $crawler = $this->client->request('GET', $path);

        $httpAssertions = $this->client->getContainer()
            ->get('liip_functional_test.http_assertions');

        $httpAssertions->assertStatusCode(200, $this->client);

        $form = $crawler->selectButton('Submit')->form();
        $this->client->submit($form);

        $httpAssertions->assertStatusCode(200, $this->client);

        try {
            $httpAssertions->assertValidationErrors(array(''), $this->client->getContainer());
        } catch (\PHPUnit_Framework_ExpectationFailedException $expected) {
            return;
        }

        $this->fail('PHPUnit_Framework_ExpectationFailedException has not been raised');
    }

    /**
     * Call isSuccessful() with "application/json" content type.
     */
    public function testJsonIsSuccesful()
    {
        $this->client = static::createClient();

        $path = '/json';

        $this->client->request('GET', $path);

        $this->client->getContainer()
            ->get('liip_functional_test.http_assertions')
            ->isSuccessful(
            $this->client->getResponse(),
            true,
            'application/json'
        );
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->client = null;
    }
}
