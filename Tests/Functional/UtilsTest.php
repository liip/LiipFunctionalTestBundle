<?php

namespace Liip\FunctionalTestBundle\Tests\Functional;

use Liip\FunctionalTestBundle\Tests\Functional\app\BaseFunctionalTestCase; 

class UtilsTest extends BaseFunctionalTestCase
{

    function testGetKernelClass()
    {
    }

    function testGetServiceMockBuilder()
    {
    }

    function testRunCommand()
    {
    }

    function testGetContainer()
    {
        $this->assertTrue(is_subclass_of($this->getContainer(), 'Symfony\Component\DependencyInjection\Container'));
    }

    function testGetUrl()
    {
    }

    function testIsSuccessful()
    {
    }

    function testFetchContent()
    {
    }

    function testFetchCrawler()
    {
    }
}

