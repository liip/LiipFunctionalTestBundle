Introduction
============

This Bundle provides base classes for functional tests to assist in loading fixtures and html5 validation.

Installation
------------

  1. Add this bundle to your project as Git submodules:

          $ git submodule add git://github.com/liip/FunctionalTestBundle.git src/Bundle/Liip/FunctionalTestBundle

  2. Add this bundle to your application's kernel:

          // application/ApplicationKernel.php
          public function registerBundles()
          {
              return array(
                  // ...
                  new Bundle\Liip\FunctionalTestBundle\LiipFunctionalTestBundle(),
                  // ...
              );
          }

  3. Configure the `functionalTest` service in your config:

          # application/config/config.yml
          functionalTest.config: ~

  4. Copy the fixtures to your projects functional tests

         $ cp Fixtures/LoadUserData.php ..

  5. Copy the functional tests to your projects functional tests

         $ cp FunctionalTests/ExampleTest.php ..

  6. Install local copy of the HTML5 validator

         More information see below

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
* Add the location of "javac" to your PATH ($JAVA_HOME/bin). Alternatively you can use the --javac=/usr/bin/javac parameter of the build.py script.

Then:

    mkdir checker
    cd checker
    svn co https://whattf.svn.cvsdude.com/build/trunk/ build
    python build/build.py all
    python build/build.py all

Note: Yes, the last line is there twice intentionally. Running the script twice tends to fix a ClassCastException on the first run.

Note: If at some point for some reason the compilation fails and you are forced to re-run it, it may be necessary to manually remove the htmlparser directory from your disk (the compilation process will complain about that).

This will download the necessary components, compile the validator and run it. This will require about 10min on the first run.

Once the validator is executed it can be reached at [http://localhost:8888/]

Execution
---------

Once the validator has been compiled, it can be run with the following command:

    cd checker
    python build/build.py run

Using the validator in functional tests
---------------------------------------

The Bundle\Liip\FunctionalTestBundle\Test\Html5WebTestCase class allows to write functional tests that validate content
against the HTML5 validator. In order to work the validator service must be running on the machine where the tests are executed.

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
This will validate an AJAX response in a specific format (probably not generic enough). If the validation succeeds, execution silently continues, otherwise the calling test will fail and display a list of validation errors.

setHtml5Wrapper:
Allow to change the default HTML5 code that is used as a wrapper around snippets to validate
