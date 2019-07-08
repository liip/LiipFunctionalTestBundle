# Upgrade guide from 2.x to 3.x

## Needed actions
This is the list of actions that you need to take when upgrading this bundle from the 2.x to the 3.x version:

 * Fixtures loading has been moved to a separate bundle named [LiipTestFixturesBundle][LiipTestFixturesBundle]
   * If you need to load fixtures, follow the [install guide for LiipTestFixturesBundle][LiipTestFixturesBundle installation]
   * If you used configuration `liip_functional_test.cache_db`, change it to `liip_test_fixtures.cache_db`
   * if you used to stubs `doctrine.dbal.connection_factory.class` you need now to use ` Liip\TestFixturesBundle\Factory\ConnectionFactory` instead of `Liip\FunctionalTestBundle\Factory\ConnectionFactory`
   * And call `use \Liip\TestFixturesBundle\Test\FixturesTrait;` in tests classes in order to access to `loadFixtures()` and `loadFixtureFiles()`
   * Change `Liip\FunctionalTestBundle\Annotations\DisableDatabaseCache` by `Liip\TestFixturesBundle\Annotations\DisableDatabaseCache`
   
[LiipTestFixturesBundle]: https://github.com/liip/LiipTestFixturesBundle
[LiipTestFixturesBundle installation]: https://github.com/liip/LiipTestFixturesBundle/blob/master/doc/installation.md

 * `makeClient()` doesn't accept a boolean or array as its first argument, it has been split in 2 functions:
   Old code:
   ```
   $client = static::makeClient(true);
   ```
    
   New code:
   ```
   $client = static::makeAuthenticatedClient();
   ```
   
   Old code:
   ```
   $client = static::makeClient([
       'username' => 'foobar',
       'password' => '12341234',
   ]);
   ```
   
   New code:
   ```
   $client = static::makeClientWithCredentials('foobar', '12341234');
   ```

   These 2 new methods still accept an array for parameters as the last argument.
