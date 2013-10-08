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

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Client;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\ClassLoader\DebugClassLoader;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;

use Doctrine\DBAL\Driver\PDOSqlite\Driver as SqliteDriver;

use Doctrine\ORM\Tools\SchemaTool;

use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;
use Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader as SymfonyFixturesLoader;
use Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader as DoctrineFixturesLoader;

/**
 * @author Lea Haensenberger
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
abstract class WebTestCase extends BaseWebTestCase
{
    protected $environment = 'test';
    protected $containers;
    protected $kernelDir;
    // 5 * 1024 * 1024 KB
    protected $maxMemory = 5242880;

    /**
     * @var array
     */
    private $firewallLogins = array();

    /**
     * @var array
     */
    static private $cachedMetadatas = array();

    static protected function getKernelClass()
    {
        $dir = isset($_SERVER['KERNEL_DIR']) ? $_SERVER['KERNEL_DIR'] : self::getPhpUnitXmlDir();

        list($appname) = explode('\\', get_called_class());

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
     *
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
     *
     * @return string
     */
    protected function runCommand($name, array $params = array())
    {
        array_unshift($params, $name);

        $kernel = $this->createKernel(array('environment' => $this->environment));
        $kernel->boot();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput($params);
        $input->setInteractive(false);

        $fp = fopen('php://temp/maxmemory:'.$this->maxMemory, 'r+');
        $output = new StreamOutput($fp);

        $application->run($input, $output);

        rewind($fp);
        return stream_get_contents($fp);
    }

    /**
     * Get an instance of the dependency injection container.
     * (this creates a kernel *without* parameters).
     *
     * @return object
     */
    protected function getContainer()
    {
        if (!empty($this->kernelDir)) {
            $tmpKernelDir = isset($_SERVER['KERNEL_DIR']) ? $_SERVER['KERNEL_DIR'] : null;
            $_SERVER['KERNEL_DIR'] = getcwd().$this->kernelDir;
        }

        $cacheKey = $this->kernelDir.'|'.$this->environment;
        if (empty($this->containers[$cacheKey])) {
            $options = array(
                'environment' => $this->environment
            );
            $kernel = $this->createKernel($options);
            $kernel->boot();

            $this->containers[$cacheKey] = $kernel->getContainer();
        }

        if (isset($tmpKernelDir)) {
            $_SERVER['KERNEL_DIR'] = $tmpKernelDir;
        }

        return $this->containers[$cacheKey];
    }

    /**
     * This function finds the time when the data blocks of a class definition
     * file were being written to, that is, the time when the content of the
     * file was changed.
     *
     * @param string $class The fully qualified class name of the fixture class to
     * check modification date on.
     *
     * @return \DateTime|null
     */
    protected function getFixtureLastModified($class)
    {
        $lastModifiedDateTime = null;

        $reflClass = new \ReflectionClass($class);
        $classFileName = $reflClass->getFileName();

        if (file_exists($classFileName)) {
            $lastModifiedDateTime = new \DateTime();
            $lastModifiedDateTime->setTimestamp(filemtime($classFileName));
        }

        return $lastModifiedDateTime;
    }

    /**
     * Determine if the Fixtures that define a database backup have been
     * modified since the backup was made.
     *
     * @param array  $classNames The fixture classnames to check
     * @param string $backup     The fixture backup SQLite database file path
     *
     * @return bool TRUE if the backup was made since the modifications to the
     * fixtures; FALSE otherwise
     */
    protected function isBackupUpToDate(array $classNames, $backup)
    {
        $backupLastModifiedDateTime = new \DateTime();
        $backupLastModifiedDateTime->setTimestamp(filemtime($backup));

        foreach ($classNames as &$className) {
            $fixtureLastModifiedDateTime = $this->getFixtureLastModified($className);
            if ($backupLastModifiedDateTime < $fixtureLastModifiedDateTime) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set the database to the provided fixtures.
     *
     * Drops the current database and then loads fixtures using the specified
     * classes. The parameter is a list of fully qualified class names of
     * classes that implement Doctrine\Common\DataFixtures\FixtureInterface
     * so that they can be loaded by the DataFixtures Loader::addFixture
     *
     * When using SQLite this method will automatically make a copy of the
     * loaded schema and fixtures which will be restored automatically in
     * case the same fixture classes are to be loaded again. Caveat: changes
     * to references and/or identities may go undetected.
     *
     * Depends on the doctrine data-fixtures library being available in the
     * class path.
     *
     * @param array $classNames List of fully qualified class names of fixtures to load
     * @param string $omName The name of object manager to use
     * @param string $registryName The service id of manager registry to use
     * @param int $purgeMode Sets the ORM purge mode
     *
     * @return null|Doctrine\Common\DataFixtures\Executor\AbstractExecutor
     */
    protected function loadFixtures(array $classNames, $omName = null, $registryName = 'doctrine', $purgeMode = null)
    {
        $container = $this->getContainer();
        $registry = $container->get($registryName);
        if ($registry instanceof ManagerRegistry) {
            $om = $registry->getManager($omName);
            $type = $registry->getName();
        } else {
            $om = $registry->getEntityManager($omName);
            $type = 'ORM';
        }

        $executorClass = 'Doctrine\\Common\\DataFixtures\\Executor\\'.$type.'Executor';
        $referenceRepository = new ProxyReferenceRepository($om);
        $cacheDriver = $om->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }

        if ('ORM' === $type) {
            $connection = $om->getConnection();
            if ($connection->getDriver() instanceof SqliteDriver) {
                $params = $connection->getParams();
                if (isset($params['master'])) {
                    $params = $params['master'];
                }

                $name = isset($params['path']) ? $params['path'] : (isset($params['dbname']) ? $params['dbname'] : false);
                if (!$name) {
                    throw new \InvalidArgumentException("Connection does not contain a 'path' or 'dbname' parameter and cannot be dropped.");
                }

                if (!isset(self::$cachedMetadatas[$omName])) {
                    self::$cachedMetadatas[$omName] = $om->getMetadataFactory()->getAllMetadata();
                }
                $metadatas = self::$cachedMetadatas[$omName];

                if ($container->getParameter('liip_functional_test.cache_sqlite_db')) {
                    $backup = $container->getParameter('kernel.cache_dir') . '/test_' . md5(serialize($metadatas) . serialize($classNames)) . '.db';
                    if (file_exists($backup) && file_exists($backup.'.ser') && $this->isBackupUpToDate($classNames, $backup)) {
                        $om->flush();
                        $om->clear();

                        $executor = new $executorClass($om);
                        $executor->setReferenceRepository($referenceRepository);
                        $executor->getReferenceRepository()->load($backup);

                        copy($backup, $name);

                        $this->postFixtureRestore();

                        return $executor;
                    }
                }

                // TODO: handle case when using persistent connections. Fail loudly?
                $schemaTool = new SchemaTool($om);
                $schemaTool->dropDatabase($name);
                if (!empty($metadatas)) {
                    $schemaTool->createSchema($metadatas);
                }
                $this->postFixtureSetup();

                $executor = new $executorClass($om);
                $executor->setReferenceRepository($referenceRepository);
            }
        }

        if (empty($executor)) {
            $purgerClass = 'Doctrine\\Common\\DataFixtures\\Purger\\'.$type.'Purger';
            $purger = new $purgerClass();
            if (null !== $purgeMode) {
                $purger->setPurgeMode($purgeMode);
            }

            $executor = new $executorClass($om, $purger);
            $executor->setReferenceRepository($referenceRepository);
            $executor->purge();
        }

        $loader = $this->getFixtureLoader($container, $classNames);

        $executor->execute($loader->getFixtures(), true);

        if (isset($name) && isset($backup)) {
            $executor->getReferenceRepository()->save($backup);
            copy($name, $backup);
        }

        return $executor;
    }

    /**
     * Callback function to be executed after Schema creation.
     * Use this to execute acl:init or other things necessary.
     */
    protected function postFixtureSetup()
    {

    }

    /**
     * Callback function to be executed after Schema restore.
     */
    protected function postFixtureRestore()
    {

    }

    /**
     * Retrieve Doctrine DataFixtures loader.
     *
     * @param ContainerInterface $container
     * @param array $classNames
     *
     * @return \Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader
     */
    protected function getFixtureLoader(ContainerInterface $container, array $classNames)
    {
        $loader = class_exists('Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader')
            ? new DataFixturesLoader($container)
            : (class_exists('Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader')
                ? new DoctrineFixturesLoader($container)
                : new SymfonyFixturesLoader($container));

        foreach ($classNames as $className) {
            $this->loadFixtureClass($loader, $className);
        }

        return $loader;
    }

    /**
     * Load a data fixture class.
     *
     * @param \Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader $loader
     * @param string $className
     */
    protected function loadFixtureClass($loader, $className)
    {
        $fixture = new $className();

        if ($loader->hasFixture($fixture)) {
            unset($fixture);
            return;
        }

        $loader->addFixture($fixture);

        if ($fixture instanceof DependentFixtureInterface) {
            foreach ($fixture->getDependencies() as $dependency) {
                $this->loadFixtureClass($loader, $dependency);
            }
        }
    }

    /**
     * Creates an instance of a lightweight Http client.
     *
     * If $authentication is set to 'true' it will use the content of
     * 'liip_functional_test.authentication' to log in.
     *
     * $params can be used to pass headers to the client, note that they have
     * to follow the naming format used in $_SERVER.
     * Example: 'HTTP_X_REQUESTED_WITH' instead of 'X-Requested-With'
     *
     * @param boolean $authentication
     * @param array   $params
     *
     * @return Client
     */
    protected function makeClient($authentication = false, array $params = array())
    {
        if ($authentication) {
            if ($authentication === true) {
                $authentication = $this->getContainer()->getParameter('liip_functional_test.authentication');
            }

            $params = array_merge($params, array(
                'PHP_AUTH_USER' => $authentication['username'],
                'PHP_AUTH_PW'   => $authentication['password']
            ));
        }

        $client = static::createClient(array('environment' => $this->environment), $params);

        if ($this->firewallLogins) {
            // has to be set otherwise "hasPreviousSession" in Request returns false.
            $options = $client->getContainer()->getParameter('session.storage.options');

            if (!$options || !isset($options['name'])) {
                throw new \InvalidArgumentException("Missing session.storage.options#name");
            }

            $session = $client->getContainer()->get('session');
            // Since the namespace of the session changed in symfony 2.1, instanceof can be used to check the version.
            if ($session instanceof Session) {
                $session->setId(uniqid());
            }

            $client->getCookieJar()->set(new Cookie($options['name'], $session->getId()));

            /** @var $user UserInterface */
            foreach ($this->firewallLogins as $firewallName => $user) {
                $token = new UsernamePasswordToken($user, null, $firewallName, $user->getRoles());

                $client->getContainer()->get('security.context')->setToken($token);
                $session->set('_security_' . $firewallName, serialize($token));
            }

            $session->save();
        }

        return $client;
    }

    /**
     * Extracts the location from the given route.
     *
     * @param string $route  The name of the route
     * @param array $params  Set of parameters
     * @param boolean $absolute
     *
     * @return string
     */
    protected function getUrl($route, $params = array(), $absolute = false)
    {
        return $this->getContainer()->get('router')->generate($route, $params, $absolute);
    }

    /**
     * Checks the success state of a response
     *
     * @param Response $response Response object
     * @param bool $success to define whether the response is expected to be successful
     * @param string $type
     *
     * @return void
     */
    public function isSuccessful($response, $success = true, $type = 'text/html')
    {
        try {
            $crawler = new Crawler();
            $crawler->addContent($response->getContent(), $type);
            if (! count($crawler->filter('title'))) {
                $title = '['.$response->getStatusCode().'] - '.$response->getContent();
            } else {
                $title = $crawler->filter('title')->text();
            }
        } catch (\Exception $e) {
            $title = $e->getMessage();
        }

        if ($success) {
            $this->assertTrue($response->isSuccessful(), 'The Response was not successful: '.$title);
        } else {
            $this->assertFalse($response->isSuccessful(), 'The Response was successful: '.$title);
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
     *
     * @return string
     */
    public function fetchContent($path, $method = 'GET', $authentication = false, $success = true)
    {
        $client = $this->makeClient($authentication);
        $client->request($method, $path);

        $content = $client->getResponse()->getContent();
        if (is_bool($success)) {
            $this->isSuccessful($client->getResponse(), $success);
        }

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
     * @param bool $success Whether the response is expected to be successful
     *
     * @return Crawler
     */
    public function fetchCrawler($path, $method = 'GET', $authentication = false, $success = true)
    {
        $client = $this->makeClient($authentication);
        $crawler = $client->request($method, $path);

        $this->isSuccessful($client->getResponse(), $success);

        return $crawler;
    }

    /**
     * @param UserInterface $user
     *
     * @return WebTestCase
     */
    public function loginAs(UserInterface $user, $firewallName)
    {
        $this->firewallLogins[$firewallName] = $user;
        return $this;
    }
}
