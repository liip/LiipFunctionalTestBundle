Create an already logged client
===============================

> [!TIP]
> Some methods provided by this bundle have been implemented in Symfony. Alternative ways will be shown below.

The `WebTestCase` provides a conveniency method to create an already logged in client using the first parameter of
`WebTestCase::makeClient()`.

You have three alternatives to create an already logged in client:

1. Use the `liip_functional_test.authentication` key in the `config_test.yml` file;
2. Pass an array with login parameters directly when you call the method;
3. Use the method `WebTestCase::loginClient()`;

> [!TIP]
> Since Symfony 5.1, [`loginUser()`](https://symfony.com/doc/5.x/testing.html#logging-in-users-authentication) can be used.

### Logging in a user from the `config_test.yml` file

You can set the credentials for your test user in your `config_test.yml` file:

```yaml
liip_functional_test:
    authentication:
        username: "a valid username"
        password: "the password of that user"
```

This way using `$client = $this->makeClient(true);` your client will be automatically logged in.

### Logging in a user passing the credentials directly in the test method

You can log in a user directly from your test method by simply passing an array as the first parameter of
`WebTestCase::makeClient()`:

```php
$credentials = array(
    'username' => 'a valid username',
    'password' => 'a valid password'
);

$client = $this->makeClient($credentials);
```

### Logging in a user using `WebTestCase::loginClient()`

To use the method `WebTestCase::loginClient()` you have to [return the repository containing all references set in the
fixtures](#referencing-fixtures-in-tests) using the method `getReferenceRepository()` and pass the reference of the `User`
object to the method `WebTestCase::loginClient()`.

```php
$client = $this->makeClient();
$fixtures = $this->loadFixtures(array(
    'AppBundle\DataFixtures\ORM\LoadUserData'
))->getReferenceRepository();

$this->loginClient($client, $fixtures->getReference('account-alpha'), 'main');
```

Remember that `WebTestCase::loginClient()` accepts objects that implement the interface `Symfony\Component\Security\Core\User\UserInterface`. 

**If you get the error message *"Missing session.storage.options#name"***, you have to simply add to your
[`config_test.yml`](https://github.com/liip/LiipFunctionalTestBundle/blob/master/Tests/App/config.yml#L16)
file the key `name`:

```yaml
framework:
    ...
    session:
        # handler_id set to null will use default session handler from php.ini
        handler_id:  ~
        storage_id: session.storage.mock_file
        name: MOCKSESSID
```

### Recommendations to use already logged in clients

As [recommended by the Symfony Cookbook](http://symfony.com/doc/current/cookbook/testing/http_authentication.html) in
the chapter about Testing, it is a good idea to to use HTTP Basic Auth for you tests. You can configure the
authentication method in your `config_test.yml`:

```yaml
# The best practice in symfony is to put a HTTP basic auth
# for the firewall in test env, so that not to have to
# make a request to the login form every single time.
# http://symfony.com/doc/current/cookbook/testing/http_authentication.html
security:
    firewalls:
        NAME_OF_YOUR_FIREWALL:
            http_basic: ~
```

### Final notes

For more details, you can check the implementation of `WebTestCase` in that bundle.

← [Command test](./command.md) • [Query counter](./query.md) →
