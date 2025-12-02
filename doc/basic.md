Basic usage
===========

> [!TIP]
> Some methods provided by this bundle have been [implemented in Symfony](https://symfony.com/doc/current/testing.html#application-tests). Alternative ways will be shown below.

Use `$this->createClientWithParams()` to create a Client object. Client is a Symfony class
that can simulate HTTP requests to your controllers and then inspect the
results. It is covered by the [functional tests](http://symfony.com/doc/current/book/testing.html#functional-tests)
section of the Symfony documentation.

If you are expecting validation errors, test them with `assertValidationErrors`.

```php
use Liip\FunctionalTestBundle\Test\WebTestCase;

class MyControllerTest extends WebTestCase
{
    public function testContact()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/contact');
        self::assertResponseStatusCodeSame(200);

        $form = $crawler->selectButton('Submit')->form();
        $crawler = $client->submit($form);

        // We should get a validation error for the empty fields.
        self::assertResponseStatusCodeSame(200);
        $this->assertValidationErrors(['data.email', 'data.message'], $client->getContainer());

        // Try again with with the fields filled out.
        $form = $crawler->selectButton('Submit')->form();
        $form->setValues(['contact[email]' => 'nobody@example.com', 'contact[message]' => 'Hello']);
        $client->submit($form);
        self::assertResponseStatusCodeSame(302);
    }
}
```

### Methods

#### Get Crawler or content

##### fetchCrawler()

Get a [Crawler](http://api.symfony.com/master/Symfony/Component/DomCrawler/Crawler.html) instance from a URL:

```php
$crawler = $this->fetchCrawler('/contact');

// There is one <body> tag
$this->assertSame(
    1,
    $crawler->filter('html > body')->count()
);
```

> [!TIP]
> Use the crawler returned by `request()` or `assertSelectorCount()` from Symfony's `WebTestCase` ([documentation](https://symfony.com/doc/current/testing.html#crawler-assertions)):

```php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyControllerTest extends WebTestCase
{
    public function testContact()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        // There is one <body> tag
        $this->assertSame(
            1,
            $crawler->filter('html > body')->count()
        );
        
        // or
        $client = static::createClient();
        $client->request('GET', '/contact');
        
        // There is one <body> tag
        self::assertSelectorCount(1, 'html > body');
    }
}
```

#### Routing

##### getURL()

Generate a URL from a route:

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

> [!TIP]
> Consider hard-coding the URLs in the test: it will ensure that if a route is changed,
> the test will fail, so you'll know that there is a Breaking Change.

#### Mock services

##### setServiceMock()

Mock a service:

```php
// mock a service
$client = static::createClient();
$mock = $this->getServiceMockBuilder(SomeService::class)->getMock();
$mock->expects($this->once())->method('get')->willReturn('mocked service');
$this->setServiceMock(static::$kernel->getContainer(), SomeService::class, $mock);

// the service is mocked
$client->request('GET', '/service');
$this->assertSame('mocked service', $client->getResponse()->getContent());
```

There are some additional conditions to be aware of when mocking services:

- The service you want to mock must be public.
You can set all services to be public in the configuration of the test environment in the `services.yaml` file:
```yaml
when@test:
  services:
    _defaults:
      public: true
      autowire: true
      autoconfigure: true
```

- The service must be mocked before any dependent services are used.
Otherwise, you must restart the kernel to mock the service.
```php
...
// run some tests with the original service
$client->request('GET', '/service');
$this->assertSame('non mocked output', $client->getResponse()->getContent());

// kernel reboot is required to mock the service if the service is already loaded
// because the service we want to mock is already injected into the dependent services
static::ensureKernelShutdown();
// boot the kernel again
$client = static::createClient();

// mock the service as shown above
...
```

- When mocking a service for command testing,
you must set the `$reuseKernel` argument to `true` in the `runCommand` method call.
See example code [here](./command.md#service-mock).

← [Installation](./installation.md) • [Command test](./command.md) →
