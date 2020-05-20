<?php
/**
 * @author debuss-a
 */

namespace Borsch\Container;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;

/**
 * Class Definition
 * @package Borsch\Container
 */
class Definition
{

    /** @var string */
    protected $id;

    /** @var mixed */
    protected $concrete;

    /** @var bool */
    protected $cached = false;

    /** @var array */
    protected $parameters = [];

    /** @var array */
    protected $methods = [];

    /** @var mixed */
    protected $resolved;

    /** @var ContainerInterface */
    protected $container;

    /**
     * Definition constructor.
     * @param string $id
     * @param mixed|null $concrete
     */
    public function __construct(string $id, $concrete = null)
    {
        $this->id = $id;
        $this->concrete = $concrete ?: $id;
    }

    /**
     * @param mixed $value
     * @return Definition
     */
    public function addParameter($value): Definition
    {
        $this->parameters[] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return $this
     */
    public function addMethod(string $name, array $arguments = []): Definition
    {
        $this->methods[] = [$name, $arguments];

        return $this;
    }

    /**
     * @param ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container): Definition
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @param bool $cached
     * @return $this
     */
    public function cache(bool $cached): Definition
    {
        $this->cached = $cached;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCached(): bool
    {
        return $this->cached;
    }

    /**
     * @return mixed
     * @throws NotFoundException
     */
    public function get()
    {
        if (($this->id == $this->concrete && is_callable($this->concrete)) ||
            is_callable($this->concrete)) {
            return ($this->concrete)();
        }

        try {
            $item = new ReflectionClass($this->concrete);
        } catch (ReflectionException $e) {
            throw new NotFoundException(
                sprintf('Unable to find the entry "%s".', $this->concrete),
                $e->getCode(),
                $e
            );
        }

        $constructor = $item->getConstructor();
        if (is_null($constructor)) {
            $object = $item->newInstance();
        } else {
            if (!count($this->parameters)) {
                foreach ($constructor->getParameters() as $param) {
                    $type = $param->getType();
                    if ($type) {
                        $this->parameters[] = $this->container->get($type->getName());
                    }
                }
            }

            $object = $item->newInstanceArgs($this->parameters);
        }

        foreach ($this->methods as $method) {
            foreach ($method[1] as $key => $value) {
                if (is_string($value) && $this->container->has($value)) {
                    $method[1][$key] = $this->container->get($value);
                }
            }

            call_user_func_array([$object, $method[0]], $method[1]);
        }

        return $object;
    }
}
