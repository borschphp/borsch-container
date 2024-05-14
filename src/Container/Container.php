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

    /** @var Definition[] $definitions */
    protected array $definitions = [];

    protected array $cache = [];

    /** @var ContainerInterface[] $delegates */
    protected array $delegates = [];

    /**
     * Container constructor.
     */
    public function __construct()
    {
        $this
            ->set(ContainerInterface::class, $this)
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

        if (!isset($this->definitions[$id])) {
            foreach ($this->delegates as $delegate) {
                if ($delegate->has($id)) {
                    return $delegate->get($id);
                }
            }
        }

        $definition = $this->definitions[$id] ?? $this->set($id);

        $item = $definition
            ->setContainer($this)
            ->get();

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
        return isset($this->definitions[$id]) || array_reduce(
                $this->delegates,
                fn($has, $container) => $has ?: $container->has($id),
                false
            );
    }

    /**
     * @param string $id
     * @param mixed|null $definition
     * @return Definition
     */
    public function set(string $id, mixed $definition = null): Definition
    {
        return $this->definitions[$id] ??= $definition instanceof Definition ?
            $definition :
            new Definition($id, $definition);
    }

    /**
     * Entrust another PSR-11 container in case of missing a requested entry ID.
     *
     * @param ContainerInterface $container
     * @return Container
     */
    public function delegate(ContainerInterface $container): Container
    {
        if (spl_object_id($container) !== spl_object_id($this)) {
            $this->delegates[] = $container;
        }

        return $this;
    }
}
