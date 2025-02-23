Basic usage
===========

> [!TIP]
> Some methods provided by this bundle have been implemented in Symfony. Alternative ways will be shown below.

Use `$this->makeClient` to create a Client object. Client is a Symfony class
that can simulate HTTP requests to your controllers and then inspect the
results. It is covered by the [functional tests](http://symfony.com/doc/current/book/testing.html#functional-tests)
section of the Symfony documentation.

After making a request, use `assertStatusCode` to verify the HTTP status code.
If it fails it will display the last exception message or validation errors
encountered by the Client object.

If you are expecting validation errors, test them with `assertValidationErrors`.

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

> [!TIP]
> Instead of calling `$this->makeClient`, consider calling `createClient()` from Symfony's `WebTestCase`:

```php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyControllerTest extends WebTestCase
{
    public function testContact()
    {
        $client = static::createClient();
        $client->request('GET', '/contact');

        // …
    }
}
```

### Methods

#### Check HTTP status codes

##### isSuccessful()

Check that the request succeeded:

```php
$client = $this->makeClient();
$client->request('GET', '/contact');

// Successful HTTP request
$this->isSuccessful($client->getResponse());
```

> [!TIP]
> Call `assertResponseIsSuccessful()` from Symfony's `WebTestCase`:

```php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyControllerTest extends WebTestCase
{
    public function testContact()
    {
        $client = static::createClient();
        $client->request('GET', '/contact');

        self::assertResponseIsSuccessful();
    }
}
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

> [!TIP]
> Call `assertResponseStatusCodeSame()` from Symfony's `WebTestCase`:

```php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyControllerTest extends WebTestCase
{
    public function testContact()
    {
        $client = static::createClient();
        $client->request('GET', '/contact');

        self::assertResponseStatusCodeSame(302);
    }
}
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

> [!TIP]
> Use the crawler returned by `request()` from Symfony's `WebTestCase`:

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
    }
}
```

##### fetchContent()

Get the content of an URL:

```php
$content = $this->fetchContent('/contact');

// `filter()` can't be used since the output is HTML code, check the content directly
$this->assertStringContainsString(
    '<h1>LiipFunctionalTestBundle</h1>',
    $content
);
```

> [!TIP]
> Call `getResponse()->getContent()` from Symfony's `WebTestCase`:

```php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyControllerTest extends WebTestCase
{
    public function testContact()
    {
        $client = static::createClient();
        $client->request('GET', '/contact');

        $this->assertStringContainsString(
            '<h1>LiipFunctionalTestBundle</h1>',
            $client->getResponse()->getContent()
        );
    }
}
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
