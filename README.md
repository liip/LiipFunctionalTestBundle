Introduction
============

This Bundle provides base classes for functional tests to assist in setting up
test-databases, loading fixtures and html5 validation.  It also provides a DI
aware mock builder for unit tests.

Installation
------------

  If you plan on loading fixtures with your tests, make sure you have the
  DoctrineFixturesBundle installed and configured first.

  [Doctrine Fixtures setup and configuration instructions](http://symfony.com/doc/2.0/cookbook/doctrine/doctrine_fixtures.html#setup-and-configuration)

  1. Add this bundle to your project as Git submodule:

          $ git submodule add git://github.com/liip/LiipFunctionalTestBundle.git vendor/bundles/Liip/FunctionalTestBundle

     Or configure your ``deps`` to include the bundle:

          [LiipFunctionalTestBundle]
              git=git://github.com/liip/LiipFunctionalTestBundle.git
              target=bundles/Liip/FunctionalTestBundle


  2. Add the Liip namespace to your autoloader:

          // app/autoload.php
          $loader->registerNamespaces(array(
                'Liip' => __DIR__.'/../vendor/bundles',
                // your other namespaces
          ));

  3. Add this bundle to your application's kernel:

          // application/ApplicationKernel.php
          public function registerBundles()
          {
              return array(
                  // ...
                  new Liip\FunctionalTestBundle\LiipFunctionalTestBundle(),
                  // ...
              );
          }

  4. Configure the `functionalTest` service, and ensure that the framework is using the filesystem for session storage:

          # application/config/config_test.yml
          framework:
              test: ~
                  session:
                          storage_id: session.storage.filesystem

          liip_functional_test: ~


  5. Copy the fixtures to your projects functional tests

         $ cp Fixtures/LoadUserData.php ..

  6. Copy example unit and functional tests to your projects functional tests

         $ cp Tests/ExampleUnitTest.php ..
         $ cp FunctionalTests/ExampleFunctionalTest.php ..
         $ cp FunctionalTests/ExampleHtml5FunctionalTest.php ..

  7. Install local copy of the HTML5 validator

         More information see below

Database tests
--------------

In case tests require database access make sure that the DB is created and
proxies are generated.  For tests that rely on specific database contents,
write fixture classes and call ``loadFixtures`` from the bundled
``Test\WebTestCase`` class. This will replace the database configured in
``config_test.yml`` with the specified fixtures. Please note that you should be
using a designated test-database if you're using test-fixtures, since
``loadFixtures`` will delete the contents from the database before loading the
fixtures.

Tips for fixture loading tests
------------------------------

1. If you want your tests to run against a completely isolated database (which is
   recommended for most functional-tests), you can configure your
   test-environment to use a sqlite-database. This will make your tests run
   faster and will create a fresh, predictable database for every test you run.

    Add this to your `app/config_test.yml`:

        doctrine:
            dbal:
                default_connection: default
                connections:
                    default:
                        driver:   pdo_sqlite
                        path:     %kernel.cache_dir%/test.db

2. Use LiipFunctionalBundle's cached database feature, so that your tests run even 
   faster. This will create backups of the initial databases (with all fixtures
   loaded) and re-load them when required.

    Add this to your `app/config_test.yml`

        liip_functional_test:
            cache_sqlite_db: true

3. Load your doctrine fixtures in your tests:

        use Liip\FunctionalTestBundle\Test\WebTestCase;

        class MyControllerTest extends WebTestCase
        {
            public function testIndex()
            {
                $client = static::createClient();

                // add all your doctrine fixtures classes
                $classes = array(
                    // classes implementing Doctrine\Common\DataFixtures\FixtureInterface
                    'Bamarni\MainBundle\DataFixtures\ORM\LoadData',
                    'Me\MyBundle\DataFixtures\ORM\LoadData'
                );

                $this->loadFixtures($classes);

                // you can now run your functional tests with a populated database
                // ...
            }
        }

4. If you don't need any fixtures to be loaded and just want to start off with
   an empty database (initialized with your schema), you can simply pass an
   empty array to ``loadFixtures``.

        use Liip\FunctionalTestBundle\Test\WebTestCase;

        class MyControllerTest extends WebTestCase
        {
            public function testIndex()
            {
                $client = static::createClient();

                $this->loadFixtures(array());

                // you can now run your functional tests with an empty database
                // ...
            }
        }

HTML5 validator
---------------

The on-line validator: http://validator.nu/
The documentation: http://about.validator.nu/
Documentation about the web service: http://wiki.whatwg.org/wiki/Validator.nu_Web_Service_Interface

Dependencies
------------

To run the validator you require the following dependencies:
* A java JDK 5 or later
* Python
* SVN
* Mercurial

Note: The script wants to see a Sun-compatible jar executable. Debian fastjar will not work.

Compilation and execution
-------------------------

Before starting:
* Set the JAVA_HOME environment variable to the root of the installed JDK
* Add the location of "javac" to your PATH ($JAVA_HOME/bin).
* Alternatively you can use the --javac=/usr/bin/javac parameter of the build.py script.

Then:

    mkdir checker
    cd checker
    svn co https://whattf.svn.cvsdude.com/build/trunk/ build
    python build/build.py all
    python build/build.py all

Note: Yes, the last line is there twice intentionally. Running the script twice tends to fix a ClassCastException
on the first run.

Note: If at some point for some reason the compilation fails and you are forced to re-run it, it may be necessary to
manually remove the htmlparser directory from your disk (the compilation process will complain about that).

This will download the necessary components, compile the validator and run it. This will require about 10min on the first run.

Once the validator is executed it can be reached at [http://localhost:8888/]

Execution
---------

Once the validator has been compiled, it can be run with the following command:

    cd checker
    python build/build.py run

Using the validator in functional tests
---------------------------------------

The Liip\FunctionalTestBundle\Test\Html5WebTestCase class allows to write functional tests that validate
content against the HTML5 validator. In order to work the validator service must be running on the machine
where the tests are executed.

This class provides the following testing methods:

validateHtml5:
This runs a validation on the provided content and returns the full messages of the validation service
(including warnings and information). This method is not meant as a test method but rather as a helper to access the
validator service. Internally the test method below will use this helper to access the validation service.

assertIsValidHtml5:
This will validate the provided content. If the validation succeeds, execution silently continues, otherwise the
calling test will fail and display a list of validation errors.

assertIsValidHtml5Snippet:
This will validate an HTML5 snippets (i.e. not a full HTML5 document) by wrapping it into an HTML5 document. If the
validation succeeds, execution silently continues, otherwise the calling test will fail and display a list of
validation errors.

assertIsValidHtml5AjaxResponse:
This will validate an AJAX response in a specific format (probably not generic enough). If the validation succeeds,
execution silently continues, otherwise the calling test will fail and display a list of validation errors.

setHtml5Wrapper:
Allow to change the default HTML5 code that is used as a wrapper around snippets to validate
