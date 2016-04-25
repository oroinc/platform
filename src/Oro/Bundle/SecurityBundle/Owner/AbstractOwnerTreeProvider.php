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
     */
    abstract protected function getTreeData();

    /**
     * @param OwnerTreeInterface $tree
     */
    abstract protected function fillTree(OwnerTreeInterface $tree);

    /**
     * @return MetadataProviderInterface
     */
    abstract protected function getOwnershipMetadataProvider();

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

        if ($this->tree === null) {
            throw new \Exception('ACL tree cache was not warmed');
        }

        return $this->tree;
    }

    /**
     * Makes sure that tree data are loaded and cached
     */
    protected function ensureTreeLoaded()
    {
        if ($this->tree === null) {
            $treeData = null;
            if ($this->getCache()) {
                $treeData = $this->getCache()->fetch(self::CACHE_KEY);
            }
            if ($treeData) {
                $this->tree = $treeData;
            } else {
                $this->tree = $this->loadTree();
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
        $treeData = $this->getTreeData();
        if ($this->checkDatabase()) {
            $this->fillTree($treeData);
        }

        if ($this->getCache()) {
            $this->getCache()->save(self::CACHE_KEY, $treeData);
        }

        $this->tree = $treeData;

        return $treeData;
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
