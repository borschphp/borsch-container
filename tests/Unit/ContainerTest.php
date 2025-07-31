<?php

use Borsch\Container\{Container, Definition, Exception\ContainerException, Exception\NotFoundException};
use BorschTest\Assets\{Bar, Baz, Foo};
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Container\ContainerInterface;

covers(Container::class);

beforeEach(function () {
    $this->container = new class extends Container {
        public function getDefinitions(): ArrayCollection { return $this->definitions; }
        public function getDelegates(): ArrayCollection { return $this->delegates; }
        public function isCached(string $id): bool { return isset($this->cache[$id]); }
        public function getCachedDefinition(string $id): mixed { return $this->cache[$id]; }
        public function clear(): void { $this->definitions->clear(); }
    };
});

test('constructor instantiate definitions', function () {
    expect($this->container->getDefinitions())->toBeInstanceOf(ArrayCollection::class)
        ->and($this->container->getDefinitions()->toArray())->toHaveCount(1);
});

test('constructor instantiate delegates', function () {
    expect($this->container->getDelegates())->toBeInstanceOf(ArrayCollection::class)
        ->and($this->container->getDelegates()->toArray())->toHaveCount(0);
});

test('constructor cache the ContainerInterface', function() {
    $this->container->get(ContainerInterface::class); // Call 1 time to place it in cache

    expect($this->container->isCached(ContainerInterface::class))->toBeTrue()
        ->and($this->container->getCachedDefinition(ContainerInterface::class))->toBe($this->container);
});

test('isAutowiring() should return true by default', function () {
    expect($this->container->isAutowiring())->toBeTrue();
});

test('setAutowiring() to false', function () {
    $this->container->setAutowiring(false);
    expect($this->container->isAutowiring())->toBeFalse();
});

it('should throw an exception when autowiring is off and class is missing a parameter', function () {
    $this->container->setAutowiring(false);
    $this->container->get(Foo::class);
})->throws(NotFoundException::class);

test('has ID in container', function () {
    $id = substr(md5(mt_rand()), 0, 7);
    $value = mt_rand(1, 100);
    $this->container->set($id, fn() => $value);
    expect($this->container->has($id))->toBeTrue()
        ->and($this->container->get($id))->toBe($value);
});

test('does not have ID in container', function () {
    expect($this->container->has('nonExistingId'))->toBeFalse();
});

test('has ID in a delegated container', function () {
    $id = substr(md5(mt_rand()), 0, 7);
    $value = mt_rand(1, 100);
    $container = new Container();
    $container->set($id, fn() => $value);
    $this->container->delegate($container);
    expect($this->container->has($id))->toBeTrue()
        ->and($this->container->get($id))->toBe($value);
});

test('does not have ID in delegated container', function () {
    $this->container->clear();
    $this->container->delegate(new Container());
    expect($this->container->has('nonExistingId'))->toBeFalse();
});

test('has() with and without tags', function () {
    $this->container->set(DateTime::class)->addParameter('now', 'datetime')->addTag('#date');
    $this->container->set('today', fn() => new DateTime())->addTag('#date');
    expect($this->container->has('#date'))->toBeTrue()
        ->and($this->container->has('#bar'))->toBeFalse()
        ->and($this->container->get('#date'))->toBeArray()->toHaveCount(2);
});

test('hasTag() with and without tags', function () {
    $this->container->set(DateTime::class)->addParameter('now', 'datetime')->addTag('#date');
    $this->container->set('today', fn() => new DateTime())->addTag('#date');
    expect($this->container->hasTag('#date'))->toBeTrue()
        ->and($this->container->hasTag('#bar'))->toBeFalse();
});

test('set() with Definition::class instance', function () {
    $id = substr(md5(mt_rand()), 0, 7);
    $message = substr(md5(mt_rand()), 0, 7);
    $definition = new Definition($id, $message);
    $this->container->set($id, $definition);
    expect($this->container->getDefinitions())->toBeInstanceOf(ArrayCollection::class)
        ->and($this->container->getDefinitions()->toArray())->toHaveCount(2)
        ->and($this->container->getDefinitions()->first())->toBeInstanceOf(Definition::class)
        ->and($this->container->getDefinitions()->last())->toBeInstanceOf(Definition::class)
        ->and($this->container->getDefinitions()->last()->getId())->toBe($id)
        ->and($this->container->getDefinitions()->last()->getConcrete())->toBe($message)
        ->and($this->container->get($id))->toBe($message);
});

