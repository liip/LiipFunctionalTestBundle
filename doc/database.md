Database Tests
==============

If you plan on loading fixtures with your tests, make sure you have the
DoctrineFixturesBundle installed and configured first:
[Doctrine Fixtures setup and configuration instructions](http://symfony.com/doc/current/bundles/DoctrineFixturesBundle/index.html#setup-and-configuration)

In case tests require database access make sure that the database is created and
proxies are generated.  For tests that rely on specific database contents,
write fixture classes and call `loadFixtures()` method from the bundled
`Test\WebTestCase` class. This will replace the database configured in
`config_test.yml` with the specified fixtures. Please note that `loadFixtures()`
will delete the contents from the database before loading the fixtures. That's
why you should use a designated database for tests.

Tips for Fixture Loading Tests
------------------------------

### SQLite

 1. If you want your tests to run against a completely isolated database (which
    is recommended for most functional-tests), you can configure your
    test-environment to use a SQLite-database. This will make your tests run
    faster and will create a fresh, predictable database for every test you run.

    * For symfony 3: add those lines to `app/config/config_test.yml`:
        ```yaml
        # app/config/config_test.yml
        doctrine:
            dbal:
                default_connection: default
                connections:
                    default:
                        driver:   pdo_sqlite
                        path:     "%kernel.cache_dir%/test.db"
        ```
    
    * For symfony 4 : create file if it doesn't exists `config/packages/test/doctrine.yaml`, and if it does append those lines:
        ```yaml
        # config/packages/test/doctrine.yaml
        doctrine:
            dbal:
                url: "%kernel.cache_dir%/test.db"

    NB: If you have an existing Doctrine configuration which uses slaves be sure to separate out the configuration for the slaves. Further detail is provided at the bottom of this README.

 2. In order to run your tests even faster, use LiipFunctionalBundle cached database.
    This will create backups of the initial databases (with all fixtures loaded)
    and re-load them when required.

    **Attention: you need Doctrine >= 2.2 to use this feature.**

    ```yaml
    # sf3: app/config/config_test.yml
    # sf4: config/packages/test/framework.yaml
    liip_functional_test:
        cache_db:
            sqlite: liip_functional_test.services_database_backup.sqlite
    ```

 3. For create custom database cache service:
 
    Create cache class, implement `\Liip\FunctionalTestBundle\Services\DatabaseBackup\DatabaseBackupInterface` and add it to config

    For example:
    ```yaml
    # app/config/config_test.yml
    liip_functional_test:
        cache_db:
            mysql: liip_functional_test.services_database_backup.mysql
            mongodb: liip_functional_test.services_database_backup.mongodb
            phpcr: ...
            db2: ...
            [Other \Doctrine\DBAL\Platforms\AbstractPlatform name]: ...
    ```

    **Attention: `liip_functional_test.services_database_backup.mysql` required `mysql-client` installed on server.**

    **Attention: `liip_functional_test.services_database_backup.mongodb` required `mongodb-clients` installed on server.**
 
 4. Load your Doctrine fixtures in your tests:

    ```php
    use Liip\FunctionalTestBundle\Test\WebTestCase;

    class MyControllerTest extends WebTestCase
    {
        public function testIndex()
        {
            // add all your fixtures classes that implement
            // Doctrine\Common\DataFixtures\FixtureInterface
            $this->loadFixtures(array(
                'Bamarni\MainBundle\DataFixtures\ORM\LoadData',
                'Me\MyBundle\DataFixtures\ORM\LoadData'
            ));

            // you can now run your functional tests with a populated database
            $client = $this->createClient();
            // ...
        }
    }
    ```

 5. If you don't need any fixtures to be loaded and just want to start off with
    an empty database (initialized with your schema), you can simply call
    `loadFixtures` without any argument.

    ```php
    use Liip\FunctionalTestBundle\Test\WebTestCase;

    class MyControllerTest extends WebTestCase
    {
        public function testIndex()
        {
            $this->loadFixtures();

            // you can now run your functional tests with a populated database
            $client = $this->createClient();
            // ...
        }
    }
    ```

 6. Given that you want to exclude some of your doctrine tables from being purged
    when loading the fixtures, you can do so by passing an array of tablenames 
    to the `setExcludedDoctrineTables` method before loading the fixtures.

    ```php
    use Liip\FunctionalTestBundle\Test\WebTestCase;

    class MyControllerTest extends WebTestCase
    {
        public function testIndex()
        {
            $this->setExcludedDoctrineTables(array('my_tablename_not_to_be_purged'));
            $this->loadFixtures(array(
                'Me\MyBundle\DataFixtures\ORM\LoadData'
            ));
            // ...
        }
    }
    ```

 7. If you want to append fixtures instead of clean database and load them, you have
 to consider use the second parameter $append with value true.

    ```php
        use Liip\FunctionalTestBundle\Test\WebTestCase;

        class MyControllerTest extends WebTestCase
        {
            public function testIndex()
            {
                $this->loadFixtures(array(
                    'Me\MyBundle\DataFixtures\ORM\LoadAnotherObjectData',
                    true
                ));
                // ...
            }
        }
    ```

 8. This bundle uses Doctrine ORM by default. If you are using another driver just
    specify the service id of the registry manager:

    ```php
    use Liip\FunctionalTestBundle\Test\WebTestCase;

    class MyControllerTest extends WebTestCase
    {
        public function testIndex()
        {
            $fixtures = array(
                'Me\MyBundle\DataFixtures\MongoDB\LoadData'
            );

            $this->loadFixtures($fixtures, false, null, 'doctrine_mongodb');

            $client = $this->createClient();
        }
    }
    ```

### Loading Fixtures Using Alice
If you would like to setup your fixtures with yml files using [Alice](https://github.com/nelmio/alice),
[`Liip\FunctionalTestBundle\Test\WebTestCase`](Test/WebTestCase.php) has a helper function `loadFixtureFiles`
which takes an array of resources, or paths to yml files, and returns an array of objects.
This method uses the [Theofidry AliceDataFixtures loader](https://github.com/theofidry/AliceDataFixtures#doctrine-orm)
rather than the FunctionalTestBundle's load methods.
You should be aware that there are some difference between the ways these two libraries handle loading.

```php
$fixtures = $this->loadFixtureFiles(array(
    '@AcmeBundle/DataFixtures/ORM/ObjectData.yml',
    '@AcmeBundle/DataFixtures/ORM/AnotherObjectData.yml',
    __DIR__.'/../../DataFixtures/ORM/YetAnotherObjectData.yml',
));
```

If you want to clear tables you have the following two ways:
1. Only to remove records of tables;
2. Truncate tables.

The first way is consisted in using the second parameter `$append` with value `true`. It allows you **only** to remove all records of table. Values of auto increment won't be reset. 
```php
$fixtures = $this->loadFixtureFiles(
    array(
        '@AcmeBundle/DataFixtures/ORM/ObjectData.yml',
        '@AcmeBundle/DataFixtures/ORM/AnotherObjectData.yml',
        __DIR__.'/../../DataFixtures/ORM/YetAnotherObjectData.yml',
    ),
    true
);
```

The second way is consisted in using the second parameter `$append` with value `true` and the last parameter `$purgeMode` with value `Doctrine\Common\DataFixtures\Purger\ORMPurger::PURGE_MODE_TRUNCATE`. It allows you to remove all records of tables with resetting value of auto increment.

```php
<?php

use Doctrine\Common\DataFixtures\Purger\ORMPurger;

$files = array(
     '@AcmeBundle/DataFixtures/ORM/ObjectData.yml',
     '@AcmeBundle/DataFixtures/ORM/AnotherObjectData.yml',
     __DIR__.'/../../DataFixtures/ORM/YetAnotherObjectData.yml',
 );
$fixtures = $this->loadFixtureFiles($files, true, null, 'doctrine', ORMPurger::PURGE_MODE_TRUNCATE );
```

### Non-SQLite

The Bundle will not automatically create your schema for you unless you use SQLite
or use `doctrine/orm` < 2.6.

So you have several options:

1. use SQLite driver in tests
2. upgrade `doctrine/orm` :
   
   ```bash
   composer require doctrine/orm:^2.6
   ```
3. if you prefer to use another database but want your schema/fixtures loaded
automatically, you'll need to do that yourself. For example, you could write a
`setUp()` function in your test, like so:


```php
use Doctrine\ORM\Tools\SchemaTool;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class AccountControllerTest extends WebTestCase
{
    public function setUp()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        if (!isset($metadatas)) {
            $metadatas = $em->getMetadataFactory()->getAllMetadata();
        }
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        if (!empty($metadatas)) {
            $schemaTool->createSchema($metadatas);
        }
        $this->postFixtureSetup();

        $fixtures = array(
            'Acme\MyBundle\DataFixtures\ORM\LoadUserData',
        );
        $this->loadFixtures($fixtures);
    }
//...
}
```

Without something like this in place, you'll have to load the schema into your
test database manually, for your tests to pass.

### Referencing fixtures in tests

In some cases you need to know for example the row ID of an object in order to write a functional test for it, e.g. 
`$crawler = $client->request('GET', "/profiles/$accountId");` but since the `$accountId` keeps changing each test run, you need to figure out its current value. Instead of going via the entity manager repository and querying for the entity, you can use `setReference()/getReference()` from the fixture executor directly, as such:

In your fixtures class:

```php
...
class LoadMemberAccounts extends AbstractFixture 
{
    public function load() 
    {
        $account1 = new MemberAccount();
        $account1->setName('Alpha');
        $this->setReference('account-alpha', $account1);
        ...
```    
and then in the test case setup:
```php
...
    public function setUp()
    {
        $this->fixtures = $this->loadFixtures([
            'AppBundle\Tests\Fixtures\LoadMemberAccounts'
        ])->getReferenceRepository();
    ...
```
and finally, in the test:
```php
        $accountId = $this->fixtures->getReference('account-alpha')->getId();
        $crawler = $client->request('GET', "/profiles/$accountId");
```

Doctrine Slaves and SQLite
--------------------------

If your main configuration for Doctrine uses Slaves, you need to ensure that the configuration for your SQLite test environment does not include the slave configuration.

The following error can occur in the case where a Doctrine Slave configuration is included:

    SQLSTATE[HY000]: General error: 1 no such table NameOfTheTable

This may also manifest itself in the command `doctrine:create:schema` doing nothing.

To resolve the issue, it is recommended to configure your Doctrine slaves  specifically for the environments that require them.
