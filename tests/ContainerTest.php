<?php

namespace BorschTest;

require_once __DIR__.'/../vendor/autoload.php';

use BorschTest\Assets\Bar;
use BorschTest\Assets\Baz;
use BorschTest\Assets\Foo;
use Borsch\Container\Container;
use Borsch\Container\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerTest extends TestCase
{

    public function testClosureResolution()
    {
        $container = new Container();
        $container->set('closure', function () {
            return 'closure';
        });

        $this->assertSame($container->get('closure'), 'closure');
    }

    public function testClosureResolutionWithAddedParameters()
    {
        $container = new Container();
        $container->set('closure', function ($text) {
            return $text;
        })->addParameter('closure');

        $this->assertSame($container->get('closure'), 'closure');
    }

    public function testClosureResolutionWithAutoWiredParameters()
    {
        $container = new Container();
        $container->set(Bar::class);
        $container->set('closure', function (Bar $bar) {
            return $bar;
        });

        $this->assertInstanceOf(
            Bar::class,
            $container->get('closure')
        );
    }

    public function testClassResolution()
    {
        $container = new Container();
        $container->set(Bar::class);

        $this->assertInstanceOf(
            Bar::class,
            $container->get(Bar::class)
        );
    }

    public function testClassResolutionWithAddedParameters()
    {
        $container = new Container();
        $container->set(Foo::class)->addParameter(new Bar());

        $this->assertInstanceOf(
            Bar::class,
            $container->get(Foo::class)->bar
        );
    }

    public function testClassResolutionWithAutoWiredParameters()
    {
        $container = new Container();
        $container->set(Bar::class);
        $container->set(Foo::class);

        $this->assertInstanceOf(
            Bar::class,
            $container->get(Foo::class)->bar
        );
    }

    public function testCannotGetValueFromInvalidKey()
    {
        $this->expectException(NotFoundException::class);

        $container = new Container();
        $container->set('invalid key');
        $container->get('invalid key');
    }

    public function testAutoWiring()
    {
        $container = new Container();
        $container->set(Foo::class);

        $this->assertInstanceOf(
            Foo::class,
            $container->get(Foo::class)
        );
        $this->assertInstanceOf(
            Bar::class,
            $container->get(Foo::class)->bar
        );
    }

    public function testClassConstructorWithOptionalParameters()
    {
        $container = new Container();
        $container->set(Baz::class);

        $this->assertInstanceOf(
            Baz::class,
            $container->get(Baz::class)
        );
        $this->assertEquals(
            'one',
            $container->get(Baz::class)->getValues()[1]
        );
    }

    public function testClassConstructorWithOptionalAddedParameters()
    {
        $container = new Container();
        $container->set(Baz::class)->addParameter([
            'zero',
            'un',
            'deux',
            'trois'
        ]);

        $this->assertInstanceOf(
            Baz::class,
            $container->get(Baz::class)
        );
        $this->assertEquals(
            'un',
            $container->get(Baz::class)->getValues()[1]
        );
    }

    public function testCachedClass()
    {
        $container = new Container();
        $container->set(Bar::class)->cache(true);

        $object1 = $container->get(Bar::class);
        $object2 = $container->get(Bar::class);

        $this->assertSame($object1, $object2);
    }

    public function testCachedClosure()
    {
        $container = new Container();
        $container->set('test', function() {
            return 42;
        })->cache(true);

        $object1 = $container->get('test');
        $object2 = $container->get('test');

        $this->assertSame($object1, $object2);
    }

    public function testClassDefinitionWithArgs()
    {
        $container = new Container();
        $container->set(Foo::class)->addParameter(new Bar());

        $this->assertInstanceOf(
            Foo::class,
            $container->get(Foo::class)
        );
    }

    public function testClassDefinitionWithMethodCall()
    {
        $container = new Container();
        $container->set(Bar::class)->addMethod(
            'setSomething',
            [
                'something in something'
            ]
        );

        $this->assertSame(
            $container->get(Bar::class)->something,
            'something in something'
        );
    }

    public function testGetContainerInstanceWithContainerInterfaceIdentifier()
    {
        $container = new Container();
        $container->set(Bar::class);

        $this->assertInstanceOf(
            ContainerInterface::class,
            $container->get(ContainerInterface::class)
        );

        $this->assertSame(
            $container->get(ContainerInterface::class),
            $container
        );

        $this->assertTrue(
            $container->get(ContainerInterface::class)->has(Bar::class)
        );
    }
}
