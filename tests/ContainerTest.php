<?php

require_once __DIR__.'/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/Assets/Foo.php';
require_once __DIR__.'/Assets/Bar.php';

class ContainerTest extends TestCase
{

    public function testClosureResolution()
    {
        $container = new \Borsch\Container\Container();
        $container->set('closure', function () {
            return 'closure';
        });

        $this->assertSame($container->get('closure'), 'closure');
    }

    public function testClassResolution()
    {
        $container = new \Borsch\Container\Container();
        $container->set(\Assets\Bar::class);

        $this->assertInstanceOf(
            \Assets\Bar::class,
            $container->get(\Assets\Bar::class)
        );
    }

    public function testCannotGetValueFromInvalidKey()
    {
        $this->expectException(\Borsch\Container\NotFoundException::class);

        $container = new \Borsch\Container\Container();
        $container->set('invalid key');
        $container->get('invalid key');
    }

    public function testAutoWiring()
    {
        $container = new \Borsch\Container\Container();
        $container->set(\Assets\Foo::class);

        $this->assertTrue(
            $container->get(\Assets\Foo::class) instanceof \Assets\Foo &&
            $container->get(\Assets\Foo::class)->bar instanceof \Assets\Bar
        );
    }

    public function testCachedClass()
    {
        $container = new \Borsch\Container\Container();
        $container->set(\Assets\Bar::class)->cache(true);

        $object1 = $container->get(\Assets\Bar::class);
        $object2 = $container->get(\Assets\Bar::class);

        $this->assertSame($object1, $object2);
    }

    public function testCachedClosure()
    {
        $container = new \Borsch\Container\Container();
        $container->set('test', function() {
            return 42;
        })->cache(true);

        $object1 = $container->get('test');
        $object2 = $container->get('test');

        $this->assertSame($object1, $object2);
    }

    public function testClassDefinitionWithArgs()
    {
        $container = new \Borsch\Container\Container();
        $container->set(\Assets\Foo::class)->addParameter(new \Assets\Bar());

        $this->assertInstanceOf(
            \Assets\Foo::class,
            $container->get(\Assets\Foo::class)
        );
    }

    public function testClassDefinitionWithMethodCall()
    {
        $container = new \Borsch\Container\Container();
        $container->set(\Assets\Bar::class)->addMethod(
            'setSomething',
            [
                'something in something'
            ]
        );

        $this->assertSame(
            $container->get(\Assets\Bar::class)->something,
            'something in something'
        );
    }
}
