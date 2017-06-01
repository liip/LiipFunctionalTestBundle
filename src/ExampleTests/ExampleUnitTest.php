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
use Liip\FunctionalTestBundle\Test\WebTestCase;

/**
 * @author Lukas Smith
 * @author Daniel Barsotti
 */
class ExampleUnitTest extends WebTestCase
{
    /**
     * Example using LiipFunctionalBundle the service mock builder.
     */
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
