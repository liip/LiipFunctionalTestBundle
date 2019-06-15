Introduction
============

ParaTest is PHPUnit Extension allowing to run unit test in parallel, it strongly reduces unit test time consuming.
To run test with paratest, you will need to download ParaTest package through composer and follow the different setup steps.

Installation
============

1) Add ParaTest package

To install with composer, simply run `composer require brianium/paratest`

2) Add the connection factory to your config_test.yml

Since Paratest run your tests through multiple process, we need to feed it with seperate data.
That's why we need to first create test schema, load fixtures, then duplicate the test schema for each created processes.

In order to do this, simply add the following to your config_test.yml

```yaml
parameters:
    doctrine.dbal.connection_factory.class: Liip\FunctionalTestBundle\Factory\ConnectionFactory
```

then rename your default test dbname with this: 

```yaml
doctrine:
    dbal:
        default_connection: default
        connections:
            default:
                driver:  pdo_sqlite
                user:    test
                path:    "%kernel.cache_dir%/__DBNAME__.db"
                memory: false
```

The connection factory will replace __DBNAME__ with it s own unique ID depending of the process unique ID which load it.

3) Run the command, and don't leave for a coffee, it s already finished.

Then run `php app/console paratest:run`

( Run in test environnement by default )



Options
=======

You can modify process amount and phpunit location with the following: 

```yaml
# app/config/config_test.yml
liip_functional_test:
    paratest:
        process:5 #default is 5
        phpunit:'./bin/phpunit' #default is ./bin/phpunit
```
Ensure that the framework is using the filesystem for session storage:


Concerning Phpunit settings, Paratest will read phpunit.xml defined in ./app by default
