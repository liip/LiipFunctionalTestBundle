<?php

$symfonyDir = __DIR__.'/../../../../symfony/src/';
require_once $symfonyDir.'Symfony/Component/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespace('Symfony', $symfonyDir);
$loader->registerNamespace('Liip', __DIR__.'/../../..');
$loader->register();