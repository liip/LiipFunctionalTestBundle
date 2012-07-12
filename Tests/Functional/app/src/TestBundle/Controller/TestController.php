<?php

namespace TestBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

class TestController
{
    public function fooAction()
    {
      return new Response('I\'m the foo page');
    }
}