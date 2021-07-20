<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Loader\ArrayLoader;
use Knp\Menu\Provider\MenuProviderInterface;
use Knp\Menu\Util\MenuManipulator;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;
use Psr\Container\ContainerInterface;

/**
 * This provider uses configured builders to build menus.
 */
class BuilderChainProvider implements MenuProviderInterface
{
    public const COMMON_BUILDER_ALIAS = '_common_builder';
    public const MENU_LOCAL_CACHE_PREFIX = 'menuLocalCachePrefix';
    public const IGNORE_CACHE_OPTION = 'ignoreCache';

    /** @var array [alias => [builder id, ...], ...] */
    private $builders;

    /** @var ContainerInterface */
    private $builderContainer;

    /** @var ItemInterface[] */
    private $menus = [];

    /** @var FactoryInterface */
    private $factory;

    /** @var ArrayLoader */
    private $loader;

    /** @var MenuManipulator */
    private $manipulator;

    /** @var CacheProvider */
    private $cache;

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

    /**
     * Set cache instance
     */
    public function setCache(CacheProvider $cache)
    {
        $this->cache = $cache;
        $this->cache->setNamespace('oro_menu_instance');
    }

    /**
     * Build menu.
     */
    public function get(string $alias, array $options = []): ItemInterface
    {
        $this->assertAlias($alias);
        $ignoreCache = array_key_exists(self::IGNORE_CACHE_OPTION, $options);
        $cacheAlias = $alias;
        if (!empty($options)) {
            $cacheAlias = $cacheAlias . md5(serialize($options));
        }

        if (!array_key_exists($cacheAlias, $this->menus)) {
            if (!$ignoreCache && $this->cache && $this->cache->contains($alias)) {
                $menuData = $this->cache->fetch($alias);
                $menu = $this->loader->load($menuData);
            } else {
                $menu = $this->buildMenu($alias, $options);
            }
            $this->menus[$cacheAlias] = $menu;
        } else {
            $menu = $this->menus[$cacheAlias];
        }
        return $menu;
    }

    /**
     * Reorder menu based on position attribute
     */
    private function sort(ItemInterface $menu)
    {
        if ($menu->hasChildren() && $menu->getDisplayChildren()) {
            $orderedChildren = [];
            $unorderedChildren = [];
            $hasOrdering = false;
            $children = $menu->getChildren();
            foreach ($children as $child) {
                if ($child->hasChildren() && $child->getDisplayChildren()) {
                    $this->sort($child);
                }
                $position = $child->getExtra('position');
                if ($position !== null) {
                    $orderedChildren[$child->getName()] = (int) $position;
                    $hasOrdering = true;
                } else {
                    $unorderedChildren[] = $child->getName();
                }
            }
            if ($hasOrdering) {
                asort($orderedChildren);
                $menu->reorderChildren(array_merge(array_keys($orderedChildren), $unorderedChildren));
            }
        }
    }

    /**
     * Checks whether a menu exists in this provider
     *
     * @param  string  $alias
     * @param  array   $options
     * @return boolean
     */
    public function has(string $alias, array $options = []): bool
    {
        $this->assertAlias($alias);

        if (array_key_exists($alias, $this->builders)) {
            return true;
        }

        $this->buildMenu($alias, $options);

        return array_key_exists($alias, $this->builders);
    }

    /**
     * Assert alias not empty
     *
     * @param string $alias
     * @throws \InvalidArgumentException
     */
    private function assertAlias($alias)
    {
        if (empty($alias)) {
            throw new \InvalidArgumentException('Menu alias was not set.');
        }
    }

    /**
     * @param string $alias
     * @param array $options
     * @return ItemInterface
     */
    private function buildMenu($alias, array $options)
    {
        $menu = $this->factory->createItem($alias, $options);

        // try to find builder for the specified menu alias
        if (array_key_exists($alias, $this->builders)) {
            foreach ($this->builders[$alias] as $builderId) {
                /** @var BuilderInterface $builder */
                $builder = $this->builderContainer->get($builderId);
                $builder->build($menu, $options, $alias);
            }
        }

        // in any case we must run common builder
        if (array_key_exists(self::COMMON_BUILDER_ALIAS, $this->builders)) {
            foreach ($this->builders[self::COMMON_BUILDER_ALIAS] as $builderId) {
                /** @var BuilderInterface $builder */
                $builder = $this->builderContainer->get($builderId);
                $builder->build($menu, $options, $alias);
            }
        }

        $this->sort($menu);

        if ($this->cache) {
            $this->cache->save($alias, $this->manipulator->toArray($menu));
        }

        return $menu;
    }
}
