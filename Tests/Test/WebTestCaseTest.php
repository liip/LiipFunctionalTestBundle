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
    /** @var \Symfony\Bundle\FrameworkBundle\Client client */
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

        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
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
     * Check the failure message returned by assertStatusCode().
     */
    public function testAssertStatusCodeFail()
    {
        if (!interface_exists('Symfony\Component\Validator\Validator\ValidatorInterface')) {
            $this->markTestSkipped('The Symfony\Component\Validator\Validator\ValidatorInterface does not exist');
        }

        $this->loadFixtures(array());

        $path = '/';

        $this->client->request('GET', $path);

        try {
            $this->assertStatusCode(-1, $this->client);
        } catch (\PHPUnit_Framework_AssertionFailedError $e) {
            $this->assertStringStartsWith(
                'HTTP/1.1 200 OK',
                $e->getMessage()
            );

            $this->assertStringEndsWith(
                'Failed asserting that 200 matches expected -1.',
                $e->getMessage()
            );

            return;
        }

        $this->fail('Test failed.');
    }

    /**
     * Check the failure message returned by assertStatusCode().
     */
    public function testAssertStatusCodeException()
    {
        $this->loadFixtures(array());

        $path = '/user/2';

        $this->client->request('GET', $path);

        try {
            $this->assertStatusCode(-1, $this->client);
        } catch (\PHPUnit_Framework_AssertionFailedError $e) {
            $string = <<<'EOF'
No user found
Failed asserting that 404 matches expected -1.
EOF;
            $this->assertSame($string, $e->getMessage());

            return;
        }

        $this->fail('Test failed.');
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
     * Throw an Exception in the try/catch block and check the failure message
     * returned by assertStatusCode().
     */
    public function testIsSuccessfulException()
    {
        $this->loadFixtures(array());

        $response = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('getContent'))
            ->getMock();

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->throwException(new \Exception('foo')));

        try {
            $this->isSuccessful($response);
        } catch (\PHPUnit_Framework_AssertionFailedError $e) {
            $string = <<<'EOF'
The Response was not successful: foo
Failed asserting that false is true.
EOF;
            $this->assertSame($string, $e->getMessage());

            return;
        }

        $this->fail('Test failed.');
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
            'Doctrine\Common\DataFixtures\ProxyReferenceRepository',
            $repository
        );

        /** @var \Liip\FunctionalTestBundle\Tests\App\Entity\User $user1 */
        $user1 = $repository->getReference('user');

        $this->assertSame(1, $user1->getId());
        $this->assertSame('foo bar', $user1->getName());
        $this->assertSame('foo@bar.com', $user1->getEmail());
        $this->assertTrue($user1->getEnabled());

        // Load data from database
        $em = $this->client->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findAll();

        // There are 2 users.
        $this->assertSame(
            2,
            count($users)
        );

        /** @var \Liip\FunctionalTestBundle\Tests\App\Entity\User $user */
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
     * Load fixture which has a dependency.
     */
    public function testLoadDependentFixtures()
    {
        $fixtures = $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\Tests\App\DataFixtures\ORM\LoadDependentUserData',
        ));

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\ORMExecutor',
            $fixtures
        );

        $em = $this->client->getContainer()
            ->get('doctrine.orm.entity_manager');

        $users = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findAll();

        // The two files with fixtures have been loaded, there are 4 users.
        $this->assertSame(
            4,
            count($users)
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

        /** @var \Liip\FunctionalTestBundle\Tests\App\Entity\User $user */
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
     * Load nonexistent resource.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testLoadNonexistentFixturesFiles()
    {
        $this->loadFixtureFiles(array(
            '@LiipFunctionalTestBundle/Tests/App/DataFixtures/ORM/nonexistent.yml',
        ));
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

        /** @var \Liip\FunctionalTestBundle\Tests\App\Entity\User $user1 */
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

        /** @var \Liip\FunctionalTestBundle\Tests\App\Entity\User $user */
        $user = $em->getRepository('LiipFunctionalTestBundle:User')
            ->findOneBy(array(
                'id' => 1,
            ));

        $this->assertTrue(
            $user->getEnabled()
        );
    }

    /**
     * Use nelmio/alice with full path to the file without calling locateResource().
     */
    public function testLoadFixturesFilesPathsWithoutLocateResource()
    {
        $fixtures = $this->loadFixtureFiles(array(
            dirname(__FILE__).'/../App/DataFixtures/ORM/user.yml',
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
    }

    /**
     * Load nonexistent file with full path.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testLoadNonexistentFixturesFilesPaths()
    {
        $path = array('/nonexistent.yml');
        $this->loadFixtureFiles($path);
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

        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
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
     *
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
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
        $this->client->submit($form);

        $this->assertStatusCode(200, $this->client);

        $this->assertValidationErrors(array(''), $this->client->getContainer());
    }

    /**
     * Check the failure message returned by assertStatusCode()
     * when an invalid form is submitted.
     */
    public function testFormWithExceptionAssertStatusCode()
    {
        if (!interface_exists('Symfony\Component\Validator\Validator\ValidatorInterface')) {
            $this->markTestSkipped('The Symfony\Component\Validator\Validator\ValidatorInterface does not exist');
        }

        $this->loadFixtures(array());

        $path = '/form';

        $crawler = $this->client->request('GET', $path);

        $form = $crawler->selectButton('Submit')->form();

        $this->client->submit($form);

        try {
            $this->assertStatusCode(-1, $this->client);
        } catch (\PHPUnit_Framework_AssertionFailedError $e) {
            $string = <<<'EOF'
Unexpected validation errors:
+ children[name].data: This value should not be blank.

Failed asserting that 200 matches expected -1.
EOF;
            $this->assertSame($string, $e->getMessage());

            return;
        }

        $this->fail('Test failed.');
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
