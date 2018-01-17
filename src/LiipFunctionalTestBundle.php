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

namespace Liip\FunctionalTestBundle;

use Liip\FunctionalTestBundle\DependencyInjection\Compiler\OptionalValidatorPass;
use Liip\FunctionalTestBundle\DependencyInjection\Compiler\SetTestClientPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LiipFunctionalTestBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new SetTestClientPass());
        $container->addCompilerPass(new OptionalValidatorPass());
    }
}
