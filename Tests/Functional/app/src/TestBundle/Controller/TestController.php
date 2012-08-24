<?php

namespace TestBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestController
{
    public function fooAction()
    {
        return new Response('I\'m the foo page');
    }

    public function secureAction(Request $request)
    {
        $user = $request->getUser();
        return new Response('Hi '.$user->getUsersame());
    }
}