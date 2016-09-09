<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityBundle\Tools\SafeDatabaseChecker;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;

abstract class AbstractOwnerTreeProvider implements ContainerAwareInterface, OwnerTreeProviderInterface
{
    const CACHE_KEY = 'data';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var OwnerTreeInterface
     */
    protected $tree;

    /**
     * @return CacheProvider
     */
    abstract public function getCache();

    /**
     * @return OwnerTreeInterface
     *
     * @deprecated since 1.10. Use createTreeObject method instead
     */
    protected function getTreeData()
    {
        return null;
    }

    /**
     * @param OwnerTreeInterface $tree
     */
    abstract protected function fillTree(OwnerTreeInterface $tree);

    /**
     * @return MetadataProviderInterface
     */
    abstract protected function getOwnershipMetadataProvider();

    /**
     * Returns empty instance of OwnerTree object
     *
     * @return OwnerTreeInterface
     */
    protected function createTreeObject()
    {
        $tree = $this->getTreeData();
        if ($tree) {
            return $tree;
        }

        return new OwnerTree();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        if (!$this->container) {
            throw new \InvalidArgumentException('ContainerInterface is not injected');
        }

        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->getCache()->deleteAll();
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

        $cache = $this->getCache();
        if (null === $cache) {
            $this->tree = $this->loadTree();
        } else {
            $this->tree = $cache->fetch(self::CACHE_KEY);
            if (!$this->tree) {
                $this->tree = $this->loadTree();
                $cache->save(self::CACHE_KEY, $this->tree);
            }
        }
    }

    /**
     * Loads tree data and save them in cache
     *
     * @return OwnerTreeInterface
     */
    protected function loadTree()
    {
        $tree = $this->createTreeObject();
        if ($this->checkDatabase()) {
            $this->fillTree($tree);
        }

        return $tree;
    }

    /**
     * Check if user table exists in db
     *
     * @return bool
     */
    protected function checkDatabase()
    {
        $className = $this->getOwnershipMetadataProvider()->getBasicLevelClass();

        return SafeDatabaseChecker::tablesExist(
            $this->getManagerForClass($className)->getConnection(),
            SafeDatabaseChecker::getTableName($this->getContainer()->get('doctrine'), $className)
        );
    }

    /**
     * @param string $className
     * @return ObjectManager|EntityManager
     */
    protected function getManagerForClass($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className);
    }
}
