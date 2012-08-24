<?php

namespace Liip\FunctionalTestBundle\Tests\Functional;

use Liip\FunctionalTestBundle\Tests\Functional\app\BaseFunctionalTestCase;

use Symfony\Component\HttpFoundation\Response; 

class UtilsTest extends BaseFunctionalTestCase
{

    function testGetKernelClass()
    {
        $this->markTestSkipped('Not working due to inheritance issue... Need to look deeper');
        $this->assertEquals('toto', $this->getKernelClass());
    }

    function testGetServiceMockBuilder()
    {
        $testService = $this->getServiceMockBuilder('test_service')->getMock();
        $this->assertObjectHasAttribute('bar', $testService);
    }

    function testRunCommand()
    {
        $output = $this->runCommand('test:foo');
        $this->assertEquals("I'm the test:foo command!", $output);
    }

    function testGetContainer()
    {
        $this->assertTrue(is_subclass_of($this->getContainer(), 'Symfony\Component\DependencyInjection\Container'));
    }

    function testGetUrl()
    {
        $this->assertEquals('/foo', $this->getUrl('foo_page'));
    }

    function testIsSuccessfulUseInSuccessCase()
    {
        $response = new Response('OK', 200);
        $this->isSuccessful($response, true);
    }

    function testIsSuccessfulUseInFailureCase()
    {
        $response = new Response('KO', 500);
        $this->isSuccessful($response, false);
    }

    function testFetchContent()
    {
        $content = $this->fetchContent('/foo');
        $this->assertEquals("I'm the foo page", $content);
    }

    function testFetchCrawler()
    {
        $crawler = $this->fetchCrawler('/foo');
        $this->assertInstanceOf('\Symfony\Component\DomCrawler\Crawler', $crawler);
        $this->assertEquals("I'm the foo page", $crawler->text());
    }
}

