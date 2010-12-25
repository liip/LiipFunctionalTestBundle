<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Bundle\Liip\FunctionalTestBundle\Test;

use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * @author Lea Haensenberger
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class WebTestCase extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
{
    protected $container;

    /**
     * Avoid the issues with
     *
     *   DOMDocument::loadHTML(): Namespace prefix fb is not defined in Entity
     *
     * when running on Debian machines (likely a libxml 2.6.x issue).
     *
     * It only sets libxml to use internal errors.
     *
     */
    public function __construct() 
    {
        libxml_use_internal_errors(true);
    }

    /**
     * Override the original createKernel method to accommodate the directory
     * additional level in the app directory:
     * app/main/MainKernel.php
     * app/mobile/MobileKernel.php
     * etc.
     *
     * @see Symfony\Bundle\FrameworkBundle\Test\WebTestCase
     * @param array $options
     * @return object
     */
    protected function createKernel(array $options = array())
    {
        $dir = getcwd();
        if (!isset($_SERVER['argv']) || false === strpos($_SERVER['argv'][0], 'phpunit')) {
            throw new \RuntimeException('You must override the WebTestCase::createKernel() method.');
        }

        // find the --configuration flag from PHPUnit
        $cli = implode(' ', $_SERVER['argv']);
        if (preg_match('/\-\-configuration[= ]+([^ ]+)/', $cli, $matches)) {
            $dir = $dir.'/'.$matches[1];
        } elseif (preg_match('/\-c +([^ ]+)/', $cli, $matches)) {
            $dir = $dir.'/'.$matches[1];
        } else {
            throw new \RuntimeException('Unable to guess the Kernel directory.');
        }

        if (!is_dir($dir)) {
            $dir = dirname($dir);
        }

        $appname = explode('\\', get_class($this));
        $appname = $appname[1];

        $class = $appname.'Kernel';
        $file = $dir.'/'.strtolower($appname).'/'.$class.'.php';
        // TODO: this fallback could probably be detected nicer and earlier, but well not important right :)
        if (!file_exists($file)) {
            return parent::createKernel($options);
        }
        require_once $file;

        return new $class(
            isset($options['environment']) ? $options['environment'] : 'test',
            isset($options['debug']) ? $options['debug'] : true
        );
    }

    /**
     * Get an instance of the dependency injection container.
     * (this creates a kernel *without* parameters).
     * @return object
     */
    protected function getContainer()
    {
        if (is_null($this->container)) {
            $options = array();
            $kernel = $this->createKernel($options);
            $kernel->boot();

            $this->container = $kernel->getContainer();
        }
        return $this->container;
    }

    protected function loadFixtures($classnames)
    {
        $kernel = $this->createKernel(array('environment' => 'test'));
        $kernel->boot();

        $em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $connection = $em->getConnection();

        if ($connection->getDriver() instanceOf \Doctrine\DBAL\Driver\PDOSqlite\Driver) {
            $params = $connection->getParams();
            $params['driver'] = 'pdo_sqlite';
            $name = isset($params['path']) ? $params['path'] : $params['dbname'];
            unset($params['dbname']);

            $tmpConnection = \Doctrine\DBAL\DriverManager::getConnection($params);
            $tmpConnection->getSchemaManager()->dropDatabase($name);

            $metadatas = $em->getMetadataFactory()->getAllMetadata();

            if (!empty($metadatas)) {
                $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
                $schemaTool->createSchema($metadatas);
            }

            $purger = null;
            $append = true;
        } else {
            $purger = new ORMPurger();
            $append = false;
        }

        $executor = new ORMExecutor($em, $purger);

        $classnames = (array)$classnames;
        foreach ($classnames as $classname) {
            $namespace = explode('\\', $classname);
            // TODO should we rather handle this via the autoloader?
            require_once $kernel->registerRootDir().'/tests/Fixtures/'.array_pop($namespace).'.php';

            $loader = new Loader();
            $loader->addFixture(new $classname());
            $executor->execute($loader->getFixtures(), $append);
            $append = true;
        }

        $connection->close();
    }

    protected function makeClient($authentication = false)
    {
        $params = array();
        if ($authentication) {
            if ($authentication === true) {
                $authentication = $this->getContainer()->getParameter('functionaltest.authentication');
            }

            $params = array('PHP_AUTH_USER' => $authentication['username'], 'PHP_AUTH_PW' => $authentication['password']);
        }

        return $this->createClient(array('environment' => 'test'), $params);
    }
}
