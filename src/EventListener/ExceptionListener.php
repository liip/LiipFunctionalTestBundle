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

namespace Liip\FunctionalTestBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

if (class_exists(ExceptionEvent::class) && !class_exists(GetResponseForExceptionEvent::class)) {
    class_alias(ExceptionEvent::class, GetResponseForExceptionEvent::class);
}

if (class_exists(RequestEvent::class) && !class_exists(GetResponseEvent::class)) {
    class_alias(RequestEvent::class, GetResponseEvent::class);
}

final class ExceptionListener implements EventSubscriberInterface
{
    /**
     * @var \Throwable|null
     */
    private $lastException;

    public function setException(GetResponseForExceptionEvent $event): void
    {
        $this->lastException = method_exists($event, 'getThrowable') ? $event->getThrowable() : $event->getException();
    }

    public function clearLastException(GetResponseEvent $event): void
    {
        if (HttpKernelInterface::MAIN_REQUEST === $event->getRequestType()) {
            $this->lastException = null;
        }
    }

    public function getLastException(): ?\Throwable
    {
        return $this->lastException;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['setException', 99999],
            KernelEvents::REQUEST => ['clearLastException', 99999],
        ];
    }
}
