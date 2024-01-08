Upgrade from previous version
========================

See [Upgrade guide from 1.x to 2.0](../UPGRADE-2.0.md) and [Upgrade guide from 2.x to 3.x](../UPGRADE-3.0.md).

Installation
============

 1. Download the Bundle

    Open a command console, enter your project directory and execute the
    following command to download the latest stable version of this bundle:

    ```bash
    $ composer require --dev liip/functional-test-bundle:^4.0.0
    ```

    This command requires you to have Composer installed globally, as explained
    in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
    of the Composer documentation.

 2. Enable the Bundle

    Add the following line in the `app/AppKernel.php` file in the `registerBundles` function to enable this bundle only
    for the `test` environment:

    ```php
    $bundles[] = new Liip\FunctionalTestBundle\LiipFunctionalTestBundle();
    ````
   
    See below for full description

    ```php
    <?php
    // app/AppKernel.php

    // ...
    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            // ...
            if (in_array($this->getEnvironment(), array('dev', 'test'), true)) {
                // ...
                if ('test' === $this->getEnvironment()) {
                    $bundles[] = new Liip\FunctionalTestBundle\LiipFunctionalTestBundle();
                }
            }

            return $bundles;
        }

        // ...
    }
    ```

 3. Enable the `functionalTest` service adding the following empty configuration, ensuring that the framework sets the session name and is using the filesystem for session storage:

    * For symfony 3:
        ```yaml
        # app/config/config_test.yml
        liip_functional_test: ~
        ```
 
        ```yaml
        # app/config/config_test.yml
        framework:
            test: ~
            session:
                storage_id: session.storage.mock_file
                name: MOCKSESSION
        ```
    * For symfony 4:
        ```yaml
        # config/packages/test/framework.yaml
        framework:
            test: true
            session:
                storage_id: session.storage.mock_file
                name: MOCKSESSION

        liip_functional_test: ~
        ```
[Basic usage](./basic.md) →
