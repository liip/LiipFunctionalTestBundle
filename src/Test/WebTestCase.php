<?php

declare(strict_types=1);

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Test;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Liip\FunctionalTestBundle\Utils\HttpAssertions;
use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Lea Haensenberger
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
abstract class WebTestCase extends BaseWebTestCase
{
    protected $environment = 'test';

    protected $containers;

    // 5 * 1024 * 1024 KB
    protected $maxMemory = 5242880;

    // RUN COMMAND
    protected $verbosityLevel;

    protected $decorated;

    /**
     * @var array
     */
    private $firewallLogins = [];

    /**
     * Creates a mock object of a service identified by its id.
     *
     * @param string $id
     *
     * @return MockBuilder
     */
    protected function getServiceMockBuilder(string $id): MockBuilder
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
    protected function runCommand(string $name, array $params = [], bool $reuseKernel = false): string
    {
        array_unshift($params, $name);

        if (!$reuseKernel) {
            if (null !== static::$kernel) {
                static::$kernel->shutdown();
            }

            $kernel = static::$kernel = $this->createKernel(['environment' => $this->environment]);
            $kernel->boot();
        } else {
            $kernel = $this->getContainer()->get('kernel');
        }

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput($params);
        $input->setInteractive(false);

        $fp = fopen('php://temp/maxmemory:'.$this->maxMemory, 'r+');
        $verbosityLevel = $this->getVerbosityLevel();

        $this->setVerbosityLevelEnv($verbosityLevel);
        $output = new StreamOutput($fp, $verbosityLevel, $this->getDecorated());

        $application->run($input, $output);

        rewind($fp);

        return stream_get_contents($fp);
    }

    /**
     * Retrieves the output verbosity level.
     *
     * @see \Symfony\Component\Console\Output\OutputInterface for available levels
     *
     * @throws \OutOfBoundsException If the set value isn't accepted
     *
     * @return int
     */
    protected function getVerbosityLevel(): int
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

    public function setVerbosityLevel($level): void
    {
        $this->verbosityLevel = $level;
    }

    /**
     * Set verbosity for Symfony 3.4+.
     *
     * @see https://github.com/symfony/symfony/pull/24425
     *
     * @param $level
     */
    private function setVerbosityLevelEnv($level): void
    {
        putenv('SHELL_VERBOSITY='.$level);
    }

    /**
     * Retrieves the flag indicating if the output should be decorated or not.
     *
     * @return bool
     */
    protected function getDecorated(): bool
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

    public function isDecorated(bool $decorated): void
    {
        $this->decorated = $decorated;
    }

    /**
     * Get an instance of the dependency injection container.
     * (this creates a kernel *without* parameters).
     *
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        $cacheKey = $this->environment;
        if (empty($this->containers[$cacheKey])) {
            $options = [
                'environment' => $this->environment,
            ];
            $kernel = $this->createKernel($options);
            $kernel->boot();

            $this->containers[$cacheKey] = $kernel->getContainer();
        }

        return $this->containers[$cacheKey];
    }

    protected function loadFixtures(array $classNames = [], bool $append = false, ?string $omName = null, string $registryName = 'doctrine', ?int $purgeMode = null): ?AbstractExecutor
    {
        return $this->getContainer()->get('liip_functional_test.fixtures_loader')->loadFixtures($classNames, $append, $omName, $registryName, $purgeMode);
    }

    public function loadFixtureFiles(array $paths = [], bool $append = false, ?string $omName = null, $registryName = 'doctrine', ?int $purgeMode = null)
    {
        return $this->getContainer()->get('liip_functional_test.fixtures_loader')->loadFixtureFiles($paths, $append, $omName, $registryName, $purgeMode);
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
    protected function makeClient($authentication = false, array $params = []): Client
    {
        if ($authentication) {
            if (true === $authentication) {
                $authentication = [
                    'username' => $this->getContainer()
                        ->getParameter('liip_functional_test.authentication.username'),
                    'password' => $this->getContainer()
                        ->getParameter('liip_functional_test.authentication.password'),
                ];
            }

            $params = array_merge($params, [
                'PHP_AUTH_USER' => $authentication['username'],
                'PHP_AUTH_PW' => $authentication['password'],
            ]);
        }

        $client = static::createClient(['environment' => $this->environment], $params);

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

                $tokenStorage = $client->getContainer()->get('security.token_storage');

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
    protected function createUserToken(UserInterface $user, string $firewallName): TokenInterface
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
    protected function getUrl(string $route, array $params = [], int $absolute = UrlGeneratorInterface::ABSOLUTE_PATH): string
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
    public function isSuccessful(Response $response, $success = true, $type = 'text/html'): void
    {
        HttpAssertions::isSuccessful($response, $success, $type);
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
    public function fetchContent(string $path, string $method = 'GET', bool $authentication = false, bool $success = true): string
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
    public function fetchCrawler(string $path, string $method = 'GET', bool $authentication = false, bool $success = true): Crawler
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
    public function loginAs(UserInterface $user, string $firewallName): self
    {
        $this->firewallLogins[$firewallName] = $user;

        return $this;
    }

    /**
     * Asserts that the HTTP response code of the last request performed by
     * $client matches the expected code. If not, raises an error with more
     * information.
     *
     * @param int    $expectedStatusCode
     * @param Client $client
     */
    public function assertStatusCode(int $expectedStatusCode, Client $client): void
    {
        HttpAssertions::assertStatusCode($expectedStatusCode, $client);
    }

    /**
     * Assert that the last validation errors within $container match the
     * expected keys.
     *
     * @param array              $expected  A flat array of field names
     * @param ContainerInterface $container
     */
    public function assertValidationErrors(array $expected, ContainerInterface $container): void
    {
        HttpAssertions::assertValidationErrors($expected, $container);
    }

    /**
     * @param array $excludedDoctrineTables
     */
    public function setExcludedDoctrineTables(array $excludedDoctrineTables): void
    {
        $this->excludedDoctrineTables = $excludedDoctrineTables;
    }
}
