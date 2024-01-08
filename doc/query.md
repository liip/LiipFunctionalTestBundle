Query Counter
=============

> [!IMPORTANT]
> The Query Counter is not compatible with Symfony 7+.

To catch pages that use way too many database queries, you can enable the query
counter for tests. This will check the profiler for each request made in the
test using the client, and fail the test if the number of queries executed is
larger than the number of queries allowed in the configuration. To enable the
query counter, adjust the `config_test.yml` file like this:

```yaml
framework:
    # ...
    profiler:
        enabled: true
        collect: true

doctrine:
    # ...
    dbal:
        connections:
            default:
                # ...
                profiling: true

liip_functional_test:
    query:
        max_query_count: 50
```

That will limit each request executed within a functional test to 50 queries.

Maximum Query Count per Test
----------------------------

The default value set in the config file should be reasonable to catch pages
with high query counts which are obviously mistakes. There will be cases where
you know and accept that the request will cause a large number of queries, or
where you want to specifically require the page to execute less than x queries,
regardless of the amount set in the configuration. For those cases you can set
an annotation on the test method that will override the default maximum for any
requests made in that test.

To do that, include the Liip\FunctionalTestBundle\Annotations\QueryCount
namespace and add the `@QueryCount(100)` annotation, where 100 is the maximum
amount of queries allowed for each request, like this:

```php
use Liip\FunctionalTestBundle\Annotations\QueryCount;

class DemoTest extends WebTestCase
{
    /**
     * @QueryCount(100)
     */
    public function testDoDemoStuff()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/demoPage');

        $this->assertTrue($crawler->filter('html:contains("Demo")')->count() > 0);
    }
}
```

Caveats
-------

 * QueryCount annotations currently only work for tests that have a method name
   of `testFooBla()` (with a test prefix). The `@test` annotation isn't
   supported at the moment.
 * Enabling the Query Counter currently breaks PHPUnit's built-in annotations,
   e.g. `@dataProvider`, `@depends` etc. To fix this, you need to hide the
   appropriate PHPUnit annotation from Doctrine's annotation reader using the
   `@IgnoreAnnotation` annotation:

   ```php
   use Liip\FunctionalTestBundle\Test\WebTestCase;

   /**
    * @IgnoreAnnotation("dataProvider")
    * @IgnoreAnnotation("depends")
    */
   class DemoTest extends WebTestCase
   {
       // ...
   }
   ```

← [Logged client](./logged.md) • [Examples](./examples.md) →
