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

use Doctrine\Common\Annotations\AnnotationRegistry;

$loader = require __DIR__.'/../../vendor/autoload.php';

// This method only exist on doctrine/annotations:^1.3,
// it is missing but not needed with doctrine/annotations:^2.0
if (method_exists(AnnotationRegistry::class, 'registerLoader')) {
    AnnotationRegistry::registerLoader([$loader, 'loadClass']);
}

return $loader;
