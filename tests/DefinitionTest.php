<?php

namespace BorschTest;

use Borsch\Container\Container;
use Borsch\Container\Definition;
use BorschTest\Assets\Bar;
use BorschTest\Assets\Baz;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DefinitionTest extends TestCase
{

    public function testAddMethod()
    {
        $container = new Container();
        $definition = new Definition(Bar::class);
        $definition->addMethod('setSomething', ['something']);
        $definition->setContainer($container);

        $this->assertSame('something', $definition->get()->something);
    }

    public function testSetContainer()
    {
        $container = new Container();
        $definition = new class('test', fn() => 'it is a test') extends Definition {
            public function getContainer(): ContainerInterface
            {
                return $this->container;
            }
        };
        $definition->setContainer($container);

        $this->assertSame($container, $definition->getContainer());
    }

    public function testIsCached()
    {
        $definition = new Definition('test', fn() => 'it is a test');
        $this->assertFalse($definition->isCached());
        $definition->cache(true);
        $this->assertTrue($definition->isCached());
    }

    public function testCache()
    {
        $definition = new Definition('test', fn() => 'it is a test');
        $this->assertFalse($definition->isCached());
        $definition->cache(true);
        $this->assertTrue($definition->isCached());
    }

    public function testConstruct()
    {
        $definition = new class(Bar::class) extends Definition {
            public function getId() {
                return $this->id;
            }
            public function getConcrete() {
                return $this->concrete;
            }
        };

        $this->assertSame(Bar::class, $definition->getId());
        $this->assertSame(Bar::class, $definition->getConcrete());

        $definition = new class(Bar::class, 'test') extends Definition {
            public function getId() {
                return $this->id;
            }
            public function getConcrete() {
                return $this->concrete;
            }
        };

        $this->assertSame(Bar::class, $definition->getId());
        $this->assertSame('test', $definition->getConcrete());
    }

    public function testAddParameter()
    {
        $container = new Container();
        $definition = new Definition(Baz::class, fn() => new Baz());
        $definition->setContainer($container);

        /** @var Baz $baz */
        $baz = $definition->get();
        $this->assertIsArray($baz->getValues());

        $definition = (new Definition(Baz::class))
            ->addParameter([3, 2, 1])
            ->setContainer($container);
        $baz = $definition->get();
        $this->assertIsArray($baz->getValues());
        $this->assertCount(3, $baz->getValues());
        $this->assertSame(3, $baz->getValues()[0]);
        $this->assertSame(2, $baz->getValues()[1]);
        $this->assertSame(1, $baz->getValues()[2]);
    }

    public function testGet()
    {
        $container = new Container();
        $definition = new Definition(Baz::class, fn() => new Baz());
        $definition->setContainer($container);

        $this->assertInstanceOf(Baz::class, $definition->get());
    }
}
