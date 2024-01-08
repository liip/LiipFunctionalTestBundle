Introduction
============

[fastest](https://github.com/liuggio/fastest) is a tool for executing PHPUnit tests in parallel.
Once LiipFunctionalTestBundle has been configured, it's easy to use fastest.

Installation
============

1. Install fastest: `composer require "liuggio/fastest=~1.4"`

2. Configure the [storage adapter](https://github.com/liuggio/fastest#storage-adapters)

For example with SQLite, the `app/config_test.yml` have to be changed to:

```yaml
# ...
parameters:
    doctrine.dbal.connection_factory.class: Liuggio\Fastest\Doctrine\DBAL\ConnectionFactory

doctrine:
    dbal:
        default_connection: default
        connections:
            default:
                driver:   pdo_sqlite
                path:     "%kernel.cache_dir%/__DBNAME__.db"
```

Usage
=====

(Optional) Warmup the cache instead of creating it during tests:

```bash
php app/console cache:warmup --env=test
```

If your tests follow the following rules:

 - the names test classes files end with `Test.php`
 - the files are located in a directory like `src/Acme/WebsiteBundle/Tests/`

You can use the following command:

```bash
find src/*/*/Tests/ -name "*Test.php" | vendor/bin/fastest "vendor/bin/phpunit -c app/phpunit.xml.dist {};"
```

Otherwise you'll have to adapt the paths.

← [Caveats](./caveats.md)