test('array resolution', function () {
    $this->container->set('array', ['foo' => 'bar']);
    expect($this->container->getDefinitions())->toBeInstanceOf(ArrayCollection::class)
        ->and($this->container->getDefinitions()->toArray())->toHaveCount(2)
        ->and($this->container->getDefinitions()->first())->toBeInstanceOf(Definition::class)
        ->and($this->container->getDefinitions()->last())->toBeInstanceOf(Definition::class)
        ->and($this->container->getDefinitions()->last()->getId())->toBe('array')
        ->and($this->container->getDefinitions()->last()->getConcrete())->toBe(['foo' => 'bar'])
        ->and($this->container->get('array'))->toBe(['foo' => 'bar']);
});

test('scalar resolution', function () {
    $this->container->set('scalar', 'foo');
    expect($this->container->getDefinitions())->toBeInstanceOf(ArrayCollection::class)
        ->and($this->container->getDefinitions()->toArray())->toHaveCount(2)
        ->and($this->container->getDefinitions()->first())->toBeInstanceOf(Definition::class)
        ->and($this->container->getDefinitions()->last())->toBeInstanceOf(Definition::class)
        ->and($this->container->getDefinitions()->last()->getId())->toBe('scalar')
        ->and($this->container->getDefinitions()->last()->getConcrete())->toBe('foo')
        ->and($this->container->get('scalar'))->toBe('foo');
});

test('closure resolution', function () {
    $this->container->set('closure', fn() => 'closure');
    expect($this->container->getDefinitions())->toBeInstanceOf(ArrayCollection::class)
        ->and($this->container->getDefinitions()->toArray())->toHaveCount(2)
        ->and($this->container->getDefinitions()->first())->toBeInstanceOf(Definition::class)
        ->and($this->container->getDefinitions()->last())->toBeInstanceOf(Definition::class)
        ->and($this->container->getDefinitions()->last()->getId())->toBe('closure')
        ->and($this->container->getDefinitions()->last()->getConcrete())->toBeCallable()
        ->and($this->container->get('closure'))->toBe('closure');
});

test('closure resolution with added parameters', function () {
    $this->container->set(Bar::class);
    $this->container->set('closure', fn(Bar $bar) => $bar);
    expect($this->container->getDefinitions())->toBeInstanceOf(ArrayCollection::class)
        ->and($this->container->getDefinitions()->toArray())->toHaveCount(3)
        ->and($this->container->getDefinitions()->first())->toBeInstanceOf(Definition::class)
        ->and($this->container->getDefinitions()->last())->toBeInstanceOf(Definition::class)
        ->and($this->container->getDefinitions()->last()->getId())->toBe('closure')
        ->and($this->container->getDefinitions()->last()->getConcrete())->toBeCallable()
        ->and($this->container->get('closure'))->toBeInstanceOf(Bar::class);
});

test('closure resolution with autowired parameters', function () {
    $value = (string)mt_rand(1, 100);
    $this->container->set('closure', fn($text) => $text)->addParameter($value);
    expect($this->container->getDefinitions())->toBeInstanceOf(ArrayCollection::class)
        ->and($this->container->getDefinitions()->toArray())->toHaveCount(2)
        ->and($this->container->getDefinitions()->first())->toBeInstanceOf(Definition::class)
        ->and($this->container->getDefinitions()->last())->toBeInstanceOf(Definition::class)
        ->and($this->container->getDefinitions()->last()->getId())->toBe('closure')
        ->and($this->container->getDefinitions()->last()->getConcrete())->toBeCallable()
        ->and($this->container->get('closure'))->toBe($value);
});

test('class resolution', function () {
    $this->container->set(Bar::class);
    expect($this->container->getDefinitions())->toBeInstanceOf(ArrayCollection::class)
        ->and($this->container->getDefinitions()->toArray())->toHaveCount(2)
        ->and($this->container->getDefinitions()->first())->toBeInstanceOf(Definition::class)
        ->and($this->container->getDefinitions()->last())->toBeInstanceOf(Definition::class)
        ->and($this->container->getDefinitions()->last()->getId())->toBe(Bar::class)
        ->and($this->container->getDefinitions()->last()->getConcrete())->toBe(Bar::class)
        ->and($this->container->get(Bar::class))->toBeInstanceOf(Bar::class);
});

test('class resolution with added parameters', function () {
    $this->container->set(Foo::class)->addParameter(new Bar());
    expect($this->container->getDefinitions())->toBeInstanceOf(ArrayCollection::class)
        ->and($this->container->getDefinitions()->toArray())->toHaveCount(2)
        ->and($this->container->getDefinitions()->first())->toBeInstanceOf(Definition::class)
        ->and($this->container->getDefinitions()->last())->toBeInstanceOf(Definition::class)
        ->and($this->container->getDefinitions()->last()->getId())->toBe(Foo::class)
        ->and($this->container->getDefinitions()->last()->getConcrete())->toBe(Foo::class)
        ->and($this->container->get(Foo::class)->bar)->toBeInstanceOf(Bar::class);
});

