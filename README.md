# Borsch Container

[![Build](https://github.com/borschphp/borsch-container/actions/workflows/php.yml/badge.svg)](https://github.com/borschphp/borsch-container/actions/workflows/php.yml)
[![Latest Stable Version](https://poser.pugx.org/borschphp/container/v)](//packagist.org/packages/borschphp/container)
[![License](https://poser.pugx.org/borschphp/container/license)](//packagist.org/packages/borschphp/container)

A lightweight and easy-to-use PSR-11 container for your web projects.

## Installation

Via [composer](https://getcomposer.org/) :

```bash
composer require borschphp/container
```

## Usage

```php
use Borsch\Container\Container;

$container = new Container();

// Add your dependencies
$container->set('db', fn() => new PDO('mysql:host=localhost;dbname=mydb', 'user', 'password'));

// Retrieve your dependencies
$db = $container->get('db');
```

### Parameterized Dependencies

In this example, we're defining a PDO service within an entry `db`.

Then we set the values of the parameters in the same order as the constructor :  
`PDO::__construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)`) :

Finally we retrieve the PDO service.

```php
use Borsch\Container\Container;

$container = new Container();

$container
    ->set('db', PDO::class)
    ->addParameter('mysql:host=localhost;dbname=mydb')
    ->addParameter('user')
    ->addParameter('password');

$db = $container->get('db');
```

### Shared and Unshared Dependencies

In this example, we're defining a logger service which is shared by default.  
We're disabling caching and retrieving the logger service twice, so we can see that the two instances are different.

```php
use Borsch\Container\Container;

$container = new Container();

$container
    ->set('logger', fn() => new Logger())
    ->cache(true);

$logger1 = $container->get('logger');
$logger2 = $container->get('logger');

// $logger1 and $logger2 are the same instances
```

### Auto-wiring by default

In this example we create a custom class that requires a `Logger` instance to be instantiated :

```php
class MyApp
{
    public function __construct(
        protected Logger $logger
    ) {}
}
```

We create an entry in the Container with `Logger::class` and use a closure to instantiate it.  
We create an entry in the Container with `MyApp::class`; as we did not provide a second parameter, 
the container will do its best to fetch required parameters (here, a `Logger`) from the environment.

```php
use Borsch\Container\Container;

$container = new Container();

$container->set(Logger::class, function () {
    $logger = new Logger('app');
    $logger->pushHandler(new StreamHandler('path/to/log/file.log'));

    return $logger;
})-> cache(true);

$container->set(MyApp::class);

$app = $container->get(MyApp::class);

// $app is instantiated with the logger from entry Logger::class
```

## License

The package is licensed under the MIT license. See [License File](https://github.com/borschphp/borsch-container/blob/master/LICENSE.md) for more information.