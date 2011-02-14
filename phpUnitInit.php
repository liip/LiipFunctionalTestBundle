<?php

/*

// Place a file with the contents of the following commented lines into your app dir

<?php

require_once __DIR__.'/bootstrap.php';
require_once __DIR__."/AppKernel.php";

$cacheDir = __DIR__."/$kernel/cache/test";
$filesystem = new \Symfony\Bundle\FrameworkBundle\Util\Filesystem();
$filesystem->remove($cacheDir);

$kernel = new $class('test', true);
$kernel->boot();

$proxyDir = $cacheDir.'/doctrine/orm/proxies';
if (!file_exists($proxyDir)) {
    $filesystem->mkdirs($proxyDir, 0777, true);
}

require __DIR__.'/../src/Liip/FunctionalTestBundle/phpUnitInit.php';

*/

$container = $kernel->getContainer();
if ($container->has('doctrine.orm.entity_manager')) {
    $em = $container->get('doctrine.orm.entity_manager');
    $connection = $em->getConnection();

    if ($connection->getDriver() instanceOf \Doctrine\DBAL\Driver\PDOSqlite\Driver) {
        $params = $connection->getParams();
        $name = isset($params['path']) ? $params['path'] : $params['dbname'];

        $connection->getSchemaManager()->dropDatabase($name);

        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        if (!empty($metadatas)) {
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
            $schemaTool->createSchema($metadatas);
        }
    }
}
