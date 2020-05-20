# Borsch Framework

[![Build Status](https://travis-ci.com/borschphp/borsch-container.svg?branch=master)](https://travis-ci.com/borschphp/borsch-container)
[![Latest Stable Version](https://poser.pugx.org/borschphp/container/v)](//packagist.org/packages/borschphp/container)

A simple and fast PSR-11 Container implementation.

## Installation

Via [composer](https://getcomposer.org/) :

`composer require borschphp/container`

## Usage

```php
// file src/Assets/Foo.php
namespace Assets;

class Foo
{
    /** @var Bar */
    public $bar;

    /**
     * @param Bar $bar
     */
    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }
}
```

```php
// file src/Assets/Bar.php
namespace Assets;

class Bar
{
    /** @var string */
    public $something;

    /**
     * @param string $something
     */
    public function setSomething(string $something): void
    {
        $this->something = $something;
    }
}
```

```php
// file index.php
require_once __DIR__.'/vendor/autoload.php';

$container = new \Borsch\Container\Container();

// Autowired, automatically fetch \Assets\Bar::class instance from the container
// To persist the item in the container and not reload everytime it is needed,
// you can cache the value with the `cache` method.
$container->set(\Assets\Foo::class)->cache(true);

// Or set the value by yourself
$container->set(\Assets\Foo::class)->addParameter(new \Assets\Bar())->cache(true);

$container->set(\Assets\Bar::class)->addMethod('setSomething', [42]);
$container->set('closure', function () {
    return 'closure';
});

var_dump(
    $container->get(\Assets\Foo::class)->bar instanceof \Assets\Bar, // true
    $container->get(\Assets\Bar::class)->something === 42, // true
    $container->get('closure') === 'closure' // true
);
```

## License

The package is licensed under the MIT license. See [License File](https://github.com/borschphp/borsch-container/blob/master/LICENSE.md) for more information.