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

    /** @var mixed[] */
    protected array $parameters = [];

    /** @var array<string, array<mixed>> */
    protected array $methods = [];

    /** @var string[] */
    protected array $tags = [];

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

    public function getId(): string
    {
        return $this->id;
    }

    public function getConcrete(): mixed
    {
        return $this->concrete;
    }

    public function addParameter(mixed $value, string $key = null): self
    {
        if ($key !== null) {
            $this->parameters[$key] = $value;
        } else {
            $this->parameters[] = $value;
        }

        return $this;
    }

    public function addParameters(array $values): self
    {
        $this->parameters = $values;

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

    public function addTag(string $name): self
    {
        $this->tags[] = $name;

        return $this;
    }

    /** @param string[] $tags */
    public function addTags(array $tags): self
    {
        foreach ($tags as $tag) {
            $this->addTag($tag);
        }

        return $this;
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags);
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

    public function isReference(): bool
    {
        return $this->concrete instanceof Reference;
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
            throw ContainerException::unableToGetClassReflection($this->concrete, $exception);
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

        foreach ($this->parameters as $index => $parameter) {
            if ($parameter instanceof Reference) {
                $this->parameters[$index] = $this->container->get($parameter->reference());
            }
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
            throw ContainerException::unableToGetFunctionReflection($this->concrete, $exception);
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
