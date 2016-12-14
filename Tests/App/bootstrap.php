<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Bridge\PhpUnit\DeprecationErrorHandler;

$loader = require __DIR__.'/../../vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

DeprecationErrorHandler::register(getenv('SYMFONY_DEPRECATIONS_HELPER'));

return $loader;
