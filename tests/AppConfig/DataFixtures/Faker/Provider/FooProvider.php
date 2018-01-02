<?php

/*
* This file is part of the Liip/FunctionalTestBundle
*
* (c) Lukas Kahwe Smith <smith@pooteeweet.org>
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Liip\FunctionalTestBundle\Tests\AppConfig\DataFixtures\Faker\Provider;

class FooProvider
{
    public static function foo($str)
    {
        return 'foo'.$str;
    }
}
