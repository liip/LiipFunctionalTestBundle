<?php

// Autoloading is different according to the context
$context = file_exists(__DIR__.'/../vendor') ? 'standalone' : 'sf2_project';
echo "Autoloading type: [$context]\n";

if ($context === 'standalone'){
    // In standalone execution we rely on composer autoloader
    $file = __DIR__.'/../vendor/autoload.php';
    if (!file_exists($file)) {
        throw new RuntimeException('Install dependencies to run test suite.');
    }
    $loader = require_once $file;
    $loader->add('TestBundle', __DIR__.'/Functional/app/src');
}
else {
    // In symfony project project context, we rely on the symfony class loader
    $symfonyDir = realpath(__DIR__.'/../../../../../vendor/symfony/src'); 
    require_once $symfonyDir.'/Symfony/Component/ClassLoader/UniversalClassLoader.php';
    $loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
    $loader->registerNamespace('Symfony', $symfonyDir);
    $loader->registerNamespace('Liip', __DIR__.'/../../..');
    $loader->registerNamespace('TestBundle', __DIR__.'/Functional/app/src');
    $loader->register();
}

// In order to adapt the test project, we must now the symfony version

define('SYMFONY_VERSION', class_exists('Symfony\Component\HttpKernel\Util\Filesystem') ? '2.0' : '2.1');

// We also need to adapt the config from the YML
Symfony\Component\Yaml\Yaml::$enablePhpParsing = true;
