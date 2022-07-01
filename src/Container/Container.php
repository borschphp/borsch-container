<?php
/**
 * @author debuss-a
 */

namespace Borsch\Container;

use Psr\Container\{
    ContainerExceptionInterface,
    ContainerInterface,
    NotFoundExceptionInterface
};
use ReflectionException;

/**
 * Class Container
 * @package Borsch\Container
 */
class Container implements ContainerInterface
{

    /**
     * Container constructor.
     */
    public function __construct(
        /** @var Definition[] */
        protected array $definitions = [],
        protected array $cache = []
    ) {
        $this
            ->set(ContainerInterface::class, fn() => $this)
            ->cache(true);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id
     * @return mixed
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function get(string $id): mixed
    {
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }

        $definition = $this->definitions[$id] ?? $this->set($id);
        $item = $definition->setContainer($this)->get();

        if ($definition->isCached()) {
            $this->cache[$id] = $item;
        }

        return $item;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->definitions[$id]);
    }

    /**
     * @param string $id
     * @param mixed|null $definition
     * @return Definition
     */
    public function set(string $id, mixed $definition = null): Definition
    {
        $this->definitions[$id] = new Definition($id, $definition);

        return $this->definitions[$id];
    }
}
