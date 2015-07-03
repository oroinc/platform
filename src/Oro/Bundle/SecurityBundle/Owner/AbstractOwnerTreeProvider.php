<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractOwnerTreeProvider implements ContainerAwareInterface
{
    const CACHE_KEY = 'data';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var OwnerTree
     */
    protected $tree;

    /**
     * @return CacheProvider
     */
    abstract public function getCache();

    /**
     * @return OwnerTree
     */
    abstract protected function getTreeData();

    /**
     * @param OwnerTree $tree
     */
    abstract protected function fillTree(OwnerTree $tree);

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
     * Clear the owner tree cache
     */
    public function clear()
    {
        $this->getCache()->deleteAll();
    }

    /**
     * Warmup owner tree cache
     */
    public function warmUpCache()
    {
        $this->ensureTreeLoaded();
    }

    /**
     * Get ACL tree
     *
     * @return OwnerTree
     * @throws \Exception
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
        $em = $this->getManagerForClass($className);
        $tableName = $em->getClassMetadata($className)->getTableName();
        $result = false;
        try {
            $conn = $em->getConnection();

            if (!$conn->isConnected()) {
                $em->getConnection()->connect();
            }

            $result = $conn->isConnected() && (bool)array_intersect(
                    [$tableName],
                    $em->getConnection()->getSchemaManager()->listTableNames()
                );
        } catch (\PDOException $e) {
        }

        return $result;
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
