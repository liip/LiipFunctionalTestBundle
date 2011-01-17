<?php

/*

// Place a file with the contents of the following commented lines into your app dir

require_once  'AppKernel.php';
$kernel = new AppKernel('test', true);
$kernel->boot();
require __DIR__.'/../src/Bundle/Liip/FunctionalTestBundle/phpUnitInit.php';

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
