<?php
namespace Liip\FunctionalTestBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class LoginTestUserListener implements EventSubscriberInterface
{
    const FAKEPASS = 'fakePass_42!';

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        // @TODO make the query parameter name configurable
        $login = $request->query->get('_login');

        if(is_null($login)) {
            return;
        }

        $request->headers->add(
            [
                'PHP_AUTH_USER' => $login,
                'PHP_AUTH_PW'   => self::FAKEPASS,
            ]
        );
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 255]];
    }
}