<?php

use Borsch\Container\{Container, Definition, Exception\ContainerException, Exception\NotFoundException};
use BorschTest\Assets\{Bar, Baz, Foo};
use Psr\Container\ContainerInterface;

test('has ID in container', function () {
    $id = substr(md5(mt_rand()), 0, 7);
    $this->container->set($id, fn() => 42);
    expect($this->container->has($id))->toBeTrue();
});

test('does not have ID in container', function () {
    expect($this->container->has('nonExistingId'))->toBeFalse();
});

test('has ID in a delegated container', function () {
    $id = substr(md5(mt_rand()), 0, 7);
    $container = new Container();
    $container->set($id, fn() => 42);
    $this->container->delegate($container);
    expect($this->container->has($id))->toBeTrue();
});

test('does not have ID in delegated container', function () {
    $this->container->delegate(new Container());
    expect($this->container->has('nonExistingId'))->toBeFalse();
});

test('closure resolution', function () {
    $this->container->set('closure', fn() => 'closure');
    expect($this->container->get('closure'))->toBe('closure');
});

test('closure resolution with added parameters', function () {
    $this->container->set(Bar::class);
    $this->container->set('closure', fn(Bar $bar) => $bar);
    expect($this->container->get('closure'))->toBeInstanceOf(Bar::class);
});

test('closure resolution with autowired parameters', function () {
    $this->container->set('closure', fn($text) => $text)->addParameter('closure');
    expect($this->container->get('closure'))->toBe('closure');
});

test('class resolution', function () {
    $this->container->set(Bar::class);
    expect($this->container->get(Bar::class))->toBeInstanceOf(Bar::class);
});

test('class resolution with added parameters', function () {
    $this->container->set(Foo::class)->addParameter(new Bar());
    expect(
        $this->container->get(Foo::class)->bar
    )->toBeInstanceOf(Bar::class);
});

test('class resolution with autowired parameters', function () {
    $this->container->set(Bar::class);
    $this->container->set(Foo::class);
    expect(
        $this->container->get(Foo::class)->bar
    )->toBeInstanceOf(Bar::class);
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

test('auto wiring', function () {
    $this->container->set(Foo::class);
    expect($this->container->get(Foo::class))->toBeInstanceOf(Foo::class)
        ->and($this->container->get(Foo::class)->bar)->toBeInstanceOf(Bar::class);
});

test('class constructor with optional parameters', function () {
    $this->container->set(Baz::class);
    expect($this->container->get(Baz::class))->toBeInstanceOf(Baz::class)
        ->and($this->container->get(Baz::class)->getValues()[1])->toBe('one');
});

test('class constructor with optional added parameters', function ($params) {
    $this->container->set(Baz::class)->addParameter($params);
    expect($this->container->get(Baz::class))->toBeInstanceOf(Baz::class)
        ->and($this->container->get(Baz::class)->getValues()[1])->toBe('un');
})->with(['french numbers' => [['zero', 'un', 'deux', 'trois']]]);

test('cached class', function () {
    $this->container->set(Bar::class)->cache(true);

    $object1 = $this->container->get(Bar::class);
    $object2 = $this->container->get(Bar::class);

    $this->assertTrue($object1 === $object2);
});

test('cached closure', function () {
    $this->container->set('test', fn() => rand())->cache(true);

    $object1 = $this->container->get('test');
    $object2 = $this->container->get('test');

    $this->assertTrue($object1 === $object2);
});

test('class definition with args', function () {
    $this->container->set(Foo::class)->addParameter(new Bar());
    expect($this->container->get(Foo::class))->toBeInstanceOf(Foo::class);
});

test('class definition with method call', function () {
    $this->container->set(Bar::class)->addMethod('setSomething', ['something in something']);
    expect($this->container->get(Bar::class)->something)->toBe('something in something');
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
    $container1 = new Container();
    $container2 = new Container();
    $container3 = new Container();
    $container3->set($id, fn() => $value);
    $this->container
        ->delegate($container1)
        ->delegate($container2)
        ->delegate($container3);
    expect($this->container->get($id))->toBe($value);
});

test('method set with Definition::class instance', function () {
    $id = substr(md5(mt_rand()), 0, 7);
    $message = substr(md5(mt_rand()), 0, 7);
    $definition = new Definition($id, $message);
    $this->container->set($id, $definition);
    expect($this->container->get($id))->toBe($message);
});

test('get() method throws exception if ID is not found', function () {
    $id = substr(md5(mt_rand()), 0, 7);
    var_dump($id, $this->container->get($id));
})->throws(NotFoundException::class);

test('callable parameters', function () {
    $this->container->set('callable', fn(int $bar, Baz $baz) => [$bar, $baz]);
    $this->container->get('callable');
})->throws(ContainerException::class);
