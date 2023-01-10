<?php

use Borsch\Container\NotFoundException;
use BorschTest\Assets\Bar;
use BorschTest\Assets\Baz;
use BorschTest\Assets\Foo;
use Psr\Container\ContainerInterface;

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

it('throws exception when trying to get value from invalid key', function () {
    $this->container->set('invalid key');
    $this->container->get('invalid key');
})->throws(NotFoundException::class);

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

it('get container instance with container interface identifier', function () {
    $this->container->set(Bar::class);
    expect($this->container->get(ContainerInterface::class))->toBeInstanceOf(ContainerInterface::class);
    $this->assertTrue($this->container === $this->container->get(ContainerInterface::class));
    expect($this->container->get(ContainerInterface::class)->has(Bar::class))->toBeTrue();
});
