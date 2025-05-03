<?php

use Borsch\Container\Exception\ContainerException;

covers(ContainerException::class);

test('unableToGetCallableParameter() returns a ContainerException without Exception', function () {
    $id = substr(md5(mt_rand()), 0, 7);
    $reflection = new class extends ReflectionNamedType {
        public function getName(): string { return 'foobar'; }
    };
    $exception = ContainerException::unableToGetCallableParameter($reflection,$id);
    expect($exception)->toBeInstanceOf(ContainerException::class)
        ->and($exception->getMessage())->toBe(sprintf(
            'Unable to get parameter for callable/closure defined in entry with ID "%s". Expected a parameter of type "foobar" but could not be found inside the container nor its delegates.',
            $id
        ))
        ->and($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBeNull();
});

test('unableToGetCallableParameter() returns a ContainerException with ReflectionException', function () {
    $id = substr(md5(mt_rand()), 0, 7);
    $message = md5(mt_rand());
    $code = mt_rand(100, 999);
    $reflection = new class extends ReflectionNamedType {
        public function getName(): string { return 'foobar'; }
    };
    $base = new ReflectionException($message, $code);
    $exception = ContainerException::unableToGetCallableParameter($reflection,$id, $base);
    expect($exception)->toBeInstanceOf(ContainerException::class)
        ->and($exception->getMessage())->toBe(sprintf(
            'Unable to get parameter for callable/closure defined in entry with ID "%s". Expected a parameter of type "foobar" but could not be found inside the container nor its delegates.',
            $id
        ))
        ->and($exception->getCode())->toBe($code)
        ->and($exception->getPrevious())->toBeInstanceOf(ReflectionException::class)
        ->and($exception->getPrevious()->getMessage())->toBe($message);
});

test('unableToGetClassReflection() returns a ContainerException without Exception', function () {
    $classname = md5(mt_rand());
    $exception = ContainerException::unableToGetClassReflection($classname);
    expect($exception)->toBeInstanceOf(ContainerException::class)
        ->and($exception->getMessage())->toBe(sprintf(
            'Unable to create a reflection for class "%s", it does not exist.',
            $classname
        ))
        ->and($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBeNull();
});

test('unableToGetClassReflection() returns a ContainerException with Exception', function () {
    $classname = md5(mt_rand());
    $message = md5(mt_rand());
    $code = mt_rand(100, 999);
    $base = new ReflectionException($message, $code);
    $exception = ContainerException::unableToGetClassReflection($classname,$base);
    expect($exception)->toBeInstanceOf(ContainerException::class)
        ->and($exception->getMessage())->toBe(sprintf(
            'Unable to create a reflection for class "%s", it does not exist.',
            $classname
        ))
        ->and($exception->getCode())->toBe($code)
        ->and($exception->getPrevious())->toBeInstanceOf(ReflectionException::class)
        ->and($exception->getPrevious()->getMessage())->toBe($message);
});

test('unableToGetFunctionReflection() returns a ContainerException without Exception', function () {
    $classname = md5(mt_rand());
    $exception = ContainerException::unableToGetFunctionReflection($classname);
    expect($exception)->toBeInstanceOf(ContainerException::class)
        ->and($exception->getMessage())->toBe(sprintf(
            'Unable to create a reflection for function "%s", it does not exist.',
            $classname
        ))
        ->and($exception->getCode())->toBe(0)
        ->and($exception->getPrevious())->toBeNull();
});

test('unableToGetFunctionReflection() returns a ContainerException with Exception', function () {
    $classname = md5(mt_rand());
    $message = md5(mt_rand());
    $code = mt_rand(100, 999);
    $base = new ReflectionException($message, $code);
    $exception = ContainerException::unableToGetFunctionReflection($classname,$base);
    expect($exception)->toBeInstanceOf(ContainerException::class)
        ->and($exception->getMessage())->toBe(sprintf(
            'Unable to create a reflection for function "%s", it does not exist.',
            $classname
        ))
        ->and($exception->getCode())->toBe($code)
        ->and($exception->getPrevious())->toBeInstanceOf(ReflectionException::class)
        ->and($exception->getPrevious()->getMessage())->toBe($message);
});
