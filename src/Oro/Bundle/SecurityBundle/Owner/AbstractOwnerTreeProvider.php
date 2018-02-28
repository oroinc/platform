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
        $this->cache->save(self::CACHE_KEY, $this->loadTree());
    }

    /**
     * {@inheritdoc}
     */
    public function getTree()
    {
        $tree = $this->cache->fetch(self::CACHE_KEY);
        if (!$tree) {
            $tree = $this->loadTree();
            $this->cache->save(self::CACHE_KEY, $tree);
        }

        return $tree;
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
