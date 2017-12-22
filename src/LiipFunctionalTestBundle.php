<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Liip\FunctionalTestBundle\DependencyInjection\Compiler\SetTestClientPass;
use Liip\FunctionalTestBundle\DependencyInjection\Compiler\OptionalValidatorPass;

class LiipFunctionalTestBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new SetTestClientPass());
        $container->addCompilerPass(new OptionalValidatorPass());
    }
}
