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

use Liip\FunctionalTestBundle\Controller\DefaultController;
use Liip\FunctionalTestBundle\Test\Html5WebTestCase;

/**
 * @author Lukas Smith
 * @author Daniel Barsotti
 */
class ExampleHtml5Test extends Html5WebTestCase
{
    protected $kernelDir = '/app/main';

    public function testIndex()
    {
        $content = $this->fetchContent('/');
        $this->assertIsValidHtml5($content, '/');
    }

    public function testBasicAuthentication()
    {
        $this->loadFixtures(array('Liip\FooBundle\Tests\Fixtures\LoadUserData'));

        $content = $this->fetchContent('/', 'GET', true);
        $this->assertEquals('Hello foo!', $content);
    }

    public function testGenerateInMissingDir()
    {
        $this->runCommand('main:generate-html', array('output-dir' => './doesntexist'));
        $this->assertFalse(file_exists($this->dir.'/index.html'));
    }

    public function testIndexAction()
    {
        $view = $this->getServiceMockBuilder('FooView')->getMock();

        $view->expects($this->once())
            ->method('setTemplate')
            ->with('FooBundle:Default:index.twig')
            ->will($this->returnValue(null))
        ;

        $view->expects($this->once())
            ->method('handle')
            ->with()
            ->will($this->returnValue('success'))
        ;

        $controller = new DefaultController($view);

        $this->assertEquals('success', $controller->indexAction());
    }
}
