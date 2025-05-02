<?php
/**
 * @author debuss-a
 */

namespace Borsch\Container;

use Borsch\Container\Exception\NotFoundException;
use Psr\Container\{
    ContainerExceptionInterface,
    ContainerInterface,
    NotFoundExceptionInterface
};
use Doctrine\Common\Collections\ArrayCollection;
use ReflectionException;

/**
 * Class Container
 * @package Borsch\Container
 */
class Container implements ContainerInterface
{

    /** @var ArrayCollection<Definition> $definitions */
    protected ArrayCollection $definitions;

    protected array $cache = [];

    /** @var ArrayCollection<ContainerInterface> $delegates */
    protected ArrayCollection $delegates;

    protected bool $autowire_unregistered_class = true;

    /**
     * Container constructor.
     */
    public function __construct()
    {
        $this->definitions = new ArrayCollection();
        $this->delegates = new ArrayCollection();

        $this
            ->set(ContainerInterface::class, $this)
            ->cache(true);
    }

    /**
     * If set to true, the container will try to autowire unregistered classes.
     * This is useful for classes that are not registered in the container but are still needed.
     *
     * This will add an entry in the container with key as the class FQDN.
     *
     * Example:
     *
     * $container = new Container();
     * $container->get(MyClass::class);
     *
     * @param string $id
     * @return Definition
     */
    public function autowireUnregisteredClass(bool $autowire): self
    {
        $this->autowire_unregistered_class = $autowire;

        return $this;
    }

    /**
     * @inheritDoc
     * @throws ReflectionException
     */
    public function get(string $id): mixed
    {
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }

        $definition = $this->resolveDefinition($id);

        if ($definition === null) {
            if ($this->delegatedHave($id)) {
                return $this->getDelegatedItem($id);
            }

            if (!class_exists($id) || !$this->autowire_unregistered_class) {
                // Can't be null for now, an option will come later to decide if we want to autowire unregistered classes
                throw new NotFoundException(sprintf('No entry found for "%s".', $id));
            }

            $definition = $this->set($id);
        }

        if ($definition instanceof ArrayCollection) {
            return $this->resolveDefinitionCollection($definition);
        }

        return $this->resolveDefinitionItem($definition, $id);
    }

    /**
     * Resolve a definition based on lookup priority.
     */
    protected function resolveDefinition(string $id): Definition|ArrayCollection|null
    {
        if ($this->definitions->containsKey($id)) {
            return $this->definitions->get($id);
        }

        if ($this->hasTag($id)) {
            return $this->definitions->filter(fn(Definition $definition) => $definition->hasTag($id));
        }

        return null;
    }

    /**
     * Resolve a collection of definitions.
     *
     * @param ArrayCollection<Definition> $definitions
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    protected function resolveDefinitionCollection(ArrayCollection $definitions): array
    {
        return $definitions->map(function (Definition $definition) {
            $item = $definition->setContainer($this)->get();

            if ($definition->isCached()) {
                $this->cache[$definition->getId()] = $item;
            }

            return $item;
        })->toArray();
    }

    /**
     * Resolve a single definition item.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    protected function resolveDefinitionItem(Definition $definition, string $id): mixed
    {
        $item = $definition->setContainer($this)->get();

        if ($definition->isCached()) {
            $this->cache[$id] = $item;
        }

        return $item;
    }

    /**
     * Get an item from a delegated container.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getDelegatedItem(string $id): mixed
    {
        return $this->delegates
            ->findFirst(fn($k, ContainerInterface $container) => $container->has($id))
            ->get($id);
    }

    /**
     * @inheritdoc
     *
     *  Implementation details:
     *  1. Checks if the ID exists in the container definitions
     *  2. Checks if the ID matches a registered tag
     *  3. Checks if any delegated containers have the ID
     */
    public function has(string $id): bool
    {
        if ($this->definitions->containsKey($id)) {
            return true;
        }

        if ($this->hasTag($id)) {
            return true;
        }

        return $this->delegatedHave($id);
    }

    /**
     * Check if any of the definitions have the requested tag.
     */
    public function hasTag(string $tag): bool
    {
        return $this->definitions->exists(fn($k, Definition $definition) => $definition->hasTag($tag));
    }

    /**
     * Check if any of the delegated containers have the requested ID.
     */
    protected function delegatedHave(string $id): bool
    {
        return $this->delegates->exists(fn($k, ContainerInterface $container) => $container->has($id));
    }

    /**
     * @param string $id
     * @param mixed|null $definition
     * @return Definition
     */
    public function set(string $id, mixed $definition = null): Definition
    {
        $this->definitions[$id] = $definition instanceof Definition
            ? $definition
            : new Definition($id, $definition);

        return $this->definitions[$id];
    }

    /**
     * Entrust another PSR-11 container in case of missing a requested entry ID.
     *
     * @param ContainerInterface $container
     * @return self
     */
    public function delegate(ContainerInterface $container): Container
    {
        if (spl_object_id($container) !== spl_object_id($this)) {
            $this->delegates->add($container);
        }

        return $this;
    }
}
