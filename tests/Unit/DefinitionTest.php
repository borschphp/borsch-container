<?php

use Borsch\Container\Definition;
use Borsch\Container\Exception\ContainerException;
use Borsch\Container\Exception\NotFoundException;
use BorschTest\Assets\Bar;
use BorschTest\Assets\Baz;
use BorschTest\Assets\Biz;
use BorschTest\Assets\ExtendedDefinition;
use BorschTest\Assets\Foo;
use BorschTest\Assets\Ink;
use Psr\Container\ContainerInterface;

it('adds method', function () {
    $definition = new Definition(Bar::class);
    $definition->addMethod('setSomething', ['something']);
    $definition->setContainer($this->container);
    expect($definition->get()->something)->toBe('something');
});

it('has set container', function () {
    $definition = new class('test', fn() => 'it is a test') extends Definition {
        public function getContainer(): ContainerInterface
        {
            return $this->container;
        }
    };
    $definition->setContainer($this->container);
    $this->assertTrue($this->container === $definition->getContainer());
});

it('is cached', function () {
    $definition = new Definition('test', fn() => 'it is a test');
    $this->assertFalse($definition->isCached());
    $definition->cache(true);
    $this->assertTrue($definition->isCached());
});

test('constructor deals with id and concrete correctly', function () {
    $definition = new ExtendedDefinition(Bar::class);
    expect($definition->getId())->toBe(Bar::class);
    expect($definition->getConcrete())->toBe(Bar::class);

    $definition = new ExtendedDefinition(Bar::class, 'test');
    expect($definition->getId())->toBe(Bar::class);
    expect($definition->getConcrete())->toBe('test');
});

it('adds parameter', function () {
    $definition = new Definition(Baz::class, fn() => new Baz());
    $definition->setContainer($this->container);
    /** @var Baz $baz */
    $baz = $definition->get();
    expect($baz->getValues())->toBeArray();

    $definition = (new Definition(Baz::class))
        ->addParameter([3, 2, 1])
        ->setContainer($this->container);
    $baz = $definition->get();
    $values = $baz->getValues();
    expect($values)->toBeArray()
        ->and($values)->toHaveCount(3)
        ->and($values[0])->toBe(3)
        ->and($values[1])->toBe(2)
        ->and($values[2])->toBe(1);
});

it('gets value', function () {
    $definition = new Definition(Baz::class, fn() => new Baz());
    $definition->setContainer($this->container);
    expect($definition->get())->toBeInstanceOf(Baz::class);
});

test('definition with a callable concrete throw ContainerException when missing parameters', function() {
    $definition = new Definition('id', fn(int $undefined) => new Baz([$undefined]));
    $definition->setContainer($this->container);

    $definition->get();
})->throws(
    ContainerException::class,
    sprintf(
        'Unable to get parameter for callable/closure defined in entry with ID "%s". '.
        'Expected a parameter of type "%s" but could not be found inside the container nor its delegates.',
        'id',
        'int'
    )
);

test('when definition throw ContainerException, a NotFoundException is thrown previously', function() {
    try {
        $definition = new Definition('id', fn(int $undefined) => new Baz([$undefined]));
        $definition->setContainer($this->container);
        $definition->get();
    } catch (Exception $exception) {
        expect($exception)->toBeInstanceOf(ContainerException::class)
            ->and($exception->getPrevious())->toBeInstanceOf(NotFoundException::class)
            ->and($exception->getCode())->toBe($exception->getPrevious()->getCode());
    }
});

test('object methods are called with value from container', function () {
    $this->container->set(Bar::class);
    $definition = new Definition(Biz::class);
    $definition
        ->addMethod('setBar', [Bar::class])
        ->setContainer($this->container);
    $biz = $definition->get();
    expect($biz)->toBeInstanceOf(Biz::class)
        ->and($biz->bar)->toBeInstanceOf(Bar::class);
});

test('invoke as class with constructor optional parameters', function () {
    $definition = new Definition(Baz::class);
    $definition->setContainer($this->container);
    /** @var Baz $baz */
    $baz = $definition->get();
    expect($baz)->toBeInstanceOf(Baz::class)
        ->and($baz->getValues())->toBeArray()
        ->and($baz->getValues())->toBe([
            'zero',
            'one',
            'two',
            'three'
        ]);
});


test('invoke as callable throw exception', function () {
    $definition = new Definition('test', [Ink::class, 'getBar']);
    $definition->setContainer($this->container);
    $bar = $definition->get();
})->throws(NotFoundException::class);