test('class resolution with autowired parameters', function () {
    $this->container->set(Bar::class);
    $this->container->set(Foo::class);
    expect($this->container->get(Foo::class))->toBeInstanceOf(Foo::class)
        ->and($this->container->get(Bar::class))->toBeInstanceOf(Bar::class)
        ->and($this->container->get(Foo::class)->bar)->toBeInstanceOf(Bar::class);
});

it('gets scalar values', function () {
    $integer = rand(1, 100);
    $float = rand(1, 100) / 100;
    $string = substr(md5(mt_rand()), 0, 7);
    $true = true;
    $false = false;
    $this->container->set('integer', $integer);
    $this->container->set('float', $float);
    $this->container->set('string', $string);
    $this->container->set('true', $true);
    $this->container->set('false', $false);
    expect($this->container->get('integer'))->toBe($integer)
        ->and($this->container->get('float'))->toBe($float)
        ->and($this->container->get('string'))->toBe($string)
        ->and($this->container->get('true'))->toBeTrue()
        ->and($this->container->get('false'))->toBeFalse();
});

it('gets array, object and resource values', function () {
    $array = [1, 2, 3, 'toto'];
    $object = new stdClass();
    $object->random = substr(md5(mt_rand()), 0, 7);
    $resource = fopen('php://temp', 'r');
    $this->container->set('array', $array);
    $this->container->set('object', $object);
    $this->container->set('resource', $resource);
    expect($this->container->get('array'))->toBe($array)
        ->and($this->container->get('object'))->toBe($object)
        ->and($this->container->get('resource'))->toBe($resource);
    fclose($resource);
});

test('class constructor with optional parameters', function () {
    $this->container->set(Baz::class);
    expect($this->container->get(Baz::class))->toBeInstanceOf(Baz::class)
        ->and($this->container->get(Baz::class)->getValues()[0])->toBe('zero')
        ->and($this->container->get(Baz::class)->getValues()[1])->toBe('one')
        ->and($this->container->get(Baz::class)->getValues()[2])->toBe('two')
        ->and($this->container->get(Baz::class)->getValues()[3])->toBe('three');
});

test('class constructor with optional added parameters', function ($params) {
    $this->container->set(Baz::class)->addParameter($params);
    expect($this->container->get(Baz::class))->toBeInstanceOf(Baz::class)
        ->and($this->container->get(Baz::class)->getValues()[0])->toBe('zero')
        ->and($this->container->get(Baz::class)->getValues()[1])->toBe('un')
        ->and($this->container->get(Baz::class)->getValues()[2])->toBe('deux')
        ->and($this->container->get(Baz::class)->getValues()[3])->toBe('trois');
})->with(['french numbers' => [['zero', 'un', 'deux', 'trois']]]);

test('cached class', function () {
    $this->container->set(Bar::class)->cache(true);

    $object1 = $this->container->get(Bar::class);
    $object2 = $this->container->get(Bar::class);

    expect($object1)->toBe($object2);
});

test('cached closure', function () {
    $this->container->set('test', fn() => rand())->cache(true);

    $object1 = $this->container->get('test');
    $object2 = $this->container->get('test');

    expect($object1)->toBe($object2);
});

test('class definition with method call', function () {
    $this->container->set(Bar::class)->addMethod('setSomething', ['something in something']);
    expect($this->container->get(Bar::class))->toBeInstanceOf(Bar::class)
        ->and($this->container->get(Bar::class)->something)->toBe('something in something');
});

it('gets container instance with container interface identifier', function () {
    $this->container->set(Bar::class);
    expect($this->container->get(ContainerInterface::class))->toBeInstanceOf(ContainerInterface::class)
        ->and($this->container === $this->container->get(ContainerInterface::class))->toBeTrue()
        ->and($this->container->get(ContainerInterface::class)->has(Bar::class))->toBeTrue();
});

test('delegated container returns what is expected', function () {
    $id = substr(md5(mt_rand()), 0, 7);
    $value = rand(1, 100);
    $container3 = new Container();
    $container3->set($id, fn() => $value);
    $this->container->delegate(new Container())->delegate(new Container())->delegate($container3);
    expect($this->container->get($id))->toBe($value);
});

