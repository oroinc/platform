<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Loader\ArrayLoader;
use Knp\Menu\Provider\MenuProviderInterface;
use Knp\Menu\Util\MenuManipulator;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;

/**
 * This provider uses configured builders to build menus.
 */
class BuilderChainProvider implements MenuProviderInterface
{
    public const COMMON_BUILDER_ALIAS = '_common_builder';
    public const MENU_LOCAL_CACHE_PREFIX = 'menuLocalCachePrefix';
    public const IGNORE_CACHE_OPTION = 'ignoreCache';

    /** @var array [menu name => [builder id, ...], ...] */
    private array $builders;
    private ContainerInterface $builderContainer;
    private FactoryInterface $factory;
    private ArrayLoader $loader;
    private MenuManipulator $manipulator;
    private ?CacheItemPoolInterface $cache = null;
    /** @var BuilderInterface[] */
    private array $loadedBuilders = [];
    /** @var ItemInterface[] */
    private array $menus = [];

    public function __construct(
        array $builders,
        ContainerInterface $builderContainer,
        FactoryInterface $factory,
        ArrayLoader $loader,
        MenuManipulator $manipulator
    ) {
        $this->builders = $builders;
        $this->builderContainer = $builderContainer;
        $this->factory = $factory;
        $this->loader = $loader;
        $this->manipulator = $manipulator;
    }

    public function setCache(CacheItemPoolInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function get(string $name, array $options = []): ItemInterface
    {
        self::assertAliasNotEmpty($name);

        $cacheKey = $name;
        if (!empty($options)) {
            $cacheKey .= md5(serialize($options));
        }

        if (\array_key_exists($cacheKey, $this->menus)) {
            return $this->menus[$cacheKey];
        }

        $ignoreCache = \array_key_exists(self::IGNORE_CACHE_OPTION, $options);
        if (!$ignoreCache && $this->cache) {
            $cacheItem = $this->cache->getItem($name);
            $menu = $cacheItem->isHit() ? $this->loader->load($cacheItem->get()) : $this->buildMenu($name, $options);
        } else {
            $menu = $this->buildMenu($name, $options);
        }
        $this->menus[$cacheKey] = $menu;

        return $menu;
    }

    public function has(string $name, array $options = []): bool
    {
        self::assertAliasNotEmpty($name);

        if (\array_key_exists($name, $this->builders)) {
            return true;
        }

        $this->buildMenu($name, $options);

        return \array_key_exists($name, $this->builders);
    }

    /**
     * Reorders menu based on position attribute.
     */
    private function sort(ItemInterface $menu): void
    {
        if ($menu->count() && $menu->getDisplayChildren()) {
            $orderedChildren = [];
            $hasOrdering = false;
            $children = $menu->getChildren();
            foreach ($children as $child) {
                if ($child->count() && $child->getDisplayChildren()) {
                    $this->sort($child);
                }
                $position = $child->getExtra(MenuUpdateInterface::POSITION);
                if ($position !== null) {
                    $orderedChildren[$child->getName()] = (int) $position;
                    $hasOrdering = true;
                }
            }
            if ($hasOrdering) {
                $childrenNames = \array_keys(array_diff_key($children, $orderedChildren));

                asort($orderedChildren);
                foreach ($orderedChildren as $name => $position) {
                    \array_splice($childrenNames, $position, 0, $name);
                }

                $menu->reorderChildren($childrenNames);
            }
        }
    }

    private static function assertAliasNotEmpty(string $alias): void
    {
        if (empty($alias)) {
            throw new \InvalidArgumentException('Menu alias was not set.');
        }
    }

    private function buildMenu(string $name, array $options): ItemInterface
    {
        $menu = $this->factory->createItem($name, $options);

        // try to find builder for the specified menu alias
        if (\array_key_exists($name, $this->builders)) {
            foreach ($this->builders[$name] as $builderId) {
                $this->getBuilder($builderId)->build($menu, $options, $name);
            }
        }

        // in any case we must run common builder
        if (\array_key_exists(self::COMMON_BUILDER_ALIAS, $this->builders)) {
            foreach ($this->builders[self::COMMON_BUILDER_ALIAS] as $builderId) {
                $this->getBuilder($builderId)->build($menu, $options, $name);
            }
        }

        $this->sort($menu);

        if (null !== $this->cache) {
            $cacheItem = $this->cache->getItem($name);
            $cacheItem->set($this->manipulator->toArray($menu));
            $this->cache->save($cacheItem);
        }

        return $menu;
    }

    private function getBuilder(string $builderId): BuilderInterface
    {
        if (isset($this->loadedBuilders[$builderId])) {
            return $this->loadedBuilders[$builderId];
        }

        $builder = $this->builderContainer->get($builderId);
        $this->loadedBuilders[$builderId] = $builder;

        return $builder;
    }
}
