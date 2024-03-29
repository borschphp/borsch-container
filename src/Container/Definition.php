<?php
/**
 * @author debuss-a
 */

namespace Borsch\Container;

use Borsch\Container\Exception\{ContainerException, NotFoundException};
use Psr\Container\{ContainerExceptionInterface, ContainerInterface, NotFoundExceptionInterface};
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use TypeError;

/**
 * Class Definition
 * @package Borsch\Container
 */
class Definition
{

    protected array $parameters = [];

    protected array $methods = [];

    protected ContainerInterface $container;

    /**
     * Definition constructor.
     *
     * @param string $id
     * @param mixed $concrete
     * @param bool $cached
     */
    public function __construct(
        protected string $id,
        protected mixed $concrete = null,
        protected bool $cached = false
    ) {
        $this->concrete = $concrete === null ? $id : $concrete;
    }

    /**
     * @param mixed $value
     * @return Definition
     */
    public function addParameter(mixed $value): self
    {
        $this->parameters[] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return $this
     */
    public function addMethod(string $name, array $arguments = []): self
    {
        $this->methods[] = [$name, $arguments];

        return $this;
    }

    /**
     * @param ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface &$container): self
    {
        $this->container = &$container;

        return $this;
    }

    /**
     * @param bool $cached
     * @return $this
     */
    public function cache(bool $cached): self
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function get(): mixed
    {
        if (($this->id == $this->concrete && is_callable($this->concrete)) || is_callable($this->concrete)) {
            return $this->invokeAsCallable();
        }

        if (($this->id == $this->concrete || is_string($this->concrete)) && class_exists($this->concrete)) {
            return $this->invokeAsClass();
        }

        if ($this->id !== $this->concrete) {
            return $this->concrete;
        }

        throw NotFoundException::unableToFindEntry($this->id);
    }

    /**
     * @return object
     * @throws ContainerExceptionInterface
     * @throws NotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    protected function invokeAsClass(): object
    {
        try {
            $item = new ReflectionClass($this->concrete);
        } catch (ReflectionException $exception) {
            throw new NotFoundException(
                NotFoundException::unableToFindEntry($this->id),
                $exception->getCode(),
                $exception
            );
        }

        $constructor = $item->getConstructor();
        $object = is_null($constructor) ?
            $item->newInstance() :
            $this->getNewInstanceWithArgs($constructor, $item);

        $this->callObjectMethods($object);

        return $object;
    }

    /**
     * @param object $object
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function callObjectMethods(object $object): void
    {
        foreach ($this->methods as $method) {
            foreach ($method[1] as $key => $value) {
                if (is_string($value) && $this->container->has($value)) {
                    $method[1][$key] = $this->container->get($value);
                }
            }

            call_user_func_array([$object, $method[0]], $method[1]);
        }
    }

    /**
     * @param ReflectionMethod $constructor
     * @param ReflectionClass $item
     * @return object
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    protected function getNewInstanceWithArgs(ReflectionMethod $constructor, ReflectionClass $item): object
    {
        if (!count($this->parameters)) {
            $this->parameters = $this->getNewInstanceParameters($constructor);
        }

        return $item->newInstanceArgs($this->parameters);
    }

    /**
     * @param ReflectionMethod $constructor
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    protected function getNewInstanceParameters(ReflectionMethod $constructor): array
    {
        return array_reduce($constructor->getParameters(), function(array $parameters, ReflectionParameter $reflection_parameter) {
            $parameter = null;

            $type = $reflection_parameter?->getType()?->getName();
            if ($this->containerHasOrCanRetrieve($type)) {
                $parameter = $this->container->get($type);
            } elseif ($reflection_parameter->isOptional() && $reflection_parameter->isDefaultValueAvailable()) {
                $parameter = $reflection_parameter->getDefaultValue();
            }

            $parameters[] = $parameter;

            return $parameters;
        }, []);
    }

    /**
     * @param null|string $id
     * @return bool
     */
    protected function containerHasOrCanRetrieve(?string $id = null): bool
    {
        return $id && (class_exists($id) || $this->container->has($id));
    }

    /**
     * @return mixed
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function invokeAsCallable(): mixed
    {
        try {
            $function = new ReflectionFunction($this->concrete);
        } catch (ReflectionException|TypeError $exception) {
            throw new NotFoundException(
                NotFoundException::unableToFindEntry($this->id),
                $exception->getCode(),
                $exception
            );
        }

        if (!$function->getNumberOfParameters()) {
            return $function->invoke();
        }

        if (!count($this->parameters)) {
            foreach ($function->getParameters() as $param) {
                $type = $param->getType();
                if ($type) {
                    try {
                        $this->parameters[] = $this->container->get($type->getName());
                    } catch (NotFoundException $exception) {
                        throw ContainerException::unableToGetCallableParameter(
                            $type,
                            $this->id,
                            $exception
                        );
                    }
                }
            }
        }

        return $function->invokeArgs($this->parameters);
    }
}
