<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Loader\ArrayLoader;
use Knp\Menu\Provider\MenuProviderInterface;
use Knp\Menu\Util\MenuManipulator;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;

class BuilderChainProvider implements MenuProviderInterface
{
    const COMMON_BUILDER_ALIAS = '_common_builder';
    const MENU_LOCAL_CACHE_PREFIX = 'menuLocalCachePrefix';
    const IGNORE_CACHE_OPTION = 'ignoreCache';

    /**
     * Collection of builders grouped by alias.
     *
     * @var array[]
     */
    protected $builders = [];

    /**
     * Collection of menus.
     *
     * @var ItemInterface[]
     */
    protected $menus = [];

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var ArrayLoader
     */
    private $loader;

    /**
     * @var MenuManipulator
     */
    private $manipulator;

    /**
     * @var CacheProvider
     */
    private $cache;

    /**
     * @param FactoryInterface $factory
     * @param ArrayLoader $loader
     * @param MenuManipulator $manipulator
     */
    public function __construct(
        FactoryInterface $factory,
        ArrayLoader $loader,
        MenuManipulator $manipulator
    ) {
        $this->factory = $factory;
        $this->loader = $loader;
        $this->manipulator = $manipulator;
    }

    /**
     * Set cache instance
     *
     * @param CacheProvider $cache
     */
    public function setCache(CacheProvider $cache)
    {
        $this->cache = $cache;
        $this->cache->setNamespace('oro_menu_instance');
    }

    /**
     * Add builder to chain.
     *
     * @param BuilderInterface $builder
     * @param string           $alias
     */
    public function addBuilder(BuilderInterface $builder, $alias = self::COMMON_BUILDER_ALIAS)
    {
        $this->assertAlias($alias);

        if (!array_key_exists($alias, $this->builders)) {
            $this->builders[$alias] = [];
        }

        $this->builders[$alias][] = $builder;
    }

    /**
     * Build menu.
     *
     * @param string $alias
     * @param array $options
     * @return ItemInterface
     */
    public function get($alias, array $options = [])
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
     *
     * @param ItemInterface $menu
     */
    protected function sort(ItemInterface $menu)
    {
        if ($menu->hasChildren() && $menu->getDisplayChildren()) {
            $orderedChildren = [];
            $unorderedChildren = [];
            $hasOrdering = false;
            $children = $menu->getChildren();
            foreach ($children as &$child) {
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
    public function has($alias, array $options = [])
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
    protected function assertAlias($alias)
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
    protected function buildMenu($alias, array $options)
    {
        $menu = $this->factory->createItem($alias);

        /** @var BuilderInterface $builder */
        // try to find builder for the specified menu alias
        if (array_key_exists($alias, $this->builders)) {
            foreach ($this->builders[$alias] as $builder) {
                $builder->build($menu, $options, $alias);
            }
        }

        // In any case we must run common builder
        if (array_key_exists(self::COMMON_BUILDER_ALIAS, $this->builders)) {
            foreach ($this->builders[self::COMMON_BUILDER_ALIAS] as $builder) {
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
