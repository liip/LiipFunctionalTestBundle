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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Liip\FunctionalTestBundle\Utils\FixturesLoader;

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

    // RUN COMMAND
    protected $verbosityLevel;
    protected $decorated;

    /**
     * @var array
     */
    private $firewallLogins = array();

    protected static function getKernelClass()
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
     * @return \PHPUnit_Framework_MockObject_MockBuilder
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
     * @param array  $params
     * @param bool   $reuseKernel
     *
     * @return string
     */
    protected function runCommand($name, array $params = array(), $reuseKernel = false)
    {
        array_unshift($params, $name);

        if (!$reuseKernel) {
            if (null !== static::$kernel) {
                static::$kernel->shutdown();
            }

            $kernel = static::$kernel = $this->createKernel(array('environment' => $this->environment));
            $kernel->boot();
        } else {
            $kernel = $this->getContainer()->get('kernel');
        }

        $application = new Application($kernel);
        $application->setAutoExit(false);

        // @codeCoverageIgnoreStart
        if ('20301' === Kernel::VERSION_ID) {
            $params = $this->configureVerbosityForSymfony20301($params);
        }
        // @codeCoverageIgnoreEnd

        $input = new ArrayInput($params);
        $input->setInteractive(false);

        $fp = fopen('php://temp/maxmemory:'.$this->maxMemory, 'r+');
        $output = new StreamOutput($fp, $this->getVerbosityLevel(), $this->getDecorated());

        $application->run($input, $output);

        rewind($fp);

        return stream_get_contents($fp);
    }

    /**
     * Retrieves the output verbosity level.
     *
     * @see Symfony\Component\Console\Output\OutputInterface for available levels
     *
     * @return int
     *
     * @throws \OutOfBoundsException If the set value isn't accepted
     */
    protected function getVerbosityLevel()
    {
        // If `null`, is not yet set
        if (null === $this->verbosityLevel) {
            // Set the global verbosity level that is set as NORMAL by the TreeBuilder in Configuration
            $level = strtoupper($this->getContainer()->getParameter('liip_functional_test.command_verbosity'));
            $verbosity = '\Symfony\Component\Console\Output\StreamOutput::VERBOSITY_'.$level;

            $this->verbosityLevel = constant($verbosity);
        }

        // If string, it is set by the developer, so check that the value is an accepted one
        if (is_string($this->verbosityLevel)) {
            $level = strtoupper($this->verbosityLevel);
            $verbosity = '\Symfony\Component\Console\Output\StreamOutput::VERBOSITY_'.$level;

            if (!defined($verbosity)) {
                throw new \OutOfBoundsException(
                    sprintf('The set value "%s" for verbosityLevel is not valid. Accepted are: "quiet", "normal", "verbose", "very_verbose" and "debug".', $level)
                    );
            }

            $this->verbosityLevel = constant($verbosity);
        }

        return $this->verbosityLevel;
    }

    /**
     * In Symfony 2.3.1 the verbosity level has to be set through {Symfony\Component\Console\Input\ArrayInput} and not
     * in {Symfony\Component\Console\Output\OutputInterface}.
     *
     * This method builds $params to be passed to {Symfony\Component\Console\Input\ArrayInput}.
     *
     * @codeCoverageIgnore
     *
     * @param array $params
     *
     * @return array
     */
    private function configureVerbosityForSymfony20301(array $params)
    {
        switch ($this->getVerbosityLevel()) {
            case OutputInterface::VERBOSITY_QUIET:
                $params['-q'] = '-q';
                break;

            case OutputInterface::VERBOSITY_VERBOSE:
                $params['-v'] = '';
                break;

            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $params['-vv'] = '';
                break;

            case OutputInterface::VERBOSITY_DEBUG:
                $params['-vvv'] = '';
                break;
        }

        return $params;
    }

    public function setVerbosityLevel($level)
    {
        $this->verbosityLevel = $level;
    }

    /**
     * Retrieves the flag indicating if the output should be decorated or not.
     *
     * @return bool
     */
    protected function getDecorated()
    {
        if (null === $this->decorated) {
            // Set the global decoration flag that is set to `true` by the TreeBuilder in Configuration
            $this->decorated = $this->getContainer()->getParameter('liip_functional_test.command_decoration');
        }

        // Check the local decorated flag
        if (false === is_bool($this->decorated)) {
            throw new \OutOfBoundsException(
                sprintf('`WebTestCase::decorated` has to be `bool`. "%s" given.', gettype($this->decorated))
            );
        }

        return $this->decorated;
    }

    public function isDecorated($decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * Get an instance of the dependency injection container.
     * (this creates a kernel *without* parameters).
     *
     * @return ContainerInterface
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
                'environment' => $this->environment,
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
     * @param array  $classNames   List of fully qualified class names of fixtures to load
     * @param string $omName       The name of object manager to use
     * @param string $registryName The service id of manager registry to use
     * @param int    $purgeMode    Sets the ORM purge mode
     *
     * @return null|AbstractExecutor
     */
    protected function loadFixtures(array $classNames, $omName = null, $registryName = 'doctrine', $purgeMode = null)
    {
        $loader = new FixturesLoader($this->getContainer());

        return $loader->loadFixtures($classNames, $omName, $registryName, $purgeMode);
    }

    /**
     * @param array  $paths        Either symfony resource locators (@ BundleName/etc) or actual file paths
     * @param bool   $append
     * @param null   $omName
     * @param string $registryName
     *
     * @return array
     *
     * @throws \BadMethodCallException
     */
    public function loadFixtureFiles(array $paths = array(), $append = false, $omName = null, $registryName = 'doctrine')
    {
        $loader = new FixturesLoader($this->getContainer());

        return $loader->loadFixtureFiles($paths, $append, $omName, $registryName);
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
     *
     * @return WebTestCase
     */
    protected function postFixtureRestore()
    {
    }

    /**
     * Callback function to be executed before Schema restore.
     *
     * @param ObjectManager            $manager             The object manager
     * @param ProxyReferenceRepository $referenceRepository The reference repository
     *
     * @return WebTestCase
     */
    protected function preFixtureRestore(ObjectManager $manager, ProxyReferenceRepository $referenceRepository)
    {
    }

    /**
     * Callback function to be executed after save of references.
     *
     * @param ObjectManager    $manager        The object manager
     * @param AbstractExecutor $executor       Executor of the data fixtures
     * @param string           $backupFilePath Path of file used to backup the references of the data fixtures
     *
     * @return WebTestCase
     */
    protected function postReferenceSave(ObjectManager $manager, AbstractExecutor $executor, $backupFilePath)
    {
    }

    /**
     * Callback function to be executed before save of references.
     *
     * @param ObjectManager    $manager        The object manager
     * @param AbstractExecutor $executor       Executor of the data fixtures
     * @param string           $backupFilePath Path of file used to backup the references of the data fixtures
     *
     * @return WebTestCase
     */
    protected function preReferenceSave(ObjectManager $manager, AbstractExecutor $executor, $backupFilePath)
    {
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
     * @param bool|array $authentication
     * @param array      $params
     *
     * @return Client
     */
    protected function makeClient($authentication = false, array $params = array())
    {
        if ($authentication) {
            if ($authentication === true) {
                $authentication = array(
                    'username' => $this->getContainer()
                        ->getParameter('liip_functional_test.authentication.username'),
                    'password' => $this->getContainer()
                        ->getParameter('liip_functional_test.authentication.password'),
                );
            }

            $params = array_merge($params, array(
                'PHP_AUTH_USER' => $authentication['username'],
                'PHP_AUTH_PW' => $authentication['password'],
            ));
        }

        $client = static::createClient(array('environment' => $this->environment), $params);

        if ($this->firewallLogins) {
            // has to be set otherwise "hasPreviousSession" in Request returns false.
            $options = $client->getContainer()->getParameter('session.storage.options');

            if (!$options || !isset($options['name'])) {
                throw new \InvalidArgumentException('Missing session.storage.options#name');
            }

            $session = $client->getContainer()->get('session');
            // Since the namespace of the session changed in symfony 2.1, instanceof can be used to check the version.
            if ($session instanceof Session) {
                $session->setId(uniqid());
            }

            $client->getCookieJar()->set(new Cookie($options['name'], $session->getId()));

            /** @var $user UserInterface */
            foreach ($this->firewallLogins as $firewallName => $user) {
                $token = $this->createUserToken($user, $firewallName);

                // BC: security.token_storage is available on Symfony 2.6+
                // see http://symfony.com/blog/new-in-symfony-2-6-security-component-improvements
                if ($client->getContainer()->has('security.token_storage')) {
                    $tokenStorage = $client->getContainer()->get('security.token_storage');
                } else {
                    // This block will never be reached with Symfony 2.6+
                    // @codeCoverageIgnoreStart
                    $tokenStorage = $client->getContainer()->get('security.context');
                    // @codeCoverageIgnoreEnd
                }

                $tokenStorage->setToken($token);
                $session->set('_security_'.$firewallName, serialize($token));
            }

            $session->save();
        }

        return $client;
    }

    /**
     * Create User Token.
     *
     * Factory method for creating a User Token object for the firewall based on
     * the user object provided. By default it will be a Username/Password
     * Token based on the user's credentials, but may be overridden for custom
     * tokens in your applications.
     *
     * @param UserInterface $user         The user object to base the token off of
     * @param string        $firewallName name of the firewall provider to use
     *
     * @return TokenInterface The token to be used in the security context
     */
    protected function createUserToken(UserInterface $user, $firewallName)
    {
        return new UsernamePasswordToken(
            $user,
            null,
            $firewallName,
            $user->getRoles()
        );
    }

    /**
     * Extracts the location from the given route.
     *
     * @param string $route    The name of the route
     * @param array  $params   Set of parameters
     * @param int    $absolute
     *
     * @return string
     */
    protected function getUrl($route, $params = array(), $absolute = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->getContainer()->get('router')->generate($route, $params, $absolute);
    }

    /**
     * Checks the success state of a response.
     *
     * @param Response $response Response object
     * @param bool     $success  to define whether the response is expected to be successful
     * @param string   $type
     */
    public function isSuccessful(Response $response, $success = true, $type = 'text/html')
    {
        $this->getContainer()->get('liip_functional_test.http_assertions')
            ->isSuccessful($response, $success, $type);
    }

    /**
     * Executes a request on the given url and returns the response contents.
     *
     * This method also asserts the request was successful.
     *
     * @param string $path           path of the requested page
     * @param string $method         The HTTP method to use, defaults to GET
     * @param bool   $authentication Whether to use authentication, defaults to false
     * @param bool   $success        to define whether the response is expected to be successful
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
     * @param string $path           path of the requested page
     * @param string $method         The HTTP method to use, defaults to GET
     * @param bool   $authentication Whether to use authentication, defaults to false
     * @param bool   $success        Whether the response is expected to be successful
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
     * @param string        $firewallName
     *
     * @return WebTestCase
     */
    public function loginAs(UserInterface $user, $firewallName)
    {
        $this->firewallLogins[$firewallName] = $user;

        return $this;
    }

    /**
     * Asserts that the HTTP response code of the last request performed by
     * $client matches the expected code. If not, raises an error with more
     * information.
     *
     * @param $expectedStatusCode
     * @param Client $client
     */
    public function assertStatusCode($expectedStatusCode, Client $client)
    {
        $this->getContainer()->get('liip_functional_test.http_assertions')
            ->assertStatusCode($expectedStatusCode, $client);
    }

    /**
     * Assert that the last validation errors within $container match the
     * expected keys.
     *
     * @param array              $expected  A flat array of field names
     * @param ContainerInterface $container
     */
    public function assertValidationErrors(array $expected, ContainerInterface $container)
    {
        $this->getContainer()->get('liip_functional_test.http_assertions')
            ->assertValidationErrors($expected, $container);
    }
}
