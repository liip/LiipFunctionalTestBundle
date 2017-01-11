[![Build status][Travis Master image]][Travis Master]
[![Scrutinizer Code Quality][Scrutinizer image]
![Scrutinizer][Scrutinizer Coverage Image]][Scrutinizer]
[![SensioLabsInsight][SensioLabsInsight Image]][SensioLabsInsight]

Table of contents:

- [Installation](#installation)
- [Basic usage and methods](#basic-usage)
- [Command tests](#command-tests)
- [Database and fixtures](#database-tests)
- [Create an already logged client](#create-an-already-logged-client)
- [HTML5 Validator](#html5-validator)
- [Query Counter](#query-counter)
- [Caveats](#caveats)
- [paratest](paratest.md)
- [fastest](fastest.md)

Introduction
============

This Bundle provides base classes for functional tests to assist in setting up
test-databases, loading fixtures and HTML5 validation.  It also provides a DI
aware mock builder for unit tests.

Installation
------------

 1. Download the Bundle

    Open a command console, enter your project directory and execute the
    following command to download the latest stable version of this bundle:

    ```bash
    $ composer require --dev liip/functional-test-bundle
    ```

    This command requires you to have Composer installed globally, as explained
    in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
    of the Composer documentation.

 2. Enable the Bundle

    Add the following line in the `app/AppKernel.php` file to enable this bundle only
    for the `test` environment:

    ```php
    <?php
    // app/AppKernel.php

    // ...
    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            // ...
            if (in_array($this->getEnvironment(), array('dev', 'test'))) {
                $bundles[] = new Liip\FunctionalTestBundle\LiipFunctionalTestBundle();
            }

            return $bundles;
        }

        // ...
    }
    ```

 3. Enable the `functionalTest` service adding the following empty configuration:

    ```yaml
    # app/config/config_test.yml
    liip_functional_test: ~
    ```
    Ensure that the framework is using the filesystem for session storage:

    ```yaml
    # app/config/config_test.yml
    framework:
        test: ~
        session:
            storage_id: session.storage.mock_file
    ```

Basic usage
-----------

Use `$this->makeClient` to create a Client object. Client is a Symfony class
that can simulate HTTP requests to your controllers and then inspect the
results. It is covered by the [functional tests](http://symfony.com/doc/current/book/testing.html#functional-tests)
section of the Symfony documentation.

After making a request, use `assertStatusCode` to verify the HTTP status code.
If it fails it will display the last exception message or validation errors
encountered by the Client object.

If you are expecting validation errors, test them with `assertValidationErrors`.

Note: Both `assertStatusCode` and `assertValidationErrors` only works on Symfony 2.5+

```php
use Liip\FunctionalTestBundle\Test\WebTestCase;

class MyControllerTest extends WebTestCase
{
    public function testContact()
    {
        $client = $this->makeClient();
        $crawler = $client->request('GET', '/contact');
        $this->assertStatusCode(200, $client);

        $form = $crawler->selectButton('Submit')->form();
        $crawler = $client->submit($form);

        // We should get a validation error for the empty fields.
        $this->assertStatusCode(200, $client);
        $this->assertValidationErrors(['data.email', 'data.message'], $client->getContainer());

        // Try again with with the fields filled out.
        $form = $crawler->selectButton('Submit')->form();
        $form->setValues(['contact[email]' => 'nobody@example.com', 'contact[message]' => 'Hello']);
        $client->submit($form);
        $this->assertStatusCode(302, $client);
    }
}
```

### Methods

#### Check HTTP status codes

##### isSuccessful()

Check that the request succedded:

```php
$client = $this->makeClient();
$client->request('GET', '/contact');

// Successful HTTP request
$this->isSuccessful($client->getResponse());
```

Add `false` as the second argument in order to check that the request failed:

```php
$client = $this->makeClient();
$client->request('GET', '/error');

// Request returned an error
$this->isSuccessful($client->getResponse(), false);
```

In order to test more specific status codes, use `assertStatusCode()`:

##### assertStatusCode()

Check the HTTP status code from the request:

```php
$client = $this->makeClient();
$client->request('GET', '/contact');

// Standard response for successful HTTP request
$this->assertStatusCode(302, $client);
```

#### Get Crawler or content

##### fetchCrawler()

Get a [Crawler](http://api.symfony.com/master/Symfony/Component/DomCrawler/Crawler.html) instance from an URL:

```php
$crawler = $this->fetchCrawler('/contact');

// There is one <body> tag
$this->assertSame(
    1,
    $crawler->filter('html > body')->count()
);
```

##### fetchContent()

Get the content of an URL:

```php
$content = $this->fetchContent('/contact');

// `filter()` can't be used since the output is HTML code, check the content directly
$this->assertContains(
    '<h1>LiipFunctionalTestBundle</h1>',
    $content
);
```

#### Routing

##### getURL()

Generate an URL from a route:

```php
$path = $this->getUrl(
    'route_name',
    array(
        'argument_1' => 'liip',
        'argument_2' => 'test',
    )
);

$client = $this->makeClient();
$client->request('GET', $path);

$this->isSuccessful($client->getResponse());
```
        

Command Tests
-------------
If you need to test commands, you might need to tweak the output to your needs.
You can adjust the command verbosity:
```yaml
# app/config/config_test.yml
liip_functional_test:
    command_verbosity: debug
```
Supported values are ```quiet```, ```normal```, ```verbose```, ```very_verbose```
and ```debug```. The default value is ```normal```.

You can also configure this on a per-test basis:
```php
use Liip\FunctionalTestBundle\Test\WebTestCase;

class MyTestCase extends WebTestCase {

    public function myTest() {
        $this->verbosityLevel = 'debug';
        $this->runCommand('myCommand');
    }
}
```

Depending where your tests are running, you might want to disable the output
decorator:
```yaml
# app/config/config_test.yml
liip_functional_test:
    command_decoration: false
```
The default value is true.

You can also configure this on a per-test basis:
```php
use Liip\FunctionalTestBundle\Test\WebTestCase;

class MyTestCase extends WebTestCase {

    public function myTest() {
        $this->decorated = false;
        $this->runCommand('myCommand');
    }
}
```

Database Tests
--------------

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

    ```yaml
    # app/config/config_test.yml
    doctrine:
        dbal:
            default_connection: default
            connections:
                default:
                    driver:   pdo_sqlite
                    path:     %kernel.cache_dir%/test.db
    ```

    NB: If you have an existing Doctrine configuration which uses slaves be sure to separate out the configuration for the slaves. Further detail is provided at the bottom of this README.

 2. In order to run your tests even faster, use LiipFunctionalBundle cached database.
    This will create backups of the initial databases (with all fixtures loaded)
    and re-load them when required.

    **Attention: you need Doctrine >= 2.2 to use this feature.**

    ```yaml
    # app/config/config_test.yml
    liip_functional_test:
        cache_sqlite_db: true
    ```

 3. Load your Doctrine fixtures in your tests:

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

 4. If you don't need any fixtures to be loaded and just want to start off with
    an empty database (initialized with your schema), you can simply pass an
    empty array to `loadFixtures`.

    ```php
    use Liip\FunctionalTestBundle\Test\WebTestCase;

    class MyControllerTest extends WebTestCase
    {
        public function testIndex()
        {
            $this->loadFixtures(array());

            // you can now run your functional tests with a populated database
            $client = $this->createClient();
            // ...
        }
    }
    ```

 5. Given that you want to exclude some of your doctrine tables from being purged
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

 6. This bundle uses Doctrine ORM by default. If you are using another driver just
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

            $this->loadFixtures($fixtures, null, 'doctrine_mongodb');

            $client = $this->createClient();
        }
    }
    ```

### Loading Fixtures Using Alice
If you would like to setup your fixtures with yml files using [Alice](https://github.com/nelmio/alice),
[`Liip\FunctionalTestBundle\Test\WebTestCase`](Test/WebTestCase.php) has a helper function `loadFixtureFiles`
which takes an array of resources, or paths to yml files, and returns an array of objects.
This method uses the [Alice Loader](https://github.com/nelmio/alice/blob/master/src/Nelmio/Alice/Fixtures/Loader.php)
rather than the FunctionalTestBundle's load methods. You should be aware that there are some difference between the ways these two libraries handle loading.

```php
$fixtures = $this->loadFixtureFiles(array(
    '@AcmeBundle/DataFixtures/ORM/ObjectData.yml',
    '@AcmeBundle/DataFixtures/ORM/AnotherObjectData.yml',
    '../../DataFixtures/ORM/YetAnotherObjectData.yml',
));
```

#### HautelookAliceBundle Faker Providers

This bundle supports faker providers from HautelookAliceBundle.
Install the bundle with `composer require --dev hautelook/alice-bundle:~1.2` and use the
[HautelookAliceBundle documentation](https://github.com/hautelook/AliceBundle/blob/1.x/src/Resources/doc/faker-providers.md#faker-providers)
in order to define your faker providers.

You'll have to add the following line in the `app/AppKernel.php` file:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        // ...
        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Hautelook\AliceBundle\HautelookAliceBundle();
        }

        return $bundles;
    }

    // ...
}
```

Then you can load fixtures with `$this->loadFixtureFiles(array('@AcmeBundle/…/fixture.yml'));`.

### Non-SQLite

The Bundle will not automatically create your schema for you unless you use SQLite.
If you prefer to use another database but want your schema/fixtures loaded
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

Create an already logged client
-----------------------------

The `WebTestCase` provides a conveniency method to create an already logged in client using the first parameter of
`WebTestCase::makeClient()`.

You have three alternatives to create an already logged in client:

1. Use the `liip_functional_test.authentication` key in the `config_test.yml` file;
2. Pass an array with login parameters directly when you call the method;
3. Use the method `WebTestCase::loginAs()`;

### Logging in a user from the `config_test.yml` file

You can set the credentials for your test user in your `config_test.yml` file:

```yaml
liip_functional_test:
    authentication:
        username: "a valid username"
        password: "the password of that user"
```

This way using `$client = $this->makeClient(true);` your client will be automatically logged in.

### Logging in a user passing the credentials directly in the test method

You can log in a user directly from your test method by simply passing an array as the first parameter of
`WebTestCase::makeClient()`:

```php
$credentials = array(
    'username' => 'a valid username',
    'password' => 'a valid password'
);

$client = $this->makeClient($credentials);
```

### Logging in a user using `WebTestCase::loginAs()`

To use the method `WebTestCase::loginAs()` you have to [return the repository containing all references set in the
fixtures](#referencing-fixtures-in-tests) using the method `getReferenceRepository()` and pass the reference of the `User`
object to the method `WebTestCase::loginAs()`.

```php
$fixtures = $this->loadFixtures(array(
    'AppBundle\DataFixtures\ORM\LoadUserData'
))->getReferenceRepository();

$this->loginAs($fixtures->getReference('account-alpha'), 'main');
$client = $this->makeClient();
```

Remember that `WebTestCase::loginAs()` accepts objects that implement the interface `Symfony\Component\Security\Core\User\UserInterface`. 

**If you get the error message *"Missing session.storage.options#name"***, you have to simply add to your
[`config_test.yml`](https://github.com/liip/LiipFunctionalTestBundle/blob/master/Tests/App/config.yml#L16)
file the key `name`:

```yaml
framework:
    ...
    session:
        # handler_id set to null will use default session handler from php.ini
        handler_id:  ~
        storage_id: session.storage.filesystem
        name: MOCKSESSID
```

### Recommendations to use already logged in clients

As [recommended by the Symfony Cookbook](http://symfony.com/doc/current/cookbook/testing/http_authentication.html) in
the chapter about Testing, it is a good idea to to use HTTP Basic Auth for you tests. You can configure the
authentication method in your `config_test.yml`:

```yaml
# The best practice in symfony is to put a HTTP basic auth
# for the firewall in test env, so that not to have to
# make a request to the login form every single time.
# http://symfony.com/doc/current/cookbook/testing/http_authentication.html
security:
    firewalls:
        NAME_OF_YOUR_FIREWALL:
            http_basic: ~
```

### Final notes

For more details, you can check the implementation of `WebTestCase` in that bundle.

HTML5 Validator
---------------

The online validator: http://validator.nu/
The documentation: http://about.validator.nu/
Documentation about the web service: https://github.com/validator/validator/wiki/Service:-HTTP-interface

Dependencies
------------

To run the validator you require the following dependencies:

 * A java JDK 5 or later
 * Python
 * SVN
 * Mercurial

Note: The script wants to see a Sun-compatible jar executable. Debian fastjar will not work.

Compilation and Execution
-------------------------

Before starting:

 * Set the `JAVA_HOME` environment variable to the root of the installed JDK
 * Add the location of `javac` to your `PATH` (`$JAVA_HOME/bin`).
 * Alternatively you can use the `--javac=/usr/bin/javac` parameter of the `build.py` script.

Then:

```sh
$ mkdir checker; cd checker
$ git clone https://github.com/validator/validator.git
$ cd validator
$ python ./build/build.py all; python ./build/build.py all
```

Note: Yes, the last line is there twice intentionally. Running the script twice tends to fix
a `ClassCastException` on the first run.

Note: If at some point for some reason the compilation fails and you are forced to re-run it,
it may be necessary to manually remove the htmlparser directory from your disk (the compilation
process will complain about that).

This will download the necessary components, compile the validator and run it. This will require
about 10 minutes on the first run.

Once the validator is executed it can be reached at http://localhost:8888/ Further instructions on how to build the validator can be found at http://validator.github.io/validator/#build-instructions.

Execution
---------

Once the validator has been compiled, it can be run with the following command:

```sh
cd checker
python build/build.py run
```

Using the Validator in Functional Tests
---------------------------------------

The `Liip\FunctionalTestBundle\Test\Html5WebTestCase` class allows to write
functional tests that validate content against the HTML5 validator. In order to
work the validator service must be running on the machine where the tests are
executed.

This class provides the following testing methods:

 * **validateHtml5**: This runs a validation on the provided content and returns
   the full messages of the validation service (including warnings and
   information). This method is not meant as a test method but rather as a
   helper to access the validator service. Internally the test method below will
   use this helper to access the validation service.

 * **assertIsValidHtml5**: This will validate the provided content. If the
   validation succeeds, execution silently continues, otherwise the calling test
   will fail and display a list of validation errors.

 * **assertIsValidHtml5Snippet**: This will validate an HTML5 snippets (i.e. not
   a full HTML5 document) by wrapping it into an HTML5 document. If the
   validation succeeds, execution silently continues, otherwise the calling test
   will fail and display a list of validation errors.

 * **assertIsValidHtml5AjaxResponse**: This will validate an AJAX response in a
   specific format (probably not generic enough). If the validation succeeds,
   execution silently continues, otherwise the calling test will fail and
   display a list of validation errors.

 * **setHtml5Wrapper**: Allow to change the default HTML5 code that is used as a
   wrapper around snippets to validate

Query Counter
=============

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

Only in Test Environment
------------------------

All the functionality of this bundle is primarily for use in the test
environment. The query counter specifically requires services that are only
loaded in the test environment, so the service will only be loaded there. If you
want to use the query counter in a different environment, you'll need to make
sure the bundle is loaded in that environment in your `AppKernel.php` file, and
load the test services by adding `test` to the framework configuration in the
config.yml (or the configuration file for your environment):

```yaml
framework:
    [...]
    test: ~
```

If that's not what you want to do, and you're getting an exception about this,
check that you're really only loading this bundle in your `test` environment
(See step 3 of the [installation](#installation))

Doctrine Slaves and SQLite
--------------------------

If your main configuration for Doctrine uses Slaves, you need to ensure that the configuration for your SQLite test environment does not include the slave configuration.

The following error can occur in the case where a Doctrine Slave configuration is included:

    SQLSTATE[HY000]: General error: 1 no such table NameOfTheTable

This may also manifest itself in the command `doctrine:create:schema` doing nothing.

To resolve the issue, it is recommended to configure your Doctrine slaves  specifically for the environments that require them.

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
  Liip\FunctionalTestBundle\Test\WebTestCase;

   /**
    * @IgnoreAnnotation("dataProvider")
    * @IgnoreAnnotation("depends")
    */
   class DemoTest extends WebTestCase
   {
       // ...
   }
   ```

[Travis Master]: https://travis-ci.org/liip/LiipFunctionalTestBundle
[Travis Master image]: https://travis-ci.org/liip/LiipFunctionalTestBundle.svg?branch=master
[Scrutinizer]: https://scrutinizer-ci.com/g/liip/LiipFunctionalTestBundle/?branch=master
[Scrutinizer image]: https://scrutinizer-ci.com/g/liip/LiipFunctionalTestBundle/badges/quality-score.png?b=master
[Scrutinizer Coverage image]: https://scrutinizer-ci.com/g/liip/LiipFunctionalTestBundle/badges/coverage.png?b=master
[SensioLabsInsight]: https://insight.sensiolabs.com/projects/98b07673-7b35-44f3-acb3-07c33b395118
[SensioLabsInsight Image]: https://insight.sensiolabs.com/projects/98b07673-7b35-44f3-acb3-07c33b395118/mini.png
