<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Liip\FunctionalTestBundle\Database\TestDatabasePreparator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class is experimental!
 */
class PrepareDatabaseListener implements EventSubscriberInterface
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $classNames = $this->getFixturesClassNames($event->getRequest());
        if (is_null($classNames)) {
            return;
        }

        // @TODO Make the registry service name configurable
        $registry = $this->container->get('doctrine');

        $dbPreparator = new TestDatabasePreparator($this->container, $registry);
        $dbPreparator->loadFixtures($classNames);
    }

    private function getFixturesClassNames(Request $request)
    {
        // @TODO Make the query parameter name configurable
        $fixturesParam = $request->query->get('_fixtures');
        if (is_null($fixturesParam)) {
            return null;
        }

        $classNamesUnfiltered = explode(',', $fixturesParam);

        return array_filter($classNamesUnfiltered);
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 255]];
    }
}
