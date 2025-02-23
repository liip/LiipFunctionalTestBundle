Command Tests
=============

If you need to test commands, you might need to tweak the output to your needs.
You can adjust the command verbosity:
```yaml
# app/config/config_test.yml
liip_functional_test:
    command_verbosity: debug
```
Supported values are ```quiet```, ```normal```, ```verbose```, ```very_verbose```
and ```debug```. The default value is ```normal```.

You can also configure this on a per-test basis:
```php
use Liip\FunctionalTestBundle\Test\WebTestCase;

class MyTestCase extends WebTestCase {

    public function myTest() {
        $this->verbosityLevel = 'debug';
        $this->runCommand('myCommand');
    }
}
```

Depending where your tests are running, you might want to disable the output
decorator:
```yaml
# app/config/config_test.yml
liip_functional_test:
    command_decoration: false
```
The default value is true.

You can also configure this on a per-test basis:
```php
use Liip\FunctionalTestBundle\Test\WebTestCase;

class MyTestCase extends WebTestCase {

    public function myTest() {
        $this->decorated = false;
        $this->runCommand('myCommand');
    }
}
```

### Service mock

It is important to set the `$reuseKernel` argument to `true` in the `runCommand` method call
if you want to keep your services mocked in the command.

```php
use Liip\FunctionalTestBundle\Test\WebTestCase;
use App\Service\YourService;

class MyTestCase extends WebTestCase
{
    public function myTest()
    {
        // boot kernel
        $kernel = static::bootKernel(['environment' => static::$env]);
        $container = $kernel->getContainer();
        // create a service mock
        $mock = $this->getServiceMockBuilder(YourService::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $mock->expects($this->once())
            ->method('getId')
            ->willReturn(42);
        // set the service mock in the container
        $this->setServiceMock($container, YourService::class, $mock);
        // now you have mocked service App\Service\YourService in your command
        $this->runCommand('myCommand', [], true);
    }
}
```

← [Basic usage](./basic.md) • [Logged client](./logged.md) →
