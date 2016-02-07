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

use Liip\FunctionalTestBundle\Test\WebTestCase;

class WebTestCaseTest extends WebTestCase
{
    private $client = null;

    public function setUp()
    {
        $this->client = static::makeClient();
    }

    /**
     * Call methods from the parent class.
     */
    public function testGetContainer()
    {
        $this->assertInstanceOf(
            'Symfony\Component\DependencyInjection\ContainerInterface',
            $this->getContainer()
        );
    }

    public function testMakeClient()
    {
        $this->assertInstanceOf(
            'Symfony\Bundle\FrameworkBundle\Client',
            static::makeClient()
        );
    }

    public function testGetUrl()
    {
        $path = $this->getUrl(
            'liipfunctionaltestbundle_user',
            array(
                'userId' => 1,
                'get_parameter' => 'abc',
            )
        );

        $this->assertInternalType('string', $path);

        $this->assertSame($path, '/user/1?get_parameter=abc');
    }

    /**
     * Call methods from Symfony to ensure the Controller works.
     */
    public function testIndex()
    {
        $path = '/';

        $crawler = $this->client->request('GET', $path);

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'Not logged in.',
            $crawler->filter('p#user')->text()
        );

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );
    }

    /**
     * Call methods from the parent class.
     */

    /**
     * @depends testIndex
     */
    public function testIndexAssertStatusCode()
    {
        $this->loadFixtures(array());

        $path = '/';

        $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);
    }

    /**
     * @depends testIndex
     */
    public function testIndexIsSuccesful()
    {
        $this->loadFixtures(array());

        $path = '/';

        $this->client->request('GET', $path);

        $this->isSuccessful($this->client->getResponse());
    }

    /**
     * @depends testIndex
     */
    public function testIndexFetchCrawler()
    {
        $this->loadFixtures(array());

        $path = '/';

        $crawler = $this->fetchCrawler($path);

        $this->assertInstanceOf(
            'Symfony\Component\DomCrawler\Crawler',
            $crawler
        );

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'Not logged in.',
            $crawler->filter('p#user')->text()
        );

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );
    }

    /**
     * @depends testIndex
     */
    public function testIndexFetchContent()
    {
        $this->loadFixtures(array());

        $path = '/';

        $content = $this->fetchContent($path);

        $this->assertInternalType('string', $content);

        $this->assertContains(
            '<h1>LiipFunctionalTestBundle</h1>',
            $content
        );
    }

    public function test404Error()
    {
        $this->loadFixtures(array());

        $path = '/missing_page';

        $this->client->request('GET', $path);

        $this->assertStatusCode(404, $this->client);

        $this->isSuccessful($this->client->getResponse(), false);
    }

    /**
     * Data fixtures.
     */
    public function testLoadEmptyFixtures()
    {
        $fixtures = $this->loadFixtures(array());

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );
    }

    public function testLoadFixtures()
    {
        $fixtures = $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\Tests\App\DataFixtures\ORM\LoadUserData',
        ));

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $repository = $fixtures->getReferenceRepository();

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $user1 = $repository->getReference('user');

        $this->assertSame(1, $user1->getId());
        $this->assertSame('foo bar', $user1->getName());
        $this->assertSame('foo@bar.com', $user1->getEmail());
        $this->assertTrue($user1->getEnabled());

        // Load data from database
        $em = $this->client->getContainer()
            ->get('doctrine.orm.entity_manager');

        $user = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy(array(
                'id' => 1,
            ));

        $this->assertSame(
            'foo@bar.com',
            $user->getEmail()
        );

        $this->assertTrue(
            $user->getEnabled()
        );
    }

    /**
     * Use nelmio/alice.
     */
    public function testLoadFixturesFiles()
    {
        $fixtures = $this->loadFixtureFiles(array(
            '@LiipFunctionalTestBundle/Tests/App/DataFixtures/ORM/user.yml',
        ));

        $this->assertInternalType(
            'array',
            $fixtures
        );

        // 10 users are loaded
        $this->assertCount(
            10,
            $fixtures
        );

        $em = $this->client->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findAll();

        $this->assertSame(
            10,
            count($users)
        );

        $user = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy(array(
                'id' => 1,
            ));

        $this->assertTrue(
            $user->getEnabled()
        );

        $user = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy(array(
                'id' => 10,
            ));

        $this->assertTrue(
            $user->getEnabled()
        );
    }

    /**
     * Use nelmio/alice with full path to the file.
     */
    public function testLoadFixturesFilesPaths()
    {
        $fixtures = $this->loadFixtureFiles(array(
            $this->client->getContainer()->get('kernel')->locateResource(
                '@LiipFunctionalTestBundle/Tests/App/DataFixtures/ORM/user.yml'
            ),
        ));

        $this->assertInternalType(
            'array',
            $fixtures
        );

        // 10 users are loaded
        $this->assertCount(
            10,
            $fixtures
        );

        $user1 = $fixtures['id1'];

        $this->assertInternalType('string', $user1->getUsername());
        $this->assertTrue($user1->getEnabled());

        $em = $this->client->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findAll();

        $this->assertSame(
            10,
            count($users)
        );

        $user = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy(array(
                'id' => 1,
            ));

        $this->assertTrue(
            $user->getEnabled()
        );
    }

    public function testUserWithFixtures()
    {
        $fixtures = $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\Tests\App\DataFixtures\ORM\LoadUserData',
        ));

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $path = '/user/1';

        $this->client->enableProfiler();

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        if ($profile = $this->client->getProfile()) {
            // One query
            $this->assertEquals(1,
                $profile->getCollector('db')->getQueryCount());
        } else {
            $this->markTestIncomplete(
                'Profiler is disabled.'
            );
        }

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'Not logged in.',
            $crawler->filter('p#user')->text()
        );

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );

        $this->assertSame(
            'Name: foo bar',
            $crawler->filter('div#content p')->eq(0)->text()
        );
        $this->assertSame(
            'Email: foo@bar.com',
            $crawler->filter('div#content p')->eq(1)->text()
        );
    }

    /**
     * Form.
     */
    public function testForm()
    {
        if (!interface_exists('Symfony\Component\Validator\Validator\ValidatorInterface')) {
            $this->markTestSkipped('The Symfony\Component\Validator\Validator\ValidatorInterface does not exist');
        }

        $this->loadFixtures(array());

        $path = '/form';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        $form = $crawler->selectButton('Submit')->form();
        $crawler = $this->client->submit($form);

        $this->assertStatusCode(200, $this->client);

        $this->assertValidationErrors(array('children[name].data'), $this->client->getContainer());

        // Try again with the fields filled out.
        $form = $crawler->selectButton('Submit')->form();
        $form->setValues(array('form[name]' => 'foo bar'));
        $crawler = $this->client->submit($form);

        $this->assertStatusCode(200, $this->client);

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

        $this->loadFixtures(array());

        $path = '/form';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        $form = $crawler->selectButton('Submit')->form();
        $crawler = $this->client->submit($form);

        $this->assertStatusCode(200, $this->client);

        try {
            $this->assertValidationErrors(array(''), $this->client->getContainer());
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
        $this->loadFixtures(array());

        $this->client = static::makeClient();

        $path = '/json';

        $this->client->request('GET', $path);

        $this->isSuccessful(
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
