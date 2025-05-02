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

        /*if (!$this->has($id)) {

        }*/

        if (isset($this->definitions[$id])) {
            $definition = $this->definitions[$id];
        } elseif ($this->hasTag($id)) {
            $definition = array_filter(
                $this->definitions,
                fn(Definition $definition) => $definition->hasTag($id)
            );
        } elseif (array_reduce($this->delegates, fn($has, $container) => $has ?: $container->has($id), false)) {
            foreach ($this->delegates as $delegate) {
                if ($delegate->has($id)) {
                    return $delegate->get($id);
                }
            }
        } else {
            $definition = $this->set($id);
        }

        /*if (!isset($this->definitions[$id]) && $this->hasTag($id)) {
            $definitions = array_filter(
                $this->definitions,
                fn(Definition $definition) => $definition->hasTag($id)
            );

            $items = [];
            foreach ($definitions as $definition) {
                $item = $this->cache[$definition->getId()] ?? $definition
                    ->setContainer($this)
                    ->get();

                if ($definition->isCached()) {
                    $this->cache[$definition->getId()] = $item;
                }

                $items[] = $item;
            }

            return $items;
        }*/

        /*$definition = $this->definitions[$id] ?? $this->set($id);*/

        if (is_array($definition)) {
            $items = [];
            foreach ($definition as $def) {
                $item = $this->cache[$def->getId()] ?? $def
                    ->setContainer($this)
                    ->get();

                if ($def->isCached()) {
                    $this->cache[$def->getId()] = $item;
                }

                $items[] = $item;
            }

            return $items;
        }

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
        if (isset($this->definitions[$id])) {
            return true;
        }

        if ($this->hasTag($id)) {
            return true;
        }

        return array_reduce(
            $this->delegates,
            fn(bool $has, ContainerInterface $container) => $has ?: $container->has($id),
            false
        );
    }

    public function hasTag(string $tag): bool
    {
        foreach ($this->definitions as $definition) {
            if ($definition->hasTag($tag)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $id
     * @param mixed|null $definition
     * @return Definition
     */
    public function set(string $id, mixed $definition = null): Definition
    {
        $this->definitions[$id] = $definition instanceof Definition ?
            $definition :
            new Definition($id, $definition);

        return $this->definitions[$id];
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
