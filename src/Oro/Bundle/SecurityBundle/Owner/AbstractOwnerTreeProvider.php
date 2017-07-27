<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;

abstract class AbstractOwnerTreeProvider implements OwnerTreeProviderInterface
{
    const CACHE_KEY = 'data';

    /** @var DatabaseChecker */
    private $databaseChecker;

    /** @var CacheProvider */
    private $cache;

    /** @var OwnerTreeInterface */
    private $tree;

    /**
     * @param DatabaseChecker $databaseChecker
     * @param CacheProvider   $cache
     */
    public function __construct(DatabaseChecker $databaseChecker, CacheProvider $cache)
    {
        $this->databaseChecker = $databaseChecker;
        $this->cache = $cache;
    }

    /**
     * @param OwnerTreeBuilderInterface $tree
     */
    abstract protected function fillTree(OwnerTreeBuilderInterface $tree);

    /**
     * Returns empty instance of the owner tree builder
     *
     * @return OwnerTreeBuilderInterface
     */
    protected function createTreeBuilder()
    {
        return new OwnerTree();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->cache->deleteAll();
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpCache()
    {
        $this->ensureTreeLoaded();
    }

    /**
     * {@inheritdoc}
     */
    public function getTree()
    {
        $this->ensureTreeLoaded();

        return $this->tree;
    }

    /**
     * Makes sure that tree data are loaded and cached
     */
    protected function ensureTreeLoaded()
    {
        if (null !== $this->tree) {
            // the tree is already loaded
            return;
        }

        if (null === $this->cache) {
            $this->tree = $this->loadTree();
        } else {
            $this->tree = $this->cache->fetch(self::CACHE_KEY);
            if (!$this->tree) {
                $this->tree = $this->loadTree();
                $this->cache->save(self::CACHE_KEY, $this->tree);
            }
        }
    }

    /**
     * Loads tree data
     *
     * @return OwnerTreeInterface
     */
    protected function loadTree()
    {
        $treeBuilder = $this->createTreeBuilder();
        if ($this->databaseChecker->checkDatabase()) {
            $this->fillTree($treeBuilder);
        }

        return $treeBuilder->getTree();
    }
}
