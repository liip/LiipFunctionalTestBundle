<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Test;

use Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Lea Haensenberger
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
abstract class WebTestCase extends BaseWebTestCase
{
    protected $container;
    protected $kernelDir;

    protected function getKernelClass()
    {
        $dir = isset($_SERVER['KERNEL_DIR']) ? $_SERVER['KERNEL_DIR'] : $this->getPhpUnitXmlDir();

        $appname = explode('\\', get_class($this));
        $appname = $appname[1];

        $class = $appname.'Kernel';
        $file = $dir.'/'.strtolower($appname).'/'.$class.'.php';
        if (!file_exists($file)) {
            return parent::getKernelClass();
        }
        require_once $file;

        return $class;
    }

    /**
     * Creates a mock object of a service identified by its id.
     *
     * @param string $id
     * @return PHPUnit_Framework_MockObject_MockBuilder
     */
    protected function getServiceMockBuilder($id)
    {
        $service = $this->getContainer()->get($id);
        $class = get_class($service);
        return $this->getMockBuilder($class)->disableOriginalConstructor();
    }

    /**
     * Builds up the environment to run the given command.
     *
     * @param string $name
     * @param array $params
     * @return string
     */
    protected function runCommand($name, array $params = array())
    {
        array_unshift($params, $name);

        $kernel = $this->createKernel(array('environment' => 'test'));
        $kernel->boot();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput($params);
        $input->setInteractive(false);

        $fp = fopen('php://temp/maxmemory:'.(5 * 1024 * 1024), 'r+');
        $output = new StreamOutput($fp);

        $application->run($input, $output);

        rewind($fp);
        return stream_get_contents($fp);
    }

    /**
     * Get an instance of the dependency injection container.
     * (this creates a kernel *without* parameters).
     * @return object
     */
    protected function getContainer()
    {
        if (!empty($this->kernelDir)) {
            $tmpKernelDir = isset($_SERVER['KERNEL_DIR']) ? $_SERVER['KERNEL_DIR'] : null;
            $_SERVER['KERNEL_DIR'] = getcwd().$this->kernelDir;
        }

        if (empty($this->container[$this->kernelDir])) {
            $options = array();
            $kernel = $this->createKernel($options);
            $kernel->boot();

            $this->container[$this->kernelDir] = $kernel->getContainer();
        }

        if (isset($tmpKernelDir)) {
            $_SERVER['KERNEL_DIR'] = $tmpKernelDir;
        }

        return $this->container[$this->kernelDir];
    }

    protected function loadFixtures($classnames = array())
    {
        $kernel = $this->createKernel(array('environment' => 'test'));
        $kernel->boot();

        $container = $kernel->getContainer();

        $em = $container->get('doctrine.orm.entity_manager');
        $connection = $em->getConnection();

        if ($connection->getDriver() instanceOf \Doctrine\DBAL\Driver\PDOSqlite\Driver) {
            $params = $connection->getParams();
            $name = isset($params['path']) ? $params['path'] : $params['dbname'];

            if ($container->getParameter('liip_functional_test.cache_sqlite_db')) {
                $backup = $container->getParameter('kernel.cache_dir').'/test_'.md5(serialize($classnames)).'.db';
                if (file_exists($backup)) {
                    copy($backup, $name);
                    return;
                }
            }

            // TODO: handle case when using persistent connections. Fail loudly?
            $connection->getSchemaManager()->dropDatabase($name);

            $metadatas = $em->getMetadataFactory()->getAllMetadata();
            if (!empty($metadatas)) {
                $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
                $schemaTool->createSchema($metadatas);
            }

            $executor = new ORMExecutor($em);
        } else {
            $purger = new ORMPurger();

            $executor = new ORMExecutor($em, $purger);
            $executor->purge();
        }

        if (empty($classnames)) {
            return;
        }

        $classnames = (array) $classnames;
        foreach ($classnames as $classname) {
            $loader = new Loader($container);
            $loader->addFixture(new $classname());
            $executor->execute($loader->getFixtures(), true);
        }

        $connection->close();

        if (isset($backup)) {
            copy($name, $backup);
        }
    }

    /**
     * Creates an instance of a lightweight Http client.
     *
     * If $authentication is set to 'true' it will use the content of
     * 'liip_functional_test.authentication' to log in.
     *
     * @param boolean $authentication
     *
     * @return Client
     */
    protected function makeClient($authentication = false)
    {
        $params = array();
        if ($authentication) {
            if ($authentication === true) {
                $authentication = $this->getContainer()->getParameter('liip_functional_test.authentication');
            }

            $params = array('PHP_AUTH_USER' => $authentication['username'], 'PHP_AUTH_PW' => $authentication['password']);
        }

        return $this->createClient(array('environment' => 'test'), $params);
    }

    /**
     * Extracts the location from the given route.
     *
     * @param string $route  The name of the route
     * @param array $params  Set of parameters
     * @return string
     */
    protected function getUrl($route, $params)
    {
        return $this->getContainer()->get('router')->generate($route, $params);
    }

    /**
     * Checks the success state of a response
     *
     * @param Response $response Response object
     * @param bool $success to define whether the response is expected to be successful
     * @return void
     */
    public function isSuccessful($response, $success = true, $type = 'text/html')
    {
        try {
            $crawler = new Crawler();
            $crawler->addContent($response->getContent(), $type);
            if (! count($crawler->filter('title'))) {
                $title = ': ['.$response->getStatusCode().'] - '.$response->getContent();
            } else {
                $title = ': '.$crawler->filter('title')->text();
            }
        } catch (\Exception $e) {
            $title = ': '. $e;
        }

        if ($success) {
            $this->assertTrue($response->isSuccessful(), 'The Response was not successful'.$title);
        } else {
            $this->assertFalse($response->isSuccessful(), 'The Response was successful'.$title);
        }
    }

    /**
     * Executes a request on the given url and returns the response contents.
     *
     * This method also asserts the request was successful.
     *
     * @param string $path path of the requested page
     * @param string $method The HTTP method to use, defaults to GET
     * @param bool $authentication Whether to use authentication, defaults to false
     * @param bool $success to define whether the response is expected to be successful
     * @return string
     */
    public function fetchContent($path, $method = 'GET', $authentication = false, $success = true)
    {
        $client = $this->makeClient($authentication);
        $client->request($method, $path);

        $content = $client->getResponse()->getContent();
        $this->isSuccessful($client->getResponse(), $success);

        return $content;
    }

    /**
     * Executes a request on the given url and returns a Crawler object.
     *
     * This method also asserts the request was successful.
     *
     * @param string $path path of the requested page
     * @param string $method The HTTP method to use, defaults to GET
     * @param bool $authentication Whether to use authentication, defaults to false
     * @param bool $success to define whether the response is expected to be successful
     * @return Crawler
     */
    public function fetchCrawler($path, $method = 'GET', $authentication = false, $success = true)
    {
        $client = $this->makeClient($authentication);
        $crawler = $client->request($method, $path);

        $this->isSuccessful($client->getResponse(), $success);

        return $crawler;
    }
}
