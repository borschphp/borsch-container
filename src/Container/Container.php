<?php declare(strict_types=1);
/**
 * @author debuss-a
 */

namespace Borsch\Container;

use Borsch\Container\Exception\{ContainerException, NotFoundException};
use Psr\Container\{ContainerExceptionInterface, ContainerInterface, NotFoundExceptionInterface};
use Doctrine\Common\Collections\ArrayCollection;
use ReflectionException;
use function spl_object_id;

/**
 * Class Container
 * @package Borsch\Container
 */
class Container implements ContainerInterface
{

    /** @var ArrayCollection<string, Definition> $definitions */
    protected ArrayCollection $definitions;

    protected bool $cache_by_default = false;

    /** @var array<string, mixed> $cache */
    protected array $cache = [];

    /** @var ArrayCollection<int, ContainerInterface> $delegates */
    protected ArrayCollection $delegates;

    protected bool $autowire = true;

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
     * This will add an entry in the container with a key as the class FQDN.
     *
     * Example:
     *
     * $container = new Container();
     * $container->get(MyClass::class);
     *
     * @param bool $autowire
     * @return self
     */
    public function setAutowiring(bool $autowire): self
    {
        $this->autowire = $autowire;

        return $this;
    }

    public function isAutowiring(): bool
    {
        return $this->autowire;
    }

    /**
     * Set the default cache behavior for the container.
     *
     * The cache for a definition is set when you add an item to the container.
     *
     * If set to true, the container will cache all the definitions.
     * If set to false, the container will not cache any definitions.
     * The cache behavior can be overridden on a per-definition basis.
     *
     * @param bool $cache
     * @return self
     */
    public function setCacheByDefault(bool $cache): self
    {
        $this->cache_by_default = $cache;

        return $this;
    }

    /**
     * @see Container::setCacheByDefault()
     */
    public function getCacheByDefault(): bool
    {
        return $this->cache_by_default;
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

            if (!$this->autowire) {
                throw NotFoundException::unableToFindEntry($id);
            }

            $definition = $this->set($id);
        }

        if ($definition instanceof ArrayCollection) {
            return $this->resolveDefinitionCollection($definition);
        }

        /** @var Definition $definition */
        if ($definition->isReference()) {
            return $this->get($definition->getConcrete()->references());
        }

        return $this->resolveDefinitionItem($definition, $id);
    }

    /**
     * Resolve a definition based on lookup priority.
     *
     * @param string $id
     * @return Definition|ArrayCollection<string, Definition>|null
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
     * @param ArrayCollection<string, Definition> $definitions
     * @return array<string, mixed>
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
        return $this
            ->delegates
            ->findFirst(fn($k, ContainerInterface $container) => $container->has($id))
            ->get($id);
    }

    /**
     * @inheritdoc
     *
     *  Implementation details:
     *  1. Checks if the ID exists in container definitions
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
            : new Definition($id, $definition, $this->cache_by_default);

        return $this->definitions[$id];
    }

    /**
     * Extend an existing definition with a callable.
     *
     * A new definition will be created with the ID $id in the container.
     *
     * @param callable $callable The callable to extend the definition with
     * @phpstan-param callable(mixed, Container): mixed $callable
     * @throws NotFoundException if $from is not found in the container
     * @throws ContainerException if $id is the same as $from
     */
    public function extend(string $id, callable $callable, string $from): Definition
    {
        if ($id === $from) {
            throw ContainerException::extendingWithSameIdAndFromForbidden($id);
        }

        if ($this->has($id)) {
            throw ContainerException::extendingAnExistingEntryIsForbidden($id);
        }

        if (!$this->has($from)) {
            throw NotFoundException::unableToFindEntry($from);
        }

        $this->definitions[$id] = (new Definition($id, $from))->setCallable($callable);

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

    /**
     * @throws NotFoundException
     */
    public function alias(string $alias, string $from): void
    {
        if (!$this->has($from)) {
            throw NotFoundException::unableToFindEntry($from);
        }

        $this->set($alias, new Reference($from));
    }
}