test('get() method throws NotFoundException if ID is not found', function () {
    $this->container->get('anID');
})->throws(NotFoundException::class, 'Unable to find entry with ID "anID".');

test('callable parameters throws ContainerException', function () {
    $this->container->set('callable', fn(int $bar, Baz $baz) => [$bar, $baz]);
    $this->container->get('callable');
})->throws(
    ContainerException::class,
    'Unable to get parameter for callable/closure defined in entry with ID "callable". Expected a parameter of type "int" but could not be found inside the container nor its delegates.',
    0
);

test('get() method returns real set instance', function() {
    $rand = substr(md5(mt_rand()), 0, 7);
    $bar = new Bar();
    $bar->setSomething($rand);
    $foo = new Foo($bar);
    $this->container->set(Foo::class, $foo);

    expect($this->container->get(Foo::class))->toBe($foo)
        ->and($this->container->get(Foo::class)->bar)->toBeInstanceOf(Bar::class)
        ->and($this->container->get(Foo::class)->bar)->toBe($bar)
        ->and($this->container->get(Foo::class)->bar->something)->toBe($rand);
});

test('alias() returns the aliased entry', function () {
    $id = substr(md5(mt_rand()), 0, 7);
    $value = mt_rand(1, 100);
    $alias = substr(md5(mt_rand()), 0, 7);
    $this->container->set($id, fn() => $value);
    $this->container->alias($alias, $id);
    expect($this->container->get($id))->toBe($value)
        ->and($this->container->get($alias))->toBe($value)
        ->and($this->container->get($alias))->toBe($this->container->get($id));
});

test('alias() throws a NotFoundException when entry does not exist', function () {
    $this->container->alias('Monolog\\Logger', 'Psr\\log\\LoggerInterface');
})->throws(NotFoundException::class, 'Unable to find entry with ID "Psr\\log\\LoggerInterface".');

test('cache behavior is false by default on instantiation of a container', function () {
    expect($this->container->getCacheByDefault())->toBeFalse();
});

test('cache behavior is true when set to true', function () {
    $this->container->set(DateTime::class)->addParameter('now');
    $this->container->setCacheByDefault(true);
    $this->container->set(Bar::class);
    $this->container->get(DateTime::class); // Call 1 time to place it in cache (if set)
    $this->container->get(Bar::class); // Call 1 time to place it in cache (if set)
    expect($this->container->getCacheByDefault())->toBeTrue()
        ->and($this->container->isCached(Bar::class))->toBeTrue()
        ->and($this->container->isCached(DateTime::class))->toBeFalse();
});

test('extend() extend a scalar', function () {
    $this->container->set('foo', 'Hello,');
    $this->container->set('bar', 'World!');
    $this->container->extend('test', fn(string $text, ContainerInterface $container) => $text . ' ' . $container->get('bar'), 'foo');

    expect($this->container->get('test'))->toBe('Hello, World!');
});

test('extend() extend a class', function () {
    $something = substr(md5(mt_rand()), 0, 7);
    $this->container->set(Bar::class)->addMethod('setSomething', [$something]);
    $this->container->extend(Foo::class, fn(Bar $bar) => new Foo($bar), Bar::class);
    expect($this->container->has(Foo::class))->toBeTrue()
        ->and($this->container->get(Foo::class))->toBeInstanceOf(Foo::class)
        ->and($this->container->get(Foo::class)->bar)->toBeInstanceOf(Bar::class)
        ->and($this->container->get(Foo::class)->bar->something)->toBe($something);
});

test('extend() throw NotFoundException', function () {
    $this->container->extend(Foo::class, fn(Bar $bar) => new Foo($bar), Bar::class);
})->throws(NotFoundException::class, 'Unable to find entry with ID "'.Bar::class.'".', 0);

test('extend() throw ContainerException (extendingWithSameIdAndFromForbidden)', function () {
        $this->container->set('foo', 'Hello,');
        $this->container->set('bar', 'World!');
        $this->container->extend('bar', fn(string $text, ContainerInterface $container) => '', 'bar');
})->throws(ContainerException::class, 'It is forbidden to extend a definition with the same ID (bar), provide a new ID (e.g. $from) to fix the issue.', 0);

test('extend() throw ContainerException (extendingAnExistingEntryIsForbidden)', function () {
    $this->container->set('foo', 'Hello,');
    $this->container->set('bar', 'World!');
    $this->container->extend('bar', fn(string $text, ContainerInterface $container) => '', 'foo');
})->throws(ContainerException::class, 'It is forbidden to extend a definition and register it with an existing ID (bar), provide a new ID (e.g. $from) to fix the issue.', 0);
