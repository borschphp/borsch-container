<?php

use Borsch\Container\Definition;
use BorschTest\Assets\Bar;
use BorschTest\Assets\Baz;
use BorschTest\Assets\ExtendedDefinition;
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
