# Caveats

## [Semantical Error] The annotation "@group" in method …::test…() was never imported

Full error:

> Doctrine\Common\Annotations\AnnotationException: [Semantical Error] The annotation "@group" in method Tests\…::test…() was never imported. Did you maybe forget to add a "use" statement for this annotation?

PHPUnit annotations like `@group controller` can cause this issue.

There are 2 ways to fix this issue:

### Add annotation to the class:

```diff
 <?php
 
 namespace Acme\Tests\Command;
 
+use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
 use Liip\FunctionalTestBundle\Test\WebTestCase;
 
+/**
+ * @IgnoreAnnotation("group")
+ */
 class AcmeTest extends WebTestCase
```

### Use a bootstrap file:

Create `tests/bootstrap.php` file:

```php
<?php

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

if (!file_exists($file = __DIR__.'/../vendor/autoload.php')) {
    throw new \RuntimeException('Install the dependencies to run the test suite.');
}

$loader = require $file;
AnnotationRegistry::registerLoader([$loader, 'loadClass']);
AnnotationReader::addGlobalIgnoredName('group');
```

Set path to this file in your PHPUnit configuration file (eg. `phpunit.xml.dist`):

```xml
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="http://schema.phpunit.de/6.5/phpunit.xsd"
    bootstrap="tests/bootstrap.php"
    …
>
``` 

← [Examples](./examples.md) • [Fastest](./fastest.md) →
