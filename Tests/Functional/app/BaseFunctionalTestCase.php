<?php

namespace Liip\FunctionalTestBundle\Tests\Functional\app;

use Liip\FunctionalTestBundle\Test\WebTestCase;

class BaseFunctionalTestCase extends WebTestCase
{
    static protected function createKernel(array $options = array())
    {
        return new AppKernel(
            isset($options['config']) ? $options['config'] : 'default.yml'
        );
    }
}