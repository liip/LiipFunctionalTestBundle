# Upgrade guide from 1.x to 2.0

## Needed actions
This is the list of actions that you need to take when upgrading this bundle from the 1.x to the 2.0 version:

 *  Since 1.x uses `nelmio/alice` <= 2 and 2.0 switched to 3 with `theofidry/alice-data-fixtures`: 
    ```bash
    # If you had “nelmio/alice” installed:
    composer remove --dev nelmio/alice
    composer require --dev liip/functional-test-bundle:~2.0@alpha
    # If you had “nelmio/alice” installed:
    composer require --dev theofidry/alice-data-fixtures
    ```

 *  The interface of `LoadFixtures` had to be changed to allow append fixtures. The main difference is it had been added
    a boolean second parameter. You will have to add it to `false` if you had changed the default manager, driver
    or purge mode.

 *  Changed config format for cache database, use:
    ```
        liip_functional_test:
            cache_db:
                sqlite: liip_functional_test.services_database_backup.sqlite
    ```
    instead of:
    ```
        liip_functional_test:
            cache_sqlite_db: true
    ```

 * MySQL database is created automatically with `doctrine/orm` ≥ 2.6, see [Non-SQLite documentation](doc/database.md#non-sqlite)
 * Declare your fixtures as services and tag them with `doctrine.fixture.orm`.
   It's done automatically if you use [autoconfigure](https://symfony.com/doc/current/service_container.html#service-container-services-load-example). 

 * The `runCommand` method returns a `CommandTester`, you need to call `getDisplay()` to keep the old behavior of getting the output.
