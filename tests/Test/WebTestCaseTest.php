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

namespace Liip\Acme\Tests\Test;

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Liip\Acme\Tests\App\AppKernel;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use PHPUnit\Framework\AssertionFailedError;

/**
 * @IgnoreAnnotation("depends")
 * @IgnoreAnnotation("expectedException")
 */
class WebTestCaseTest extends WebTestCase
{
    protected function setUp(): void
    {
        static::$class = AppKernel::class;
    }

    public static function getKernelClass()
    {
        return AppKernel::class;
    }

    /**
     * Call methods from the parent class.
     */
    public function testGetContainer(): void
    {
        $this->assertInstanceOf(
            'Symfony\Component\DependencyInjection\ContainerInterface',
            $this->getContainer()
        );
    }

    public function testMakeClient(): void
    {
        $this->assertInstanceOf(
            'Symfony\Bundle\FrameworkBundle\Client',
            static::makeClient()
        );
    }

    public function testGetUrl(): void
    {
        $path = $this->getUrl(
            'liipfunctionaltestbundle_user',
            [
                'userId' => 1,
                'get_parameter' => 'abc',
            ]
        );

        $this->assertIsString($path);

        $this->assertSame($path, '/user/1?get_parameter=abc');
    }

    /**
     * Call methods from Symfony to ensure the Controller works.
     */
    public function testIndex(): void
    {
        $path = '/';

        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = static::makeClient()->request('GET', $path);

        $this->assertSame(
            1,
            $crawler->filter('html > body')->count()
        );

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
    public function testIndexAssertStatusCode(): void
    {
        $path = '/';

        $client = static::makeClient();

        $client->request('GET', $path);

        $this->assertStatusCode(200, $client);
    }

    /**
     * Check the failure message returned by assertStatusCode().
     */
    public function testAssertStatusCodeFail(): void
    {
        $path = '/';

        $client = static::makeClient();
        $client->request('GET', $path);

        try {
            $this->assertStatusCode(-1, $client);
        } catch (AssertionFailedError $e) {
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
    public function testAssertStatusCodeException(): void
    {
        $path = '/9999';

        $client = static::makeClient();
        $client->request('GET', $path);

        try {
            $this->assertStatusCode(-1, $client);
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('No route found', $e->getMessage());
            $this->assertStringContainsString('Symfony\Component\HttpKernel\EventListener\RouterListener->onKernelRequest(', $e->getMessage());
            $this->assertStringContainsString('Failed asserting that 404 matches expected -1.', $e->getMessage());

            return;
        }

        $this->fail('Test failed.');
    }

    /**
     * @depends testIndex
     */
    public function testIndexIsSuccesful(): void
    {
        $path = '/';

        $client = static::makeClient();
        $client->request('GET', $path);

        $this->isSuccessful($client->getResponse());
    }

    /**
     * @depends testIndex
     */
    public function testIndexFetchCrawler(): void
    {
        $path = '/';

        $crawler = $this->fetchCrawler($path);

        $this->assertInstanceOf(
            'Symfony\Component\DomCrawler\Crawler',
            $crawler
        );

        $this->assertSame(
            1,
            $crawler->filter('html > body')->count()
        );

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
    public function testIndexFetchContent(): void
    {
        $path = '/';

        $content = $this->fetchContent($path);

        $this->assertIsString($content);

        $this->assertStringContainsString(
            '<h1>LiipFunctionalTestBundle</h1>',
            $content
        );
    }

    public function test404Error(): void
    {
        $path = '/missing_page';

        $client = static::makeClient();
        $client->request('GET', $path);

        $this->assertStatusCode(404, $client);

        $this->isSuccessful($client->getResponse(), false);
    }

    /**
     * Throw an Exception in the try/catch block and check the failure message
     * returned by assertStatusCode().
     */
    public function testIsSuccessfulException(): void
    {
        $response = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
            ->disableOriginalConstructor()
            ->setMethods(['getContent'])
            ->getMock();

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->throwException(new \Exception('foo')));

        try {
            $this->isSuccessful($response);
        } catch (AssertionFailedError $e) {
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
     * Form.
     */
    public function testForm(): void
    {
        $path = '/form';

        $client = static::makeClient();
        $crawler = $client->request('GET', $path);

        $this->assertStatusCode(200, $client);

        $form = $crawler->selectButton('Submit')->form();
        $crawler = $client->submit($form);

        $this->assertStatusCode(200, $client);

        $this->assertValidationErrors(['children[name].data'], $client->getContainer());

        // Try again with the fields filled out.
        $form = $crawler->selectButton('Submit')->form();
        $form->setValues(['form[name]' => 'foo bar']);
        $crawler = $client->submit($form);

        $this->assertStatusCode(200, $client);

        $this->assertStringContainsString(
            'Name submitted.',
            $crawler->filter('div.flash-notice')->text()
        );
    }

    /**
     * Ensure form validation helpers still work with embedded controllers.
     *
     * @see https://github.com/liip/LiipFunctionalTestBundle/issues/273
     */
    public function testFormWithEmbed(): void
    {
        $path = '/form-with-embed';

        $client = static::makeClient();
        $crawler = $client->request('GET', $path);

        $this->assertStatusCode(200, $client);

        $form = $crawler->selectButton('Submit')->form();
        $crawler = $client->submit($form);

        $this->assertStatusCode(200, $client);

        $this->assertValidationErrors(['children[name].data'], $client->getContainer());

        // Try again with the fields filled out.
        $form = $crawler->selectButton('Submit')->form();
        $form->setValues(['form[name]' => 'foo bar']);
        $crawler = $client->submit($form);

        $this->assertStatusCode(200, $client);

        $this->assertStringContainsString(
            'Name submitted.',
            $crawler->filter('div.flash-notice')->text()
        );
    }

    /**
     * @depends testForm
     */
    public function testFormWithException(): void
    {
        $path = '/form';

        $client = static::makeClient();
        $crawler = $client->request('GET', $path);

        $this->assertStatusCode(200, $client);

        $form = $crawler->selectButton('Submit')->form();
        $client->submit($form);

        $this->assertStatusCode(200, $client);

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);

        $this->assertValidationErrors([''], $client->getContainer());
    }

    /**
     * Check the failure message returned by assertStatusCode()
     * when an invalid form is submitted.
     */
    public function testFormWithExceptionAssertStatusCode(): void
    {
        $path = '/form';

        $client = static::makeClient();
        $crawler = $client->request('GET', $path);

        $form = $crawler->selectButton('Submit')->form();

        $client->submit($form);

        try {
            $this->assertStatusCode(-1, $client);
        } catch (AssertionFailedError $e) {
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
    public function testJsonIsSuccessful(): void
    {
        $path = '/json';

        $client = static::makeClient();
        $client->request('GET', $path);

        $this->isSuccessful(
            $client->getResponse(),
            true,
            'application/json'
        );
    }
}
